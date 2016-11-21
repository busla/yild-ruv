<?php

/**
 * @file
 * Yild blocks are defined here.
 */

/**
 * Implements hook_block_info().
 */
function yild_block_info() {
  $blocks = array();
  $blocks['yild_gmap'] = array(
    'info' => t("Yild Google map integration."),
    'weight' => '10',
  );
  $blocks['yild_related'] = array(
    'info' => t("Yild related content list."),
    'weight' => '10',
  );
  return $blocks;
}

/**
 * Implements hook_block_view().
 */
function yild_block_view($delta = '') {
  $block = array();
  switch ($delta) {
    case 'yild_gmap':
      $block['content'] = _yild_get_gmap();
      $block['subject'] = t("Location on a map");
      break;

    case 'yild_related':
      $block['content'] = _yild_get_related();
      $block['subject'] = t("Related content");
      break;
  }
  return $block;
}

/**
 * Return markup for an embedded google map.
 */
function _yild_get_gmap() {
  $node = menu_get_object();
  if (!empty($node)) {
    if (!empty($node->nid)) {
      // Start by finding if this node is tagged with any kind of geonames.
      $data_field_name = variable_get('yild_data_field_name', 'yild_data');
      $field_info = field_info_instances('node', $node->type);
      $geofields = array();
      $key_candidates = array();
      // Loop through all fields for this node type and determine if they are
      // using the yild widget.
      foreach ($field_info as $field) {
        if (!empty($field['widget'])) {
          if ($field['widget']['type'] == 'yild_term_reference_autocomplete') {
            // Try to invoke this provider's module's geonames hook.
            // If successful, this field could provider geocoordinates.
            if (!empty($field['widget']['settings']['providers'])) {
              foreach ($field['widget']['settings']['providers'] as $provider) {
                if (module_invoke('yild_' . $provider, 'yild_geolocation')) {
                  $geofields[] = $field['field_name'];
                  // If module provides geolocation coordinates, it could also
                  // provide a google map api key.
                  $google_api_key = module_invoke('yild_' . $provider, 'yild_google_api_key');
                  if (!empty($google_api_key)) {
                    $key_candidates[] = $google_api_key;
                  }
                }
              }
            }
          }
        }
      }

      // We can't show a google map without at least one key.
      if (empty($key_candidates)) {
        return FALSE;
      }

      // If we find at least one geofield, we try to extract coordinates from
      // that field in the active node.
      $geocoords = array();

      foreach ($geofields as $gf) {
        if (!empty($node->{$gf}[LANGUAGE_NONE])) {
          foreach ($node->{$gf}[LANGUAGE_NONE] as $geoval) {
            if (!empty($geoval['taxonomy_term']->$data_field_name)) {
              $data = unserialize($geoval['taxonomy_term']->{$data_field_name}[LANGUAGE_NONE][0]['value']);
              if (!empty($data['geocode'])) {
                if (!empty($data['geocode']['latitude']) && !empty($data['geocode']['longitude'])) {
                  $geocoords[] = array(
                    'lat' => trim($data['geocode']['latitude']),
                    'lng' => trim($data['geocode']['longitude']),
                    'name' => $geoval['taxonomy_term']->name,
                  );
                }
              }
            }
          }
        }
      }

      // If we find at least one set of coordinates, we go ahead and map them
      // with a google map.
      if (count($geocoords) > 0) {
        return _yild_get_gmap_markup_from_coordinates($geocoords, 'yild_gmap_' . $node->nid, $key_candidates);
      }
    }
  }
  elseif (arg(1) == 'term' && is_numeric(arg(2))) {
    $term = taxonomy_term_load(arg(2));
    if (!empty($term)) {
      $geocoords = array();
      $data_field_name = variable_get('yild_data_field_name', 'yild_data');
      if (!empty($term->$data_field_name)) {
        $coordset = explode('x', $term->{$data_field_name}[LANGUAGE_NONE][0]['value']);
        if (count($coordset) == 2) {
          $geocoords[] = array(
            'lat' => $coordset[0],
            'lng' => $coordset[1],
            'name' => $term->name,
          );
        }
      }

      // If we find at least one set of coordinates, we go ahead and map them
      // with a google map.
      if (count($geocoords) > 0) {
        return _yild_get_gmap_markup_from_coordinates($geocoords, 'yild_gmap_term_' . $term->tid, $key_candidates);
      }
    }
  }
  return FALSE;
}

/**
 * Takes a set of coordinates and returns Google map markup for them.
 */
function _yild_get_gmap_markup_from_coordinates($coords, $yild_map_id, $key_candidates) {
  // Loop through coordinate sets for adding markers.
  $markers = array();
  $markercounter = 0;
  foreach ($coords as $c) {
    $markers[] = 'var marker' . ++$markercounter . ' = new google.maps.Marker({position: {lat: ' . $c['lat'] . ', lng: ' . $c['lng'] . '}, map: map, title: "' . $c['name'] . '"});';
  }

  $google_api_key = reset($key_candidates);
  drupal_add_css(drupal_get_path('module', 'yild') . '/assets/yild_gmap.css');
  drupal_add_js('https://maps.googleapis.com/maps/api/js' . (!empty($google_api_key) ? '?key=' . $google_api_key : ''), 'external');
  drupal_add_js("function initialize() {
    var mapOptions = { center: { lat: " . $coords[0]['lat'] . ", lng: " . $coords[0]['lng'] . "}, zoom: 3 };
    var map = new google.maps.Map(document.getElementById('" . $yild_map_id . "'), mapOptions);
    " . implode("\r\n", $markers) . "
  }
  google.maps.event.addDomListener(window, 'load', initialize);", 'inline');
  return '<div class="yild-gmap" id="' . $yild_map_id . '"></div>';
}

/**
 * Retrieves all related nodes of any content type for this node.
 */
function _yild_get_related() {
  // Fetch the active node.
  $node = menu_get_object();
  $result = array();
  if (!empty($node)) {
    if (!empty($node->nid)) {
      // Fetch all Yild fields in this node.
      $field_info = field_info_instances('node', $node->type);
      $yild_fields = array();
      foreach ($field_info as $field) {
        if (!empty($field['widget'])) {
          if ($field['widget']['type'] == 'yild_term_reference_autocomplete') {
            $yild_fields[] = $field['field_name'];
          }
        }
      }

      // Array of term id:s (tid) used in this node.
      $terms_on_this_node = array();

      // Loop through all Yild fields on this node.
      foreach ($yild_fields as $yild_field) {
        if (!empty($node->{$yild_field}[LANGUAGE_NONE])) {
          // Loop through all terms in this field.
          foreach ($node->{$yild_field}[LANGUAGE_NONE] as $term) {
            if (!in_array($term['tid'], $terms_on_this_node)) {
              // Save each term id (tid) to an array.
              $terms_on_this_node[] = $term['tid'];
            }
          }
        }
      }

      if (count($terms_on_this_node) > 0) {
        // Use the list of terms in use on this node to find the nodes
        // with the largest amount of terms in common.
        $query = 'SELECT ti.nid, n.title, n.created, count(*) AS amt FROM taxonomy_index ti INNER JOIN node n ON n.nid = ti.nid WHERE tid IN (:tidlist) AND ti.nid != :thisnid GROUP BY ti.nid HAVING amt >= :min_common_terms ORDER BY amt DESC, n.created DESC LIMIT ' . variable_get('yild_related_max_related', 10);
        $result = db_query($query, array(
          ':tidlist' => $terms_on_this_node,
          ':min_common_terms' => variable_get('yild_related_min_common_terms', 2),
          ':thisnid' => $node->nid,
        ));
      }
    }
  }
  return theme('yild_related', array('items' => $result));
}
