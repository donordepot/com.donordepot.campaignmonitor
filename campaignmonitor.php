<?php

require_once 'campaignmonitor.civix.php';
require_once 'packages/createsend-php/csrest_subscribers.php';
require_once 'packages/createsend-php/csrest_lists.php';

/**
 * Implementation of hook_civicrm_pre
 */
function campaignmonitor_civicrm_pre($op, $objectName, $id, &$params) {
  
  // List all of the Object Names in use.
  $names = array(
    'Individual',
    'Household',
    'Organization',
  );
  
  // List all of the Operations in use.
  $ops = array(
    'create',
    'restore',
    'edit',
    'delete',
  );
  
  if (in_array($op, $ops) && in_array($objectName, $names)) {
     campaignmonitor_update_contact($op, $id, $params);
  }
  
}

/**
 * Implementation of hook_civicrm_post
 */
function campaignmonitor_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  
  // List all of the Operations in use.
  $ops = array(
    'create',
    'edit',
    'delete',
  );
  
  if ($objectName == 'GroupContact' && in_array($op, $ops)) {
    campaignmonitor_update_subscription_history();
  }
  
  if ($objectName == 'Group' && $op == 'delete') {
    campaignmonitor_delete_group($objectRef);
  }
  
}

/**
 * Set Campaign Monitor Variable
 */
function campaignmonitor_variable_set($variable, $value = NULL) {
  return CRM_Core_BAO_Setting::setItem(
    $value,
    'Campaign Monitor Preferences',
    $variable
  );
}

/**
 * Get Campaign Monitor Variable
 */
function campaignmonitor_variable_get($variable, $default = NULL) {
  return CRM_Core_BAO_Setting::getItem(
    'Campaign Monitor Preferences',
    $variable,
    NULL,
    $default
  );
}

/**
 * Run through each new Subscription History Entry
 * and Update Campaign Monitor Accordingly.
 */
function campaignmonitor_update_subscription_history() {
  
  // Get the API Key
  $api_key = campaignmonitor_variable_get('api_key');
  
  // If the API Key or Client ID are empty
  // return now, for there is nothing else we can do.
  if (empty($api_key)) {
    return;
  }
  
  // Get the Groups
  $groups = campaignmonitor_variable_get('groups', array());
  
  // Get the Groups
  $group_map = campaignmonitor_variable_get('group_map', array());
  
  // Get the Last ID retrieved.
  $last_id = campaignmonitor_variable_get('subscription_history', 0);
  
  // Set this to the new last id.
  $new_last_id = $last_id;
  
  // Get the Subscription History since the last time we ran this.
  $history = new CRM_Contact_BAO_SubscriptionHistory();
  $history->whereAdd('id > '.$last_id);
  $history->find();
  
  // Setup the CS Subscribers.
  $subscribers = new CS_REST_Subscribers(NULL, $api_key);
  
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
    $subscribers->set_list_id($list_id);
    
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
  campaignmonitor_variable_set('subscription_history', $new_last_id);
  
}

/**
 * Update Campaign Monitor to Match the new Contact.
 */
function campaignmonitor_update_contact($op, $contact_id, $params) {

  // Get the API Key
  $api_key = campaignmonitor_variable_get('api_key');
  
  // If the API Key or Client ID are empty
  // return now, for there is nothing else we can do.
  if (empty($api_key)) {
    return;
  }
  
  // Get the Groups
  $groups = campaignmonitor_variable_get('groups', array());
  
  // Get the Groups
  $group_map = campaignmonitor_variable_get('group_map', array());
  
  // Get the Contact
  $contact = new CRM_Contact_BAO_Contact();
  $contact->get('id', $contact_id);
  
  // Setup the CS Subscribers.
  $subscribers = new CS_REST_Subscribers(NULL, $api_key);
  
  // Get the Contact's Current Primary Email.
  $email = new CRM_Core_BAO_Email();
  $email->whereAdd('contact_id = '.$contact->id);
  $email->whereAdd('is_primary = 1');
  $email->find(TRUE);
  
  if ($op == 'create' || $op == 'edit') {
    
    $primary_email = '';
    
    // Find the Primary Eamil from the Paramaters.
    foreach ($params['email'] as $email_params) {
      if (!empty($email_params['is_primary'])) {
        $primary_email = $email_params['email'];
      }
    }
    
    // See if the Current Primary Email is different from the submitted value.
    if ($email->email != $primary_email) {
      
      // Update the List to reflect the new primary email.
      foreach ($params['group'] as $group_id => $in_group) {
        
        // Make sure that we should be working with this user.
        if (!empty($groups[$group_id]) && !empty($group_map[$group_id])) {
          
          // Set the List ID
          $subscribers->set_list_id($group_map[$group_id]);
          
          // Create the Parameters to be Updated.
          $subscriber = array (
            'EmailAddress' => $primary_email,
          );
          
          // If Both emails are empty, the email has changed.
          if (!empty($email->email) && !empty($primary_email)) {
            
            // Create the Parameters to be Updated.
            $subscriber = array (
              'EmailAddress' => $primary_email,
            );
          
            // Update the Subscriber.
            $result = $subscribers->update($email->email, $subscriber);
          }
          // if the Existing email is empty, subscirbe the user (only if they are in the group)
          elseif ($in_group && empty($email->email) && !empty($primary_email)) {
            
            // Create the Paramaters to be Subscribed
            $subscriber = array (
              'EmailAddress' => $primary_email,
              'Name' => $contact->display_name,
              'Resubscribe' => !empty($params['privacy']['do_not_email']) ? FALSE : TRUE,
              'RestartSubscriptionBasedAutoResponders' => FALSE,
            );
            
            // Add the Subscriber.
            $result = $subscribers->add($subscriber);
            
          }
          // If the exting email is not empty, but the primary email is, then they should be deleted.
          elseif (!empty($email->email) && empty($primary_email)) {
            
            // Delete the Subscriber.
            $result = $subscribers->delete($email->email);
            
          }
          
          
        }
        
      }
    
    }
  
  }
  // If the User is being deleted
  elseif ($op == 'delete' && !empty($email->email)) {
    
    // Loop through all groups that should be synced.
    foreach ($groups as $group_id => $sync) {
      
      // If a map exists for said group
      if ($sync && !empty($group_map[$group_id])) {
        
          // Set the List ID
          $subscribers->set_list_id($group_map[$group_id]);
          
          // If the Contact hasn't been removed yet
          if (!empty($contact->is_deleted)) {
            // Delete the Subscriber
            $result = $subscribers->delete($email->email);
          }
          else {
            // Remove the Subscriber
            $result = $subscribers->unsubscribe($email->email);
          }
          
        }        
    }
      
  }
  // If the Contact is being created or restored
  elseif (!empty($email->email) && $op == 'restore') {
  
    // Get all the Groups a Contact was in.
    $group_contact = new CRM_Contact_BAO_GroupContact();
    $group_contact->whereAdd('contact_id = '.$contact->id);
    $group_contact->whereAdd("status = 'Added'");
    $group_contact->find();
    
    // Loop through Each Group.
    while ($group_contact->fetch()) {
      
      // Set the Group ID.
      $group_id = $group_contact->group_id;
      
      // Make sure this group should be synced and it is mapped.
      if (!empty($groups[$group_id]) && !empty($group_map[$group_id])) {
      
        // Set the List ID.
        $subscribers->set_list_id($group_map[$group_id]);
        
        $subscriber = array (
          'EmailAddress' => $email->email,
          'Name' => $contact->display_name,
          'Resubscribe' => $contact->do_not_email ? FALSE : TRUE,
          'RestartSubscriptionBasedAutoResponders' => FALSE,
        );
        $result = $subscribers->add($subscriber);
        
      }
      
    }
    
  }
  
}

/**
 * When a CiviCRM Group is deleted,
 * remove it's list and references.
 */
function campaignmonitor_delete_group($group) {
  
  // Get the API Key
  $api_key = campaignmonitor_variable_get('api_key');
  
  // Get the Groups
  $groups = campaignmonitor_variable_get('groups', array());
  
  // Get the Groups
  $group_map = campaignmonitor_variable_get('group_map', array());
  
  // Before attempting to remove the List
  // Make sure all data is ready
  if (!empty($api_key) && !empty($group_map[$group->id])) {
    
    // Delete the List.
    $lists = new CS_REST_Lists($group_map[$group->id], $api_key);
    $result = $lists->delete();
    
  }
  
  unset($groups[$group->id]);
  
  campaignmonitor_variable_set('groups', $groups);
  
  unset($group_map[$group->id]);
  
  campaignmonitor_variable_set('group_map', $group_map);
  
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
  
  $navigation = new CRM_Core_BAO_Navigation();
  
  $params = array(
    'name' => 'Campaign Monitor',
    'label' => 'Campaign Monitor',
    'url' => 'civicrm/admin/setting/campaignmonitor',
    'permission' => 'access CiviCRM',
    'parent_id' => 137,
    'is_active' => TRUE,
  );
  
  $navigation->add($params);
  
  $params = array(
    'name' => 'Campaign Monitor Sync',
    'label' => 'Campaign Monitor Sync',
    'url' => 'civicrm/admin/campaignmonitor/sync',
    'permission' => 'access CiviCRM',
    'parent_id' => 15,
    'is_active' => TRUE,
  );
  
  $navigation->add($params);
  
  return _campaignmonitor_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function campaignmonitor_civicrm_disable() {
  
  $navigation = new CRM_Core_BAO_Navigation();
  $navigation->url = 'civicrm/admin/setting/campaignmonitor';
  $navigation->find();
  
  while ($navigation->fetch()) {
    $navigation->processDelete($navigation->id);
  }
  
  $navigation = new CRM_Core_BAO_Navigation();
  $navigation->url = 'civicrm/admin/campaignmonitor/sync';
  $navigation->find();
  
  while ($navigation->fetch()) {
    $navigation->processDelete($navigation->id);
  }
  
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
