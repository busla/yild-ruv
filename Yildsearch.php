<?php

/**
 * @file
 * Helper class for a Yild search from various providers.
 */

/**
 * Helper class for doing an autocomplete search across many providers in Yild.
 */
class Yildsearch {
  private $resultSet = array();
  public $providers;

  /**
   * Constructor for the class.
   *
   * @param array $providers
   *   An array of provider names.
   */
  public function __construct($providers) {
    $this->providers = $providers;
  }

  /**
   * Adds the results from one provider to the global result set.
   *
   * @param array $results
   *   The result set from one provider.
   */
  public function addResults(array $results) {
    if (is_array($results)) {
      foreach ($results as $id => $result) {
        $item = new Yilditem($id, $result);
        $this->resultSet[] = $item;
      }
    }
  }

  /**
   * Parses the global result set according to usage frequency / other criteria.
   *
   * @return Returns
   *   An array of Drupal compatible autocomplete items.
   */
  public function parseResults() {
    // We sort by custom order and frequency of usage.
    $frequencies = [];
    $order = [];
    foreach ($this->resultSet as $item) {
      $item_frequency = $item->getFrequency();
      $frequencies[] = $item_frequency;
      $order[] = !empty($item->order) ? $item->order : 0;
    }
    array_multisort($order, SORT_ASC, $frequencies, SORT_DESC, $this->resultSet);
    $ret = [];

    foreach ($this->resultSet as $item) {
      $ret[check_plain($item->combinedId) . '|' . check_plain($item->name) . '|' . check_plain(implode('+', $item->getDisambiguators())) . '|' . check_plain($item->getData())] = $item->getHtmlDescription();
    }
    return $ret;
  }

}
