<?php

/**
 * @file
 * Yild configuration UI.
 */

/**
 * Implements hook_admin_settings_form().
 */
function yild_kg_admin_settings_form($form, &$form_state) {
  $form = array();

  $form['yild_kg_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Yild Knowledge graph configuration'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['yild_kg_settings']['yild_kg_googlekey'] = array(
    '#type' => 'textfield',
    '#title' => t('Google Api key'),
    '#description' => t('The Google Api *server* key to use for Knowledge graph autocomplete lookups.'),
    '#default_value' => variable_get('yild_kg_googlekey', ''),
    '#disabled' => FALSE,
    '#size' => 100,
    '#maxlength' => 100,
  );

  $form['yild_kg_settings']['yild_kg_show_description'] = array(
    '#type' => 'checkbox',
    '#title' => t('Autocomplete show description with image'),
    '#description' => t('If checked, shows a slightly longer description in a floating box next to the highlighted autocomplete line if Knowledge graph provided it. One image is also fetched if present.'),
    '#default_value' => variable_get('yild_kg_show_description', 1),
  );

  $form['yild_kg_settings']['yild_kg_get_additional_data'] = array(
    '#type' => 'checkbox',
    '#title' => t('Fetch additional data from Knowledge graph'),
    '#description' => t('If checked, will fetch and save additional data for Knowledge graph terms, such as description, image and geolocation.'),
    '#default_value' => variable_get('yild_kg_get_additional_data', 1),
  );

  return system_settings_form($form);
}
