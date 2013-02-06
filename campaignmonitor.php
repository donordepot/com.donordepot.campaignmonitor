<?php

require_once 'campaignmonitor.civix.php';

/**
 * Implementation of hook_civicrm_post
 */
function campaignmonitor_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  
  // List all of the Object Names in use.
  $names = array(
    'Individual',
    'Household',
    'Organization',
    'GroupContact',
  );
  
  // If the objectName passed is not
  // being used, return.
  if (!in_array($objectName, $names)) {
    return;
  }
  
  // List all of the Operations in use.
  $ops = array(
    'create',
    'edit',
    'delete',
  );
  
  // If the operation passed is not being used, return.
  if (!in_array($op, $ops)) {
    return;
  }
  
  if ($objectName == 'GroupContact') {
    // @TODO: Query for the civicrm_subscription_history since the "last_id" (stored as a variable), and execute the 'status' of each one since the last time something was saved. This will occur on all of the create/edit/delete actions.
  }
  else {
     // @TODO: If the record has been "deleted" (thrown in the trash), it should be unsubscribed from every list that it's a part of. If it's been deleted permenently then it should be deleted from each list. If it's been restored from the trash, user should be re-subscribed to each list. If the user's contact information has changed, then the change should be refelected on each list.
    if ($op == 'edit') {
      
    }
    elseif ($op == 'delete') {
      
    }
  }
  
  
    
}

/**
 * Implementation of hook_civicrm_config
 */
function campaignmonitor_civicrm_config(&$config) {
  _campaignmonitor_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function campaignmonitor_civicrm_xmlMenu(&$files) {
  _campaignmonitor_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function campaignmonitor_civicrm_install() {
  return _campaignmonitor_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function campaignmonitor_civicrm_uninstall() {
  return _campaignmonitor_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function campaignmonitor_civicrm_enable() {
  return _campaignmonitor_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function campaignmonitor_civicrm_disable() {
  return _campaignmonitor_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function campaignmonitor_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _campaignmonitor_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function campaignmonitor_civicrm_managed(&$entities) {
  return _campaignmonitor_civix_civicrm_managed($entities);
}
