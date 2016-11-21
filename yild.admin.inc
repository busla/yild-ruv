<?php

/**
 * @file
 * Yild configuration UI.
 */

/**
 * Implements hook_admin_settings_form().
 */
function yild_admin_settings_form($form, &$form_state) {
  $form = array();

  $form['yild_global_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Yild configuration'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['yild_global_settings']['yild_result_language'] = array(
    '#type' => 'textfield',
    '#title' => t('Results language'),
    '#description' => t('The two letter language code for the results. Default is "en" for English.'),
    '#default_value' => variable_get('yild_result_language', 'en'),
    '#disabled' => FALSE,
    '#size' => 3,
    '#maxlength' => 2,
  );

  $form['yild_global_settings']['yild_show_progress_bar'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show tag progress bar'),
    '#description' => t('When enabled (default), a progress bar will be shown when tagging to indicate whether the suggested amount of terms have been entered.'),
    '#default_value' => variable_get('yild_show_progress_bar', "1"),
  );

  $form['yild_global_settings']['yild_update_terms'] = array(
    '#type' => 'checkbox',
    '#title' => t('Update terms with new data'),
    '#description' => t('When enabled, existing terms will be updated with the data received from the provider whenever a tag is reused. Default: disabled.'),
    '#default_value' => variable_get('yild_update_terms', "0"),
  );

  $form['yild_global_settings']['yild_progress_bar_amount'] = array(
    '#type' => 'select',
    '#title' => t('Minimum suggested term amount'),
    '#description' => t('The amount of terms that each yild field should ideally have.'),
    '#default_value' => variable_get('yild_progress_bar_amount', 8),
    '#options' => array(
      '1' => t('1 term'),
      '2' => t('2 terms'),
      '3' => t('3 terms'),
      '4' => t('4 terms'),
      '5' => t('5 terms'),
      '6' => t('6 terms'),
      '7' => t('7 terms'),
      '8' => t('8 terms'),
      '9' => t('9 terms'),
      '10' => t('10 terms'),
    ),
  );

  $form['yild_global_settings']['yild_show_provider'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show provider name in terms.'),
    '#description' => t('When enabled (default), each selected term will be shown with a prefix of the provider that it was fetched from. To save space you can disable this, especially if you use only one provider.'),
    '#default_value' => variable_get('yild_show_provider', "1"),
  );

  $form['yild_global_settings']['yild_expose_endpoint'] = array(
    '#type' => 'checkbox',
    '#title' => t('Expose Yild endpoint'),
    '#description' => t('When enabled (default), other sites running yild could ask you for information about your content tagged with a specific Yild term.'),
    '#default_value' => variable_get('yild_expose_endpoint', "1"),
  );

  if (count(module_implements('yild_improve_term_pages')) > 0) {
    $form['yild_global_settings']['yild_improve_term_pages'] = array(
      '#type' => 'checkbox',
      '#title' => t('Improved Yild term pages'),
      '#description' => t('When enabled, term pages will be improved by the provider modules when possible. This might mean term pages contain articles with related terms as well as the original term.'),
      '#default_value' => variable_get('yild_improve_term_pages', "0"),
    );
  }

  $form['yild_global_settings']['security_info'] = array(
    '#markup' => '<p class="warning">Please note that password and ip-list are treated as <strong>OR-conditions</strong>. That means you must either be on the IP-list <strong>OR</strong> have the correct password to be let through. You don\'t need both.</p>',
  );

  $form['yild_global_settings']['yild_api_password_salt'] = array(
    '#type' => 'textfield',
    '#title' => t('Yild API hash salt.'),
    '#description' => t('The salt to use for protecting API request. The final parameter of the API request should be the next to last parameter concatenated with this salt and run through MD5, so for the API request http://example.com/yild/api/provider/freebase, you should add a last parameter MD5("freebase" . $salt). Leave empty to have the API endpoint completely open, which could potentially be a point of entry for increasing your server load.'),
    '#default_value' => variable_get('yild_api_password_salt', ''),
    '#disabled' => FALSE,
    '#size' => 50,
    '#maxlength' => 50,
  );

  $form['yild_global_settings']['yild_api_ip_list'] = array(
    '#type' => 'textfield',
    '#title' => t('The list of IP:s allowed to access the Yild API endpoint.'),
    '#description' => t('A comma separated list of IP:s. If this field is non-empty, the requesting ip is matched against all valid ip:s in this list and only those request matching an ip will be let through.'),
    '#default_value' => variable_get('yild_api_ip_list', ''),
    '#disabled' => FALSE,
    '#size' => 200,
    '#maxlength' => 4096,
  );

  return system_settings_form($form);
}
