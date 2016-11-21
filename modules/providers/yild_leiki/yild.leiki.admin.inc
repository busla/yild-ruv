<?php

/**
 * @file
 * Yild configuration UI.
 */

/**
 * Implements hook_admin_settings_form().
 */
function yild_leiki_admin_settings_form($form, &$form_state) {
  $form = array();

  $form['yild_leiki_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Yild Leiki configuration'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['yild_leiki_settings']['yild_leiki_apiuser'] = array(
    '#type' => 'textfield',
    '#title' => t('Leiki API username'),
    '#description' => t('The username for authenticating against the Leiki Api.'),
    '#default_value' => variable_get('yild_leiki_apiuser', ''),
    '#disabled' => FALSE,
    '#size' => 100,
    '#maxlength' => 100,
  );

  $form['yild_leiki_settings']['yild_leiki_apipassword'] = array(
    '#type' => 'textfield',
    '#title' => t('Leiki API password'),
    '#description' => t('The password for authenticating against the Leiki Api.'),
    '#default_value' => variable_get('yild_leiki_apipassword', ''),
    '#disabled' => FALSE,
    '#size' => 100,
    '#maxlength' => 100,
  );

  $form['yild_leiki_settings']['yild_leiki_max_suggestions'] = array(
    '#type' => 'textfield',
    '#title' => t('Max amount of term suggestions'),
    '#description' => t('The maximum amount of suggestions (per provider) delivered from Leiki.'),
    '#default_value' => variable_get('yild_leiki_max_suggestions', '4'),
    '#disabled' => FALSE,
    '#size' => 10,
    '#maxlength' => 10,
  );

  $form['yild_leiki_settings']['yild_leiki_score_threshold'] = array(
    '#type' => 'textfield',
    '#title' => t('Leiki score threshold'),
    '#description' => t('The minimum score a Leiki suggestion must have to be considered viable.'),
    '#default_value' => variable_get('yild_leiki_score_threshold', '5'),
    '#disabled' => FALSE,
    '#size' => 10,
    '#maxlength' => 10,
  );

  $form['yild_leiki_settings']['yild_leiki_similarity_threshold'] = array(
    '#type' => 'textfield',
    '#title' => t('Leiki similarity threshold'),
    '#description' => t('How similar Leiki and other provider strings need to be in order to be accepted.'),
    '#default_value' => variable_get('yild_leiki_similarity_threshold', '85'),
    '#disabled' => FALSE,
    '#size' => 10,
    '#maxlength' => 10,
  );

  $form['yild_leiki_settings']['yild_leiki_analysis_bridge'] = array(
    '#type' => 'textfield',
    '#title' => t('Leiki analysis bridge'),
    '#description' => t('Comma separated list of Yild providers that Leiki terms will be matched against.'),
    '#default_value' => variable_get('yild_leiki_analysis_bridge', 'freebase,finto'),
    '#disabled' => FALSE,
    '#size' => 100,
    '#maxlength' => 100,
  );

  $form['yild_leiki_settings']['yild_leiki_debug'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show Leiki Debug Info'),
    '#description' => t('When enabled, debug information will be shown below the analysis button with a summary of how terms were matched.'),
    '#default_value' => variable_get('yild_leiki_debug', "0"),
  );

  return system_settings_form($form);
}
