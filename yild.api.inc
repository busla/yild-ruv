<?php

/**
 * @file
 * Api related callback functions for Yild.
 */

/**
 * Retrieve a list of all content by yild external id.
 *
 * @param string $external_id
 *    The unique external Yild id for a term.
 * @param bool $show_body
 *   Whether to show the body of the articles found.
 *
 * @return array
 *   An array containing a list of articles for the given Yild id.
 */
function _yild_list_content_by_extid($external_id, $show_body = TRUE, $user_hash = NULL) {
  $access = _yild_check_access($external_id, $user_hash);
  if ($access === TRUE) {
    $id_field_name = variable_get('yild_id_field_name', 'yild_ext_id');
    $query = 'SELECT * FROM  { field_data_' . $id_field_name . ' } where ' . $id_field_name . '_value = :external_id';
    $params = array(':external_id' => $external_id);
    $result = db_query($query, $params);
    $obj = $result->fetchObject();
    if (!empty($obj)) {
      return _yild_list_content($obj->external_id, $show_body);
    }
  }
  else {
    return array('error' => $access);
  }
}

/**
 * Retrieve a list of all content by taxonomy term id.
 *
 * @param int $term_tid
 *    The Drupal taxonomy term tid.
 * @param bool $show_body
 *    Whether to retrieve body contents or just titles.
 *
 * @return array
 *    The content found.
 */
function _yild_list_content($term_tid, $show_body = FALSE) {
  // First check if this is ok.
  if (!variable_get('yild_expose_endpoint', "1")) {
    return array();
  }

  $id_field_name = variable_get('yild_id_field_name', 'yild_ext_id');

  $ret = array();
  $term = taxonomy_term_load($term_tid);
  if (!empty($term)) {
    $nodes = array();
    $node_ids = taxonomy_select_nodes($term_tid);
    $nodes = node_load_multiple($node_ids);
    $path = taxonomy_term_uri($term);
    $ret['term_name'] = $term->name;
    if (!empty($term->{$id_field_name}[LANGUAGE_NONE])) {
      $ret['external_id'] = $term->{$id_field_name}[LANGUAGE_NONE][0]['value'];
    }
    $ret['taxonomy_path'] = !empty($path['path']) ? url($path['path'], array('absolute' => TRUE)) : '';
    $ret['article_count'] = count($nodes);
    $ret['subjectOf'] = array();
    foreach ($nodes as $n) {
      if (!empty($n->body)) {
        if (!empty($n->body[LANGUAGE_NONE][0]['summary'])) {
          $body = $n->body[LANGUAGE_NONE][0]['summary'];
        }
        elseif (!empty($n->body[LANGUAGE_NONE][0]['value'])) {
          $body = $n->body[LANGUAGE_NONE][0]['value'];
        }
      }
      $uri = url('node/' . $n->nid, array('absolute' => TRUE));
      $ret['subjectOf'][] = $show_body ? array(
        'title' => $n->title,
        'body' => $body,
        'uri' => $uri,
      ) : array('title' => $n->title, 'uri' => $uri);
    }
  }

  return $ret;
}

/**
 * Lists all content from a specific provider in json format.
 *
 * @param string $provider
 *   The (machine)name of the provider.
 * @param string $user_hash
 *   The requester supplied hash for unlocking a protected API endpoint.
 */
function _yild_list_provider_terms($provider, $user_hash = NULL) {
  $access = _yild_check_access($provider, $user_hash);
  if ($access === TRUE) {
    $timestamp_start = microtime(TRUE);
    $provider_field_name = variable_get('yild_provider_field_name', 'yild_provider');
    $id_field_name = variable_get('yild_id_field_name', 'yild_ext_id');
    $id_value_col = $id_field_name . '_value';

    $field_name = 'field_data_' . $provider_field_name;

    $query = 'SELECT ttd.tid, fdyei.' . $id_value_col . ' FROM taxonomy_term_data ttd INNER JOIN { ' . $field_name . ' } fdyp ON fdyp.entity_id = ttd.tid INNER JOIN { field_data_' . $id_field_name . ' fdyei } ON fdyei.entity_id = ttd.tid where ' . $provider_field_name . '_value = :provider';
    $params = array(':provider' => $provider);

    $terms = db_query($query, $params);
    $term_list = array();
    foreach ($terms as $t) {
      $term_list[] = _yild_list_content($t->tid);
    }

    $processing_time = microtime(TRUE) - $timestamp_start;
    $processing_rate = count($term_list) / $processing_time;

    $return_list = array(
      'processing_time' => round($processing_time, 2) . ' sec',
      'processing_rate' => round($processing_rate, 2) . ' terms/sec',
      'provider' => $provider,
      'term_count' => count($term_list),
      'terms' => $term_list,
    );

    return $return_list;
  }
  else {
    return array('error' => $access);
  }
}

/**
 * Checks API access by comparing hash/salt and ip-address lists.
 */
function _yild_check_access($parameter, $user_hash = NULL) {
  // Check if API is enabled.
  if (!variable_get('yild_expose_endpoint', "1")) {
    return t('API endpoint not enabled');
  }

  // Check hash.
  if (variable_get('yild_api_password_salt', '')) {
    $salt = variable_get('yild_api_password_salt', '');
    $our_hash = md5($parameter . $salt);
    if (strtolower($our_hash) == strtolower($user_hash)) {
      return TRUE;
    }
    return t('Hash @hash is incorrect', array('@hash' => strtolower($user_hash)));
  }

  // Then check IP.
  if (variable_get('yild_api_ip_list', '')) {
    $ip_list = explode(',', variable_get('yild_api_ip_list', ''));
    $user_ip = ip_address();
    foreach ($ip_list as $ip) {
      if (trim($ip) == $user_ip) {
        return TRUE;
      }
    }
    return t('Your ip !user_ip is not on the list.', array('!user_ip' => $user_ip));
  }
  return TRUE;
}
