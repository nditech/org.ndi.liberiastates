<?php
/**
 * Provide config options for the extension.
 *
 * @return array
 *   Array of:
 *   - whether to remove existing states/provinces,
 *   - ISO abbreviation of the country,
 *   - list of states/provinces with abbreviation,
 *   - list of states/provinces to rename,
 */
function liberiastates_stateConfig() {
  $config = array(
    // CAUTION: only use `overwrite` on fresh databases.
    'overwrite' => TRUE,
    'countryIso' => 'LR',
    'states' => array(
      // 'state name' => 'abbreviation',
      'Abia State' => '1',
      'Adamawa State' => '2',
      'Akwa Ibom State' => '3',
      'Anambra State' => '4',
      'Bauchi State' => '5',
      'Bayelsa State' => '6',
      'Benue State' => '7',
      'Borno State' => '8',
      'Cross River State' => '9',
      'Delta State' => '10',
      'Ebonyi State' => '11',
      'Edo State' => '12',
      'Ekiti State' => '13',
      'Enugu State' => '14',
      'FCT' => '15',
      'Gombe State' => '16',
      'Imo State' => '17',
      'Jigawa State' => '18',
      'Kaduna State' => '19',
      'Kano State' => '20',
      'Katsina State' => '21',
      'Kebbi State' => '22',
      'Kogi State' => '23',
      'Kwara State' => '24',
      'Lagos State' => '25',
      'Nasarawa State' => '26',
      'Niger State' => '27',
      'Ogun State' => '28',
      'Ondo State' => '29',
      'Osun State' => '30',
      'Oyo State' => '31',
      'Plateau State' => '32',
      'Rivers State' => '33',
      'Sokoto State' => '34',
      'Taraba State' => '35',
      'Yobe State' => '36',
      'Zamfara State' => '37',
    ),
    'rewrites' => array(
      // List states to rewrite in the format:
      // 'Default State Name' => 'Corrected State Name',
    ),
  );
  return $config;
}
/**
 * Check and load states/provinces.
 *
 * @return bool
 *   Success true/false.
 */
function liberiastates_loadProvinces() {
  $stateConfig = liberiastates_stateConfig();
  if (empty($stateConfig['states']) || empty($stateConfig['countryIso'])) {
    return FALSE;
  }
  static $dao = NULL;
  if (!$dao) {
    $dao = new CRM_Core_DAO();
  }
  $statesToAdd = $stateConfig['states'];
  try {
    $countryId = civicrm_api3('Country', 'getvalue', array(
      'return' => 'id',
      'iso_code' => $stateConfig['countryIso'],
    ));
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::debug_log_message(ts('API Error: %1', array(
      'domain' => 'org.ndi.liberiastates',
      1 => $error,
    )));
    return FALSE;
  }
  // Rewrite states.
  if (!empty($stateConfig['rewrites'])) {
    foreach ($stateConfig['rewrites'] as $old => $new) {
      $sql = 'UPDATE civicrm_state_province SET name = %1 WHERE name = %2 and country_id = %3';
      $stateParams = array(
        1 => array(
          $new,
          'String',
        ),
        2 => array(
          $old,
          'String',
        ),
        3 => array(
          $countryId,
          'Integer',
        ),
      );
      CRM_Core_DAO::executeQuery($sql, $stateParams);
    }
  }
  // Find states that are already there.
  $stateIdsToKeep = array();
  foreach ($statesToAdd as $state => $abbr) {
    $sql = 'SELECT id FROM civicrm_state_province WHERE name = %1 AND country_id = %2 LIMIT 1';
    $stateParams = array(
      1 => array(
        $state,
        'String',
      ),
      2 => array(
        $countryId,
        'Integer',
      ),
    );
    $foundState = CRM_Core_DAO::singleValueQuery($sql, $stateParams);
    if ($foundState) {
      unset($statesToAdd[$state]);
      $stateIdsToKeep[] = $foundState;
      continue;
    }
  }
  // Wipe out states to remove.
  if (!empty($stateConfig['overwrite'])) {
    $sql = 'SELECT id FROM civicrm_state_province WHERE country_id = %1';
    $params = array(
      1 => array(
        $countryId,
        'Integer',
      ),
    );
    $dbStates = CRM_Core_DAO::executeQuery($sql, $params);
    $deleteIds = array();
    while ($dbStates->fetch()) {
      if (!in_array($dbStates->id, $stateIdsToKeep)) {
        $deleteIds[] = $dbStates->id;
      }
    }
    // Go delete the remaining old ones.
    foreach ($deleteIds as $id) {
      $sql = "DELETE FROM civicrm_state_province WHERE id = %1";
      $params = array(
        1 => array(
          $id,
          'Integer',
        ),
      );
      CRM_Core_DAO::executeQuery($sql, $params);
    }
  }
  // Add new states.
  $insert = array();
  foreach ($statesToAdd as $state => $abbr) {
    $stateE = $dao->escape($state);
    $abbrE = $dao->escape($abbr);
    $insert[] = "('$stateE', '$abbrE', $countryId)";
  }
  // Put it into queries of 50 states each.
  for ($i = 0; $i < count($insert); $i = $i + 50) {
    $inserts = array_slice($insert, $i, 50);
    $query = "INSERT INTO civicrm_state_province (name, abbreviation, country_id) VALUES ";
    $query .= implode(', ', $inserts);
    CRM_Core_DAO::executeQuery($query);
  }
  return TRUE;
}
/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function liberiastates_civicrm_install() {
  liberiastates_loadProvinces();
}
/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function liberiastates_civicrm_enable() {
  liberiastates_loadProvinces();
}
/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function liberiastates_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  liberiastates_loadProvinces();
}
