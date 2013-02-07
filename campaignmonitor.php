<?php

require_once 'campaignmonitor.civix.php';
require_once 'packages/createsend-php/csrest_subscribers.php';

/**
 * Implementation of hook_civicrm_post
 */
function campaignmonitor_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  
  // List all of the Object Names in use.
  $names = array(
    'Individual',
    'Household',
    'Organization',
  );
  
  // List all of the Operations in use.
  $ops = array(
    'create',
    'edit',
    'delete',
  );
  
  if ($objectName == 'GroupContact' && in_array($op, $ops)) {
    campaignmonitor_update_subscription_history();
  }
  elseif (in_array($op, $ops) && in_array($objectName, $names)) {
     campaignmonitor_update_contact($op, $objectRef);
  }
  
}

/**
 * Run through each new Subscription History Entry
 * and Update Campaign Monitor Accordingly.
 */
function campaignmonitor_update_subscription_history() {
  
  // Get the API Key
  $api_key = CRM_Core_BAO_Setting::getItem(
    'Campaign Monitor Preferences',
    'api_key',
    NULL,
    ''
  );
  
  // If the API Key or Client ID are empty
  // return now, for there is nothing else we can do.
  if (empty($api_key)) {
    return;
  }
  
  // Get the Groups
  $groups = CRM_Core_BAO_Setting::getItem(
    'Campaign Monitor Preferences',
    'groups',
    NULL,
    array()
  );
  
  // Get the Groups
  $group_map = CRM_Core_BAO_Setting::getItem(
    'Campaign Monitor Preferences',
    'group_map',
    NULL,
    array()
  );
  
  // Get the Last ID retrieved.
  $last_id = CRM_Core_BAO_Setting::getItem(
    'Campaign Monitor Preferences',
    'subscription_history',
    NULL,
    0
  );
  
  // Set this to the new last id.
  $new_last_id = $last_id;
  
  // Get the Subscription History since the last time we ran this.
  $history = new CRM_Contact_BAO_SubscriptionHistory();
  $history->whereAdd('id > '.$last_id);
  $history->find();
  
  // Loop through the history that is found.
  while ($history->fetch()) {
    
    // Update the Last ID regardless if we do anything.
    $new_last_id = $history->id;
    
    // If this is a Group that shouldn't be synced, then continue.
    if (empty($groups[$history->group_id])) {
      continue;
    }
    
    // If a mapping for this group doesn't exist, then continue.
    if (empty($group_map[$history->group_id])) {
      continue;
    }
    
    // Set the List ID.   
    $list_id = $group_map[$history->group_id];
    
    // Get the Contact
    $contact = new CRM_Contact_BAO_Contact();
    $contact->get('id', $history->contact_id);
    
    // If a Contact was not returned, continue.
    if (empty($contact->id)) {
      continue;
    }
    
    // Get the Contact's Primary Email.
    $email = new CRM_Core_BAO_Email();
    $email->whereAdd('contact_id = '.$contact->id);
    $email->whereAdd('is_primary = 1');
    $email->find(TRUE);
    
    // If the Contact does not have a primary email, continue.
    if (empty($email->email)) {
      continue;
    }
    
    // Setup the CS Subscribers.
    $subscribers = new CS_REST_Subscribers($list_id, $api_key);
    
    // If the Contact is being added, add them to the list.
    if ($history->status == 'Added') {
      $subscriber = array (
        'EmailAddress' => $email->email,
        'Name' => $contact->display_name,
        'Resubscribe' => $contact->do_not_email ? FALSE : TRUE,
        'RestartSubscriptionBasedAutoResponders' => FALSE,
      );
      $result = $subscribers->add($subscriber);
    }
    // If they are being removed, unsubscribe them.
    elseif ($history->status == 'Removed') {
      $result = $subscribers->unsubscribe($email->email);
    }
    // If they are being permenently deleted, delete them from the list.
    elseif ($history->status == 'Deleted') {
      $result = $subscribers->delete($email->email);
    }
            
  }
  
  // Update the Last ID, so we know where to start from.
  CRM_Core_BAO_Setting::setItem(
    $new_last_id,
    'Campaign Monitor Preferences',
    'subscription_history'
  );
  
}

/**
 * Update Campaign Monitor to Match the new Contact.
 */
function campaignmonitor_update_contact($op, $contact) {
  // @TODO: If the record has been "deleted" (thrown in the trash), it should be unsubscribed from every list that it's a part of. If it's been deleted permenently then it should be deleted from each list. If it's been restored from the trash, user should be re-subscribed to each list. If the user's contact information has changed, then the change should be refelected on each list.
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
