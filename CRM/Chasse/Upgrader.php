<?php
use CRM_Chasse_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Chasse_Upgrader extends CRM_Chasse_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Create the custom field to store journey code.
   */
  public function install() {

    // Ensure we have the custom field group we need for contributions.
    $chasse_custom_group = $this->api_get_or_create('CustomGroup', [
        'name' => "chasse",
        'extends' => "Contact",
      ],
      ['title' => 'Chassé: Supporter Journey']);

    // Add the step field
    $journey_step = $this->api_get_or_create('CustomField', [
      'name'            => "chasse_step",
      'custom_group_id' => $chasse_custom_group['id'],
      'data_type'       => "String",
      'html_type'       => 'Text',
      'is_required'     => "0",
      'is_searchable'   => "1",
      'default_value'   => "",
      'text_length'     => "20",
    ],
    ['label' => 'Current Step']);

    // Add the not_before field
    $journey_step = $this->api_get_or_create('CustomField', [
      'name'            => "chasse_not_before",
      'custom_group_id' => $chasse_custom_group['id'],
    ],
    [
      'label'            => 'Do not process before date',
      'column_name'      => "not_before",
      'data_type'        => "Date",
      'html_type'        => 'Select Date',
      'is_required'      => "0",
      'is_searchable'    => "1",
      "is_search_range"  => "1",
      'default_value'    => "",
      'start_date_years' => 0,
      'end_date_years'   => 0,
      'date_format'      => 'd M yy',
      'time_format'      => '2',
    ]);
  }


  /**
   * Helper function for creating data structures.
   *
   * @param string $entity - name of the API entity.
   * @param Array $params_min parameters to use for search.
   * @param Array $params_extra these plus $params_min are used if a create call
   *              is needed.
   */
  protected function api_get_or_create($entity, $params_min, $params_extra) {
    $params_min += ['sequential' => 1];
    $result = civicrm_api3($entity, 'get', $params_min);
    if (!$result['count']) {
      // Couldn't find it, create it now.
      $result = civicrm_api3($entity, 'create', $params_extra + $params_min);
    }
    return $result['values'][0];
  }
  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   *
  public function postInstall() {
    $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
      'return' => array("id"),
      'name' => "customFieldCreatedViaManagedHook",
    ));
    civicrm_api3('Setting', 'create', array(
      'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
    ));
  }

  /**
   * Remove the custom field set.
   */
  public function uninstall() {
    $chasse_custom_group_id = (int) civicrm_api3('CustomGroup', 'getvalue', [
        'name' => "chasse",
        'return' => 'id']);

    if ($chasse_custom_group_id > 0) {
      civicrm_api3('CustomGroup', 'delete', ['id' => $chasse_custom_group_id]);
    }
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = E::ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */


  /**
   * Upgrading from v1 to v2 we need to:
   *
   * - Add the not_before custom field to the chasse custom field group.
   * - Update the journey configuration JSON
   */
  public function upgrade_0001() {
    $chasse_custom_group_id = civicrm_api3('CustomGroup', 'getvalue', [
        'name' => "chasse",
        'return' => 'id']);

    // Add the not_before field
    $journey_step = $this->api_get_or_create('CustomField', [
      'name'             => "chasse_not_before",
      'custom_group_id'  => $chasse_custom_group_id,
    ],
    [
      'label'            => 'Do not process before date',
      'column_name'      => "not_before",
      'data_type'        => "Date",
      'html_type'        => 'Select Date',
      'is_required'      => "0",
      'is_searchable'    => "1",
      "is_search_range"  => "1",
      'default_value'    => "",
      'start_date_years' => 0,
      'end_date_years'   => 0,
      'date_format'      => 'd M yy',
      'time_format'      => '2',
    ]);

    // Upgrade the configuration.
    $journeys = Civi::settings()->get('chasse_config');
    if (!$journeys) {
      $journeys = [];
    }

    // check we've not already upgraded.
    if (!isset($journeys['next_id'])) {

      $new_journeys_array = [];
      foreach ($journeys['journeys'] as $i => $journey) {
        // Originally journeys were stored as an array and referenced by their index.
        // This could cause problems, e.g. if one is deleted then an automated job could
        // now be triggering the wrong journey. So give each job an ID like journeyNNN and
        // make sure that can't change. This is how journeys should be referenced in future.
        $id = "journey$i";
        $journey['id'] = $id;

        // 3. Originally all journeys were manually processed, now we have scheduled too.
        $journey['processing'] = 'manual';

        $new_journeys_array[$id] = $journey;
      }

      // Move the main journeys down in to a sub-array if this has not been done yet.
      $journeys = ['journeys' => $new_journeys_array, 'next_id' => count($new_journeys_array)];

      Civi::settings()->set('chasse_config', $journeys);
    }

    return TRUE;
  }
}
