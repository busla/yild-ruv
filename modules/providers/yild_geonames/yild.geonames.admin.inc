<?php

/**
 * @file
 * Yild configuration UI.
 */

/**
 * Implements hook_admin_settings_form().
 */
function yild_geonames_admin_settings_form($form, &$form_state) {
  $form = array();

  $form['yild_geonames_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Yild Geonames configuration'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['yild_geonames_settings']['yild_geonames_username'] = array(
    '#type' => 'textfield',
    '#title' => t('Geonames username'),
    '#description' => t('The mandatory username for Geonames api requests.'),
    '#default_value' => variable_get('yild_geonames_username', 'demo'),
    '#disabled' => FALSE,
    '#size' => 100,
    '#maxlength' => 100,
  );

  $form['yild_geonames_settings']['yild_geonames_googlekey'] = array(
    '#type' => 'textfield',
    '#title' => t('Google Api key'),
    '#description' => t('The Google Api key to use for showing geolocations on a map (using a block defined by this module).'),
    '#default_value' => variable_get('yild_geonames_googlekey', ''),
    '#disabled' => FALSE,
    '#size' => 100,
    '#maxlength' => 100,
  );

  return system_settings_form($form);
}
