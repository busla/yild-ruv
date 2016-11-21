<?php

/**
 * @file
 * Class representing one item searched from any Yild provider.
 */

/**
 * Class that defines one Yild item.
 */
class Yilditem {
  public static $yildFreebaseImageUrl = 'https://usercontent.googleapis.com/freebase/v1/image';

  /**
   * The Yild compatible id in the form provider:unique_id.
   *
   * @var string
   */
  public $combinedId;

  /**
   * The unique external id without the provider.
   *
   * @var string
   */
  public $id;

  /**
   * Alternative id.
   *
   * It's possible to tag with a secondary term at the same time as the main.
   * The unique external id of the alternate provider's corresponding item.
   *
   * @var string
   */
  public $altId;

  /**
   * Disambiguator.
   *
   * A disambiguator is any string that separates one term from another.
   * Such as Moon (Planetary object) and Moon (Movie).
   *
   * @var string
   */
  public $disambiguator = '';

  /**
   * Alternative disambiguator for so called dual terms.
   *
   * @var string
   */
  public $altDisambiguator = '';

  /**
   * Additional data to save about this item.
   *
   * @var array
   */
  public $data = array();

  /**
   * The service providing this item, such as Freebase.
   *
   * @var string
   */
  public $provider;

  /**
   * The providername for the alternate provider.
   *
   * @var string
   */
  public $altProvider;

  /**
   * Provider label.
   *
   * The provider label we want to show in case it's not the same as the actual.
   *
   * @var string
   */
  public $providerLabel;

  /**
   * The official name of an item, such as "Monty Python".
   *
   * @var string
   */
  public $name;

  /**
   * A longer description of the term.
   *
   * @var string
   */
  public $description = '';

  /**
   * Usage frequency.
   *
   * How many times this particular term has been used in Drupal before.
   * Used in autocomplete results.
   *
   * @var int
   */
  public $frequency;

  /**
   * Order for autocomplete results.
   *
   * A custom integer order variable that determines primary sorting.
   * Only used for autocomplete results.
   *
   * @var int
   */
  public $order = 0;

  /**
   * Constructor for a Yild item.
   *
   * Will try to parse the necessary components and place them into variables.
   *
   * @param string $yildterm
   *   A Yild compatible id string containing provider:unique_id.
   *   OR a Term name for parsing.
   * @param array $item
   *   The item to initialize the object with.
   */
  public function __construct($yildterm, array $item = NULL) {
    // We have three ways to initialize this object.
    // The data can come from a | (pipe) separated querystring (during save).
    // The data can be a normal Drupal taxonomy term.
    // The data can be encoded as an id + array (during autocomplete).
    // Object instansified with a Drupal term object.
    if (is_object($yildterm)) {
      if (stristr($yildterm->name, '|')) {
        $this->initWithEncodedTerm($yildterm->name);
      }
      else {
        $this->initWithDrupalTerm($yildterm);
      }
    }

    elseif (stristr($yildterm, '|')) {
      $this->initWithEncodedTerm($yildterm);
    }

    // Object instansified with yild id + termdata from autocomplete.
    elseif (!empty($yildterm) && !empty($item)) {
      $this->initWithId($yildterm, $item);
    }
  }

  /**
   * Constructor with Drupal term object.
   *
   * @param object $term
   *    Drupal term object.
   */
  public function initWithDrupalTerm($term) {
    $this->name = $term->name;
    $this->disambiguator = $term->yild_disambiguator[LANGUAGE_NONE][0]['value'];
    $this->id = $term->yild_ext_id[LANGUAGE_NONE][0]['value'];
    $this->provider = $term->yild_provider[LANGUAGE_NONE][0]['value'];
    $this->disambiguator = $term->yild_disambiguator[LANGUAGE_NONE][0]['value'];
    $this->combinedId = $this->provider . ':' . $this->id;
    $this->data = unserialize($term->yild_data[LANGUAGE_NONE][0]['value']);
  }

  /**
   * Constructor with term object.
   *
   * @param object $term_name
   *   The Term object as sent when chosen with the Yild autocomplete widget.
   *
   * @return bool
   *   True on success. False otherwise.
   */
  private function initWithEncodedTerm($term_name) {
    if (!empty($term_name)) {
      $parts = explode('|', $term_name);
      // A Yild term has three to five components separated by pipes.
      // id|name|disambiguator|data|description.
      if (count($parts) >= 3 && count($parts) <= 5) {
        $this->combinedId = trim($parts[0]);
        $id_parts = $this->getProviderAndId($this->combinedId);
        if (!empty($id_parts['id']) && !empty($id_parts['provider'])) {
          $this->id = $id_parts['id'];
          $this->provider = $id_parts['provider'];
          if (!empty($id_parts['altId'])) {
            $this->altId = $id_parts['altId'];
          }
          if (!empty($id_parts['altProvider'])) {
            $this->altProvider = $id_parts['altProvider'];
          }
          $this->name = trim($parts[1]);
          if (!empty($parts[2])) {
            if (stristr($parts[2], '+')) {
              $disambiguator_parts = explode('+', $parts[2]);
              $this->disambiguator = trim($disambiguator_parts[0]);
              $this->altDisambiguator = trim($disambiguator_parts[1]);
            }
            else {
              $this->disambiguator = trim($parts[2]);
            }
          }
          if (!empty($parts[3])) {
            $this->data = $this->parseData(trim($parts[3]));
          }
          if (!empty($parts[4])) {
            $this->description = trim($parts[4]);
          }
          // Fix encoded double quotes.
          foreach (['name', 'disambiguator', 'description'] as $field) {
            $this->$field = str_replace('&quot;', '"', $this->$field);
          }
          return TRUE;
        }
        else {
          return FALSE;
        }
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Constructor with id and Item array.
   *
   * @param string $id
   *   The Yild id as a string.
   * @param array $item
   *   Array containing the name, disambiguator and data for this object.
   */
  private function initWithId($id, $item) {
    $this->combinedId = $id;
    $id_parts = $this->getProviderAndId(trim($id));
    if (!empty($id_parts['id']) && !empty($id_parts['provider'])) {
      $this->id = $id_parts['id'];
      $this->provider = $id_parts['provider'];
      if (!empty($id_parts['altId'])) {
        $this->altId = $id_parts['altId'];
      }
      if (!empty($id_parts['altProvider'])) {
        $this->altProvider = $id_parts['altProvider'];
      }
      $this->name = $item['name'];
      if (!empty($item['disambiguator'])) {
        if (stristr($item['disambiguator'], '+')) {
          $disambiguator_parts = explode('+', $item['disambiguator']);
          $this->disambiguator = $disambiguator_parts[0];
          $this->altDisambiguator = $disambiguator_parts[1];
        }
        else {
          $this->disambiguator = $item['disambiguator'];
        }
      }
      if (!empty($item['frequency'])) {
        $this->frequency = $item['frequency'];
      }
      if (!empty($item['data'])) {
        $this->data = is_array($item['data']) ? $item['data'] : $this->parseData($item['data']);
      }
      if (!empty($item['description'])) {
        $this->description = $item['description'];
      }
      if (!empty($item['providerlabel'])) {
        $this->providerLabel = $item['providerlabel'];
      }
      if (!empty($item['order'])) {
        $this->order = $item['order'];
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Determines if this is a real Yild term or just an impostor.
   *
   * @return bool
   *   Returns True|False depending on whether this object has been determined
   *   to be a valid Yild term with id, name and provider.
   */
  public function isYildTerm() {
    if (!empty($this->id) && !empty($this->provider) && !empty($this->name) && $this->getVocabulary()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Gets the vocabulary for this Yild term.
   *
   * @return Object
   *   The Vocabulary Object from Drupal.
   */
  public function getVocabulary() {
    $machine_name = variable_get('yild_vocabulary', 'yild_tags');
    return taxonomy_vocabulary_machine_name_load($machine_name);
  }

  /**
   * Retrieves the Drupal Term corresponding to this yild item.
   *
   * @return int
   *   Returns the TID of the Drupal Term object if one is found in the db.
   */
  public function getDrupalTermTid() {
    if (!empty($this->id)) {
      $id_field_name = variable_get('yild_id_field_name', 'yild_ext_id');

      // Check if a term with the exact same id already exists.
      $query = 'SELECT * FROM {field_data_' . $id_field_name . '} WHERE ' . $id_field_name . '_value = :id';
      $params = array(':id' => $this->id);
      $result = db_query($query, $params);
      $result_object = $result->fetchObject();
      return !empty($result_object) ? $result_object->entity_id : FALSE;
    }
    return FALSE;
  }

  /**
   * Retrieves the whole Drupal object matching this external id.
   *
   * @return object|bool
   *    The loaded Drupal term object identified by external id. False on not
   *    found.
   */
  public function getDrupalTerm() {
    if ($tid = $this->getDrupalTermTid()) {
      return taxonomy_term_load($tid);
    }
    return FALSE;
  }

  /**
   * Populates a Drupal Term object with the data in this object.
   *
   * If this Yild item contains the necessary data for population, the
   * provided term object is overwritten with that data.
   *
   * @param object $term
   *   The Drupal Term object to be repopulated with this object.
   * @param bool $overwrite
   *   Whether to overwrite an existing term.
   *
   * @return object
   *   The repopulated Drupal Term object.
   */
  public function populateDrupalTermFields($term = NULL, $overwrite = TRUE) {
    if (empty($term)) {
      $term = new stdClass();
    }

    // Populate the term object if what we have in this object is a real Yild
    // item.
    if ($this->isYildTerm()) {
      // Fetch the existing Drupal term with this external id.
      if ($existing_term = $this->getDrupalTerm()) {
        $existing_term_tid = $existing_term->tid;
      }

      // Fetch additional data for this id if the provider supports it.
      $additional_data = module_invoke('yild_' . $this->provider, 'additional_data', $this->id, variable_get('yild_result_language', 'en'));
      if (!empty($additional_data['description']) && empty($this->description)) {
        $this->description = $additional_data['description'];
      }
      if (!empty($additional_data['data'])) {
        $this->data = $additional_data['data'];
      }

      if (!empty($existing_term_tid)) {
        if ($overwrite) {
          $term->tid = $existing_term_tid;
        }
        else {
          return taxonomy_term_load($existing_term_tid);
        }
      }

      // Define the field names.
      $provider_field_name = variable_get('yild_provider_field_name', 'yild_provider');
      $id_field_name = variable_get('yild_id_field_name', 'yild_ext_id');
      $disambiguator_field_name = variable_get('yild_disambiguator_field_name', 'yild_disambiguator');
      $data_field_name = variable_get('yild_data_field_name', 'yild_data');

      // Term names are natively restricted to 255 chars in Drupal.
      $term->name = substr($this->name, 0, 255);
      if (!empty($this->description) && empty($term->description)) {
        $term->description = $this->description;
      }
      $term->$provider_field_name = array(LANGUAGE_NONE => array(array('value' => $this->provider)));
      $term->$id_field_name = array(LANGUAGE_NONE => array(array('value' => $this->id)));
      $term->$disambiguator_field_name = array(LANGUAGE_NONE => array(array('value' => $this->disambiguator)));
      if (!empty($this->data)) {
        $term->$data_field_name = array(LANGUAGE_NONE => array(array('value' => serialize($this->data))));
      }
      $term->parent = 0;

      if (empty($term->vid)) {
        $vocabulary = $this->getVocabulary();
        if (!empty($vocabulary)) {
          $term->vid = $vocabulary->vid;
        }
      }
      // If overwrite is enabled, we resave the taxonomy term with the new data.
      // We do this only if name, description, disambiguator or data have
      // changed.
      if ($overwrite && !empty($existing_term)) {
        $existing_name = $existing_term->name;
        $existing_description = !empty($existing_term->description) ? $existing_term->description : '';
        $existing_disambiguator = !empty($existing_term->$disambiguator_field_name) ? $existing_term->{$disambiguator_field_name}[LANGUAGE_NONE][0]['value'] : '';
        $existing_data = !empty($existing_term->$data_field_name) ? $existing_term->{$data_field_name}[LANGUAGE_NONE][0]['value'] : '';
        $term_description = !empty($term->description) ? $term->description : '';

        if (
          $existing_name != $term->name ||
          ($existing_description != $term_description && !empty($term_description)) ||
          ($existing_disambiguator != $this->disambiguator && !empty($this->disambiguator)) ||
          ($existing_data != serialize($this->data) && !empty($this->data))
        ) {
          taxonomy_term_save($term);
        }
      }
      return $term;
    }
    else {
      return $term;
    }
  }

  /**
   * Returns a Drupal compatible term array instead of an object.
   *
   * @param object $term
   *   The Drupal Term object to be repopulated.
   * @param bool $overwrite
   *   Whether to overwrite an existing term.
   *
   * @return object
   *   The repopulated Drupal Term object.
   */
  public function getDrupalTermArray($term = NULL, $overwrite = TRUE) {
    $drupal_term = $this->populateDrupalTermFields($term, $overwrite);
    $term_array = [];
    $fields_to_convert = [
      'name',
      'description',
      'vid',
      'tid',
      variable_get('yild_id_field_name', 'yild_ext_id'),
      variable_get('yild_provider_field_name', 'yild_provider'),
      variable_get('yild_disambiguator_field_name', 'yild_disambiguator'),
      variable_get('yild_data_field_name', 'yild_data'),
    ];
    foreach ($fields_to_convert as $field_name) {
      if (!empty($drupal_term->{$field_name})) {
        $term_array[$field_name] = $drupal_term->{$field_name};
      }
    }
    return $term_array;
  }

  /**
   * Gets a html formatted description for Drupal autocomplete lists.
   *
   * The different components are placed into html <span> -elements.
   *
   * @return string
   *   A string suitable for display in a Drupal autocomplete list.
   */
  public function getHtmlDescription() {
    if (!empty($this->provider) && !empty($this->name) && !empty($this->id)) {
      $provider_label = check_plain($this->providerLabel ? $this->providerLabel : $this->provider . ($this->altProvider ? '+' . $this->altProvider : ''));
      $parts = array();
      $parts[] = '<span class="yild_autocomplete_frequency">' . $this->getFrequency() . '</span>';
      $parts[] = '<span class="yild_autocomplete_provider">' . $provider_label . '</span>';
      $parts[] = '<span class="yild_autocomplete_name">' . check_plain($this->name) . '</span>';
      if (!empty($this->disambiguator)) {
        $parts[] = ' <span class="yild_autocomplete_disambiguator">(' . check_plain($this->disambiguator) . ')</span>';
      }
      return '<div class="yild_autocomplete_item ' . drupal_html_class('order-' . $this->order) . ' ' . drupal_html_class($provider_label) . '">' . (!empty($this->description) ? '<div class="description"><h2>' . check_plain($this->name) . '</h2><h3>' . check_plain($this->disambiguator) . '</h3>' . $this->getCleanDescription() . '</div>' : '') . '<div class="yild_text_container">' . implode('', $parts) . '<div class="yild_clearer"></div></div></div>';
    }
    else {
      return FALSE;
    }
  }

  /**
   * Cleans the description for the autocomplete additional popup box.
   */
  public function getCleanDescription() {
    $ret = array();
    $parts = explode("\n", check_plain($this->description));
    foreach ($parts as $p) {
      $ret[] = '<p>' . trim($p) . '</p>';
    }
    $image = $this->getImage();
    if (!empty($image)) {
      array_unshift($ret, '<img src="' . $image . '" ALT="' . check_plain($this->name) . '" class="description_image" />');
    }
    return implode("\n", $ret);
  }

  /**
   * Returns a possible image url from the data field.
   */
  public function getImage() {
    if (!empty($this->data['image'])) {
      if ($this->provider == 'freebase') {
        return self::$yildFreebaseImageUrl . check_plain($this->data['image']) . '?maxwidth=150&maxheight=150&mode=fillcropmid' . (variable_get('yild_freebase_googlekey') ? '&key=' . variable_get('yild_freebase_googlekey') : '');
      }
      return $this->data['image'];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns the disambiguator(s) separated by a plus.
   */
  public function getDisambiguators() {
    $ret = array();
    if (!empty($this->disambiguator)) {
      $ret['disambiguator'] = $this->disambiguator;
    }
    if (!empty($this->altDisambiguator)) {
      $ret['altDisambiguator'] = $this->altDisambiguator;
    }
    return $ret;
  }

  /**
   * Reformats a data string into an array.
   *
   * @param string $data_string
   *   The encoded dataset from the Yild autocomplete selection.
   *
   * @return array
   *   The various data types separated into an array.
   */
  public function parseData($data_string) {
    $data = explode(';', $data_string);
    $ret = array();
    foreach ($data as $d) {
      $parts = explode(':', $d);
      if (count($parts) >= 2) {
        $param_name = trim(array_shift($parts));
        $param_data = trim(implode(':', $parts));
        // Handle geocoordinates.
        if ($param_name == 'geocode') {
          $coords = explode('x', $param_data);
          if (count($coords) == 2) {
            $ret[$param_name] = array('latitude' => $coords[0], 'longitude' => $coords[1]);
          }
        }
        elseif ($param_name == 'altId') {
          $this->altId = $param_name;
          $ret[$param_name] = $param_data;
        }
        elseif ($param_name == 'altProvider') {
          $this->altProvider = $param_name;
          $ret[$param_name] = $param_data;
        }
        else {
          $ret[$param_name] = $param_data;
        }
      }
    }
    return $ret;
  }

  /**
   * Returns data in string format for passing to autocomplete json output.
   */
  public function getData() {
    $ret = array();
    foreach ($this->data as $label => $data) {
      if ($label == 'geocode') {
        $data = implode('x', $data);
      }
      elseif (is_array($data)) {
        // This shouldn't happen at present.
        $data = implode(',', $data);
      }
      $ret[] = $label . ':' . $data;
    }
    return implode(';', $ret);
  }

  /**
   * Extracts id and provider from combined string.
   *
   * @param string $id
   *   The Yild compatible id for an item.
   *
   * @return array
   *   For example "freebase:/m/12345" would return
   *   provider => "freebase" and id => '/.12345'.
   */
  public function getProviderAndId($id) {
    if (stristr($id, ':')) {
      $parts = explode(':', $id);
      $provider = array_shift($parts);

      // Look for alternate providers:
      $providers = explode('+', $provider);

      $ids = explode('+', implode(':', $parts));

      return array(
        'id' => $ids[0],
        'provider' => trim($providers[0]),
        'altId' => !empty($ids[1]) ? $ids[1] : NULL,
        'altProvider' => !empty($providers[1]) ? trim($providers[1]) : NULL,
      );
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns the provider name for this item.
   */
  public function getProviderName() {
    if (!empty($this->provider)) {
      return $this->provider;
    }
    elseif (!empty($this->id)) {
      $id_parts = $this->getProviderAndId(trim($this->id));
      return !empty($id_parts['provider']) ? $id_parts['provider'] : NULL;
    }
    return FALSE;
  }

  /**
   * Returns the name of the provider module in Yild for this item provider.
   *
   * @return string
   *   The name of the module that provided this term.
   */
  public function getProviderModule() {
    $provider = $this->getProviderName();
    if (!empty($provider)) {
      $provider_module_name = 'yild_' . $provider;
      if (module_exists($provider_module_name)) {
        return $provider_module_name;
      }
    }
    return FALSE;
  }

  /**
   * Finds out how many times this specific term has been used before.
   *
   * @param string|array $ids
   *   The Yild compatible id for the term.
   *
   * @return int
   *   The amount of times something has been tagged with this term.
   */
  public function getFrequency($ids = NULL) {
    // Return previously fetched value if present.
    if (isset($this->frequency)) {
      return $this->frequency;
    }

    if (!is_array($ids)) {
      $ids = array($ids);
    }

    // Use the instance id:s if id-parameter is empty.
    if (count($ids) > 0) {
      $ids = array($this->id);
      if (!empty($this->altId)) {
        $ids[] = $this->altId;
      }
    }

    $instance_list = self::getInstanceList();

    $this->frequency = 0;

    $yild_vocabulary = variable_get('yild_vocabulary', 'yild_tags');
    $vocabulary = taxonomy_vocabulary_machine_name_load($yild_vocabulary);

    if (!empty($vocabulary)) {
      foreach ($ids as $id) {
        $term_tid = $this->getTidById($id);
        if (!empty($term_tid)) {
          // Loop through all reference fields to find if this term is
          // used by any of them.
          $total_freq = 0;
          reset($instance_list);
          foreach ($instance_list as $instance_field_name) {
            $yild_table = 'field_data_' . $instance_field_name;

            $freq_query = 'SELECT count(*) AS freqval FROM {' . $yild_table . '} WHERE ' . $instance_field_name . '_tid=:tid';
            $params = array(':tid' => $term_tid);
            $freq_result = db_query($freq_query, $params);
            $freq = $freq_result->fetchObject();
            if (!empty($freq->freqval)) {
              $total_freq += $freq->freqval;
            }
          }
          if ($total_freq > 0) {
            $this->frequency += $total_freq;
          }
        }
      }
      return $this->frequency;
    }
    return 0;
  }

  /**
   * Static function for getting all field instances using a Yild widget.
   *
   * @return array
   *    List of instances.
   */
  public static function getInstanceList() {
    // First query the db for all instances of type taxonomy_term_reference,
    // that are using our widget (yild_term_reference_autocomplete).
    $instance_query = 'SELECT fc.field_name FROM {field_config} fc INNER JOIN {field_config_instance} fci ON fci.field_name=fc.field_name WHERE fc.type = :fieldtype AND fci.data LIKE :widgetname AND fci.deleted = 0';
    $instance_params = array(':fieldtype' => 'taxonomy_term_reference', ':widgetname' => '%yild_term_reference_autocomplete%');
    $instances = db_query($instance_query, $instance_params);

    // Translate the instance result set into an array, since we need to iterate
    // over it multiple times and since we might get duplicates.
    $instance_list  = array();
    foreach ($instances as $instance) {
      if (!in_array($instance->field_name, $instance_list)) {
        $instance_list[] = $instance->field_name;
      }
    }

    return $instance_list;
  }

  /**
   * Checks if a term with a specific Yild id has already been saved to Drupal.
   *
   * @param string $id
   *   The Yild compatible id for the term we are looking for.
   *
   * @return int
   *   The tid (taxonomy id) of the term if found.
   */
  public function getTidById($id='4404911119') {
    $id_field_name = variable_get('yild_id_field_name', 'yild_ext_id');
    $term_query = 'SELECT * FROM {field_data_' . $id_field_name . '} WHERE ' . $id_field_name . '_value = :id';
    $params = array(':id' => $id);
    $term_result = db_query($term_query, $params);
    $term = $term_result->fetchObject();
    if (!empty($term->entity_id)) {
      return $term->entity_id;
    }
  }

  /**
   * Save to Yild vocabulary as a Drupal term.
   *
   * This checks whether the same term already exists and doesn't create a
   * duplicate.
   */
  public function saveNewYildTerm() {
    $new_term = new stdClass();
    $new_term = $this->populateDrupalTermFields($new_term, FALSE);
    if (!empty($new_term)) {
      if (empty($new_term->tid) && !empty($new_term->vid) && !empty($new_term->name)) {
        // Save to database if this is a new term that has no tid.
        taxonomy_term_save($new_term);
        $new_term->isNew = TRUE;
      }
      return $new_term;
    }
  }

  /**
   * Creates a link between a node and a yild term through a specific field.
   *
   * Essentially just "tags" a node with a specific term. This method would
   * probably be used mostly for migrations where you move lots of terms to
   * a Yild field.
   *
   * @param int $target_nid
   *   The nid of the node referencing the term.
   * @param int $yild_tid
   *   The tid of the Yild term we reference.
   * @param string $yild_field_instance_name
   *   The name of the field instance the term reference should be applied to.
   * @param string $content_type
   *   Content type (article, blog post etc) is needed for the bundle.
   *
   * @return bool
   *   TRUE on success, FALSE on fail.
   */
  public function linkYildTermToField($target_nid, $yild_tid, $yild_field_instance_name, $content_type) {
    // Start by checking the link doesn't already exist.
    $query = 'SELECT * FROM {field_data_' . $yild_field_instance_name . '} WHERE entity_id = :target_nid ORDER BY delta';
    $check = db_query($query, array(':target_nid' => $target_nid));
    $tid_col = $yild_field_instance_name . '_tid';
    $high_delta = -1;
    foreach ($check as $c) {
      if ($c->$tid_col == $yild_tid) {
        // This term is already linked to this node.
        return FALSE;
      }
      $high_delta = $c->delta;
    }

    // Fetch the field.
    $node = node_load($target_nid);
    if (!empty($node)) {
      db_insert('field_data_' . $yild_field_instance_name)
        ->fields(array(
          'entity_type' => 'node',
          'bundle' => $content_type,
          'deleted' => 0,
          'entity_id' => $target_nid,
          'revision_id' => $node->vid,
          'language' => LANGUAGE_NONE,
          'delta' => $high_delta + 1,
          $tid_col => $yild_tid,
        ))
        ->execute();
    }
    return TRUE;
  }

  /**
   * Checks and creates taxonomy_index as needed.
   *
   * @param int $target_nid
   *   The nid of the node referencing the term.
   * @param int $yild_tid
   *   The tid of the Yild term we reference.
   */
  public function updateTaxonomyIndex($target_nid, $yild_tid) {
    // Start by checking the link doesn't already exist.
    $query = 'SELECT * FROM {taxonomy_index} WHERE nid = :target_nid AND tid = :yild_tid';
    $check = db_query($query, array(':target_nid' => $target_nid, ':yild_tid' => $yild_tid));
    if ($check->rowCount() == 0) {
      // No index found, let's index it.
      db_insert('taxonomy_index')
        ->fields(array(
          'nid' => $target_nid,
          'tid' => $yild_tid,
          'sticky' => 0,
          'created' => time(),
        ))
        ->execute();
    }
  }

}
