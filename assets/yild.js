/**
 * @file
 * Yild javascript functionality.
 */

(function ($) {
  Drupal.behaviors.yild = {
    attach: function (context) {
      if (context === document) {
        var id_parts;
        // Listen to the analyse button if one is present.
        $('div.yild_analyse_button').click(function () {

          var btn = $(this);
          var analyse_container = btn.parents('.yild_analyse_container');
          var tags_container = analyse_container.find('.yild_suggestions_container');
          var target_container = analyse_container.siblings('.yild_tags_container');
          var hidden_field = $(this).yildGetHiddenField();
          if (!$(this).hasClass('inprogress')) {
            $(this).addClass('inprogress ajax-progress ajax-progress-throbber');
            // Fetch the content of all textareas with the name "body".
            var textfields = [];
            $('input#edit-title').each(function () {
              textfields.push($(this).val());
            });
            if (BUE.instances.length > 0) {
              $.each(BUE.instances, function (index, editor_instance) {
                textfields.push(editor_instance.getContent());
              });
            }
            else {
              $('textarea').each(function () {
                if ($(this).attr('id').indexOf('body') !== -1 && $(this).val().length > 30) {
                  textfields.push($(this).val());
                }
              });
            }

            if (textfields.length > 0) {
              // Call the analyse url as a post ajax request to fetch term suggestions.
              analyse_container.find('div.yild_throbber').show();
              $.post(Drupal.settings.basePath + 'yild/analyse', {text_to_analyse: textfields.join("\n")}, function (data) {
                // Loop through all hits and place them as suggestions under their
                // respective provider fields.
                $.each(data, function (key, val) {
                  id_parts = key.split(':');
                  var termid = id_parts.join(':').trim();
                  if (target_container.find('li.yild_term[data-id="' + termid + '"]').length === 0) {
                    tags_container.addYildTag(key, val.name, val.disambiguator, null, hidden_field, true, val.description);
                  }
                });
                if (data.debug) {
                  $('fieldset.yild_fieldset div.debugcontainer').remove();
                  $('fieldset.yild_fieldset').last().append('<div class="debugcontainer">' + data.debug.toString() + '</div>');
                }
                analyse_container.find('div.yild_throbber').hide();
                analyse_container.find('div.yild_analyse_label').show();
              });
            }
            $(this).removeClass('inprogress');
          }
        });
      }

      $('input.yild_autocomplete').once(function () {
        var acfield = $(this);
        var tagscontainer = $(this).parents('.yild_fieldset').find('ul.yild_tags_container');
        var hidden_field = $(this).yildGetHiddenField();
        acfield.bind('autocompleteSelect', function (e) {
          var selected_value = $(this).val();
          var value_parts = selected_value.split('|');
          var id = value_parts[0];
          var label = value_parts[1];
          var disambiguator = '';
          if (value_parts.length >= 3) {
            disambiguator = value_parts[2];
          }

          var alt_id = '';
          var alt_disambiguator = '';

          // If id contains double parameters, make a second term-button.
          if (id.indexOf('+') !== -1 && id.indexOf(':') !== -1) {
            var id_provider_parts = id.split(':');
            var providers = id_provider_parts.shift();
            var ids = id_provider_parts.join(':');

            var provider_parts = providers.split('+');
            var id_parts = ids.split('+');
            var disambiguator_parts = disambiguator.split('+');
            if (disambiguator_parts.length === 2) {
              disambiguator = disambiguator_parts[1];
              alt_disambiguator = disambiguator_parts[0];
            }

            if (id_parts.length >= 2 && provider_parts.length >= 2) {
              alt_id = provider_parts[0] + ':' + id_parts[0];
              id = provider_parts[1] + ':' + id_parts[1];
            }
          }

          var data = '';
          if (value_parts.length >= 4) {
            data = value_parts[3];
          }
          if (tagscontainer.length > 0) {
            tagscontainer.addYildTag(id, label, disambiguator, data, hidden_field);
            if (alt_id) {
              // Add alternative terms to their own providers if we have
              // multiple fields using different providers.
              tagscontainer.yildAddTagToProviderField(alt_id, label, alt_disambiguator ? alt_disambiguator : disambiguator, null, hidden_field);
            }
            tagscontainer.yildMoveValues(hidden_field);
            tagscontainer.yildRefreshButtons();
            acfield.val('');
          }
          else {
            // Unable to find container.
          }
        });

        // If we landed on this page because of a form validation error, we need
        // to make term buttons out of all the terms described in the hidden
        // fields.
        if ($('input.error').length > 0) {
          tagscontainer.yildMakeButtonsFromHiddenField(hidden_field);
        }

        tagscontainer.yildRefreshButtons();
        tagscontainer.yildRefreshProgressBar();
      });

      // Check if jQuery-ui sortable is loaded.
      if ($.fn.sortable !== undefined) {
        $('ul.yild_tags_container').each(function () {
          var container = $(this);
          container.sortable({
            stop: function (event, ui) {
              container.yildMoveValues(container.yildGetHiddenField());
            },
            placeholder: 'yild_placeholder',
            forcePlaceholderSize: true
          });
        });
      }

      // Ask each yild field to move value to hidden field.
      $('.yild_tags_container').each(function () {
        $(this).yildMoveValues($(this).siblings('.hidden_yild_field'));
      });
    }
  };

  /**
   * Prototype for adding term buttons to a container.
   *
   * @param {int} id - Unique term id.
   * @param {string} label - Term label.
   * @param {string} disambiguator - Term disambiguator that describes what type of concept it is.
   * @param {array} data - Various data associated with the concept.
   * @param {element} hidden_field - The jQuery element for a hidden_field.
   * @param {bool} inactive - Whether the term button should be rendered as inactive.
   * @param {string} description - The term description.
   */
  $.fn.addYildTag = function (id, label, disambiguator, data, hidden_field, inactive, description) {
    // Only add term if it isn't already there.
    label = label.trim();
    disambiguator = disambiguator.trim();
    description = description || '';
    var id_parts = id.trim().split(':');
    if (id_parts.length >= 2) {
      var provider = id_parts.shift().trim();
      var termid = id_parts.join(':').trim();
      var idfix = termid.replace(' ', '').trim();
      // Prevent duplicates under the same autocomplete field.
      if ($(this).find('li.yild_term[data-id="' + termid + '"]').length === 0) {
        if (!data) {
          data = '';
        }
        $(this).append('<li class="yild_term ' + (inactive ? 'yild_inactive' : '') +
          '" id="' + provider + idfix + '" data-id="' + termid +
          '" data-provider="' + provider + '" data-label="' + label +
          '" data-disambiguator="' + disambiguator + '" data-data="' + data +
          '"><div class="yild_remove">&otimes;</div><span class="label">' +
          (Drupal.settings.yild.yild_show_provider ? provider + ': ' : '') + label +
          (disambiguator.length > 0 || description.length > 0 ? ' <span class="disambiguator">(' + (disambiguator ? disambiguator : description) + ')</span>' : '') +
          '</span></li>');
        $(this).yildRefreshButtons();
      }
    }
  };

  /**
   * Adds a term to all Yild fields using a specific provider.
   *
   * @param {int} id - Unique term id.
   * @param {string} label - Term label.
   * @param {string} disambiguator - Term disambiguator that describes what type of concept it is.
   * @param {array} data - Various data associated with the concept.
   * @param {element} hidden_field_origin - The jQuery element for a hidden_field.
   * @param {bool} inactive - Whether the term button should be rendered as inactive.
   */
  $.fn.yildAddTagToProviderField = function (id, label, disambiguator, data, hidden_field_origin, inactive) {
    var id_parts = id.trim().split(':');
    if (id_parts.length >= 2) {
      var provider = id_parts.shift().trim();
      $('input.' + provider).parents('.yild_fieldset').find('ul.yild_tags_container').each(function () {
        var hidden_field = $(this).parent().find('input.hidden_yild_field');
        $(this).addYildTag(id, label, disambiguator, data, hidden_field, inactive);
        $(this).yildMoveValues(hidden_field);
      });
    }
  };

  /**
   * Add values to the hidden fields from the container with term buttons.
   *
   * @param {element} hidden_field - The jQuery element for a hidden_field.
   */
  $.fn.yildMoveValues = function (hidden_field) {
    var id_list = [];
    $(this).find('li.yild_term').not('.yild_inactive').each(function () {
      // Push the id within double quotes, so we don't break it if the id
      // happens to contain a comma.
      // Also fix label and disambiguator that might contain quotes.
      var label = disambiguator = '';
      if ($(this).data('label')) {
        label = String($(this).data('label')).replace(/"/g, '&quot;');
      }
      if ($(this).data('disambiguator')) {
        disambiguator = String($(this).data('disambiguator')).replace(/"/g, '&quot;');
      }

      var idval = '"' + $(this).data('provider') + ':' + $(this).data('id') + '|' + label + '|' + disambiguator + '|' + $(this).data('data') + '"';
      id_list.push(idval);
    });
    hidden_field.val(id_list.join(','));
    $(this).yildRefreshProgressBar();
  };

  /**
   * Refresh all term button handlers, such as removing a term.
   *
   * This method is used on tag containers.
   */
  $.fn.yildRefreshButtons = function () {
    var container = $(this);
    var hidden_field = container.yildGetHiddenField();
    $(this).find('div.yild_remove').each(function () {
      $(this).yildRefreshRemove(container, hidden_field);
    });
    $(this).find('.yild_inactive').each(function () {
      $(this).unbind('click');
      $(this).click(function () {
        $(this).unbind('click');
        $(this).removeClass('yild_inactive');
        var target_container = $(this).parent().parent().siblings('.yild_tags_container');
        if (target_container.find('#' + $(this).attr('id')).length === 0) {
          $(this).detach().appendTo(target_container);
          $(this).find('div.yild_remove').yildRefreshRemove(target_container, hidden_field);
          target_container.yildMoveValues(hidden_field);
        }
        else {
          $(this).remove();
        }
      });
    });
  };

  /**
   * Refreshes the remove event handler for a given term button.
   *
   * @param {element} container - The container in which the removed term button is.
   * @param {element} hidden_field - The jQuery element for a hidden_field.
   */
  $.fn.yildRefreshRemove = function (container, hidden_field) {
    $(this).unbind('click');
    $(this).click(function () {
      $(this).parent().remove();
      if (!$(this).parent().hasClass('yild_inactive')) {
        container.yildMoveValues(hidden_field);
      }
    });
  };

  /**
   * If a progress bar exists for the ideal amount of terms, we show it and update it for the current situation.
   */
  $.fn.yildRefreshProgressBar = function () {
    var progressBar = $(this).siblings('.yild_progress_bar');
    if (progressBar.length > 0) {
      var percentage = Math.round(100 * $(this).find('.yild_term').not('.yild_inactive').length / progressBar.data('amount'));
      if (percentage > 130) {
        percentage = 130;
      }
      var marked = false;
      $.each([100, 50], function (index, value) {
        progressBar.children('.bar').removeClass('bar-' + value);
        if (percentage >= value && !marked) {
          marked = true;
          progressBar.children('.bar').addClass('bar-' + value);
        }
      });
      progressBar.children('.bar').width(percentage + '%');
    }
  };

  /**
   * Returns the hidden field from within a container.
   *
   * @return {element} the hidden input field.
   */
  $.fn.yildGetHiddenField = function () {
    return $(this).parents('.yild_fieldset').find('input.hidden_yild_field');
  };

  /**
   * This is called only on form validation error to repopulate tags.
   *
   * If a form is incorrectly filled out, this will be called to remake pill
   * buttons from the hidden field submitted with the wrongly filled out form.
   *
   * @param {element} hidden_field - The jQuery element for a hidden_field.
   */
  $.fn.yildMakeButtonsFromHiddenField = function (hidden_field) {
    var hiddenval = hidden_field.val();
    var button_parts;
    if (hiddenval.indexOf('|') !== -1) {
      // Explode strings at comma that may or may not be enclosed by double quotes.
      // See: http://stackoverflow.com/questions/11456850/split-a-string-by-commas-but-ignore-commas-within-double-quotes-using-javascript.
      var hiddenvals = hiddenval.match(/(".*?"|[^",\s]+)(?=\s*,|\s*$)/g);
      for (var h = 0; h < hiddenvals.length; h++) {
        // Remove enclosing quotes.
        hiddenvals[h] = hiddenvals[h].replace(/(^"|"$)/g, '');
        button_parts = hiddenvals[h].split('|');
        if (button_parts.length >= 3) {
          // Make sure array indeces are not empty.
          for (var i = 3; i <= 4; i++) {
            if (!button_parts[i]) {
              button_parts[i] = '';
            }
          }
          $(this).addYildTag(button_parts[0], button_parts[1], button_parts[2], button_parts[3], hidden_field, false);
        }
      }
    }
  };
})(jQuery);
