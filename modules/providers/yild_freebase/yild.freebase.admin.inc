<?php

/**
 * @file
 * Yild configuration UI.
 */

/**
 * Implements hook_admin_settings_form().
 */
function yild_freebase_admin_settings_form($form, &$form_state) {
  $form = array();

  $form['yild_freebase_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Yild Freebase configuration'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['yild_freebase_settings']['yild_freebase_googlekey'] = array(
    '#type' => 'textfield',
    '#title' => t('Google Api key'),
    '#description' => t('The Google Api key to use for Freebase autocomplete lookups.'),
    '#default_value' => variable_get('yild_freebase_googlekey', ''),
    '#disabled' => FALSE,
    '#size' => 100,
    '#maxlength' => 100,
  );

  $form['yild_freebase_settings']['yild_freebase_show_description'] = array(
    '#type' => 'checkbox',
    '#title' => t('Autocomplete show description with image'),
    '#description' => t('If checked, shows a slightly longer description in a floating box next to the highlighted autocomplete line if Freebase provided it. One image is also fetched if present.'),
    '#default_value' => variable_get('yild_freebase_show_description', 1),
  );

  $form['yild_freebase_settings']['yild_freebase_get_additional_data'] = array(
    '#type' => 'checkbox',
    '#title' => t('Fetch additional data from Freebase'),
    '#description' => t('If checked, will fetch and save additional data for Freebase terms, such as description, image and geolocation.'),
    '#default_value' => variable_get('yild_freebase_get_additional_data', 1),
  );

  return system_settings_form($form);
}
