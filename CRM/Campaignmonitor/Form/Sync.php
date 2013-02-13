<?php

require_once 'campaignmonitor.php';

require_once 'packages/createsend-php/csrest_clients.php';
require_once 'packages/createsend-php/csrest_lists.php';

class CRM_Campaignmonitor_Form_Sync extends CRM_Core_Form {

  const QUEUE_NAME = 'cm-sync';
  
  const END_URL = 'civicrm/admin/campaignmonitor/sync';
  
  const END_PARAMS = 'run=true';
  
  /**
   * Function to return the Form Name.
   *
   * @return None
   * @access public
   */
  public function getTitle() {
    return ts('Campaign Monitor Sync');
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    
    // Create the Submit Button.
    $buttons = array(
      array(
        'type' => 'submit',
        'name' => ts('Sync Contacts'),
      ),
    );
    
    // Add the Buttons.
    $this->addButtons($buttons);
    
    // Set the Default Field Values.
    // $this->setDefaults($defaults);
    
  }
  
  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
  
    // Store the submitted values in an array.
    $params = $this->controller->exportValues($this->_name);
    
    // Setup the Queue
    $queue = CRM_Queue_Service::singleton()->create(array(
      'name' => self::QUEUE_NAME,
      'type' => 'Sql',
      'reset' => TRUE,
    ));
    
    // Set the Number of Rounds to 0
    $round = 0;
    
    // Set the Group IDs to an empty array
    $group_ids = array();
    
    // Get the Groups
    $groups = campaignmonitor_variable_get('groups', array());
    
    // Get the Groups
    $group_map = campaignmonitor_variable_get('group_map', array());
    
    // Loop through each Group
    foreach ($groups as $group_id => $sync) {
      
      // If the Group is Supposed to be synced and a map exists for the group
      if (!empty($groups[$group_id]) && !empty($group_map[$group_id])) {
        $group_ids[$group_id] = $group_id;
      }
    
    }
    
    // Figure out how many Contacts there are.
    if (!empty($group_ids)) {
      
      $group_contact = new CRM_Contact_BAO_GroupContact();
      $group_contact->addWhere('group_id IN ('.implode(',', $group_ids).')');
      $group_contact->addWhere("status = 'Added' OR status = 'Removed'");
      $group_contact->orderBy('id ASC');
      $count = $group_contact->count();
      
      $rounds = ceil($count/10);
      
    }
    
    // Setup a Task in the Queue
    $i = 0;
    while ($i < $rounds) {
    
      $start = $i * 10;
      
      $task = new CRM_Queue_Task(
        array (
          'CRM_Campaignmonitor_Form_Sync',
          'runSync',
        ),
        array(
          $start,
        ),
        'Campaign Monitor Sync '.($i+1).' of '.$rounds
      );
      
      // Add the Task to the Queu
      $queue->createItem($task);
      
      $i++;
    }
    
    // Setup the Runner
    $runner = new CRM_Queue_Runner(array(
      'title' => ts('Campaign Monitor Sync'),
      'queue' => $queue,
      'errorMode'=> CRM_Queue_Runner::ERROR_ABORT,
      'onEndUrl' => CRM_Utils_System::url(self::END_URL, self::END_PARAMS),
    ));
    
    // Run Everything in the Queue via the Web.
    $runner->runAllViaWeb();
  }
  
  /**
   * Run the From
   *
   * @access public
   *
   * @return TRUE
   */
  public function runSync(CRM_Queue_TaskContext $ctx, $start) {
  
     // Get the API Key
    $api_key = campaignmonitor_variable_get('api_key');
    
    // If the API Key or Client ID are empty
    // return now, for there is nothing else we can do.
    if (empty($api_key)) {
      return CRM_Queue_Task::TASK_FAIL;
    }
    
    // Get the Groups
    $groups = campaignmonitor_variable_get('groups', array());
    
    // Get the Groups
    $group_map = campaignmonitor_variable_get('group_map', array());
    
    // Setup the CS Subscribers.
    $subscribers = new CS_REST_Subscribers(NULL, $api_key);
    
    // Loop through each Group
    foreach ($groups as $group_id => $sync) {
      
      // If the Group is Supposed to be synced and a map exists for the group
      if (!empty($groups[$group_id]) && !empty($group_map[$group_id])) {
      
        // Setup the CS Subscribers.
        $subscribers->set_list_id($group_map[$group_id]);
        
        // Find the GroupContacts matching this group_id
        $group_contact = new CRM_Contact_BAO_GroupContact();
        $group_contact->addWhere('group_id = '.$group_id);
        $group_contact->addWhere("status = 'Added' OR status = 'Removed'");
        $group_contact->orderBy('id ASC');
        $group_contact->limit($start, 10);
        $group_contact->find();
        
        while ($group_contact->fetch()) {
        
          $contact = new CRM_Contact_BAO_Contact();
          $contact->id = $group_contact->contact_id;
          $contact->find(TRUE);
          
          $email = new CRM_Core_BAO_Email();
          $email->contact_id = $group_contact->contact_id;
          $email->is_primary = TRUE;
          $email->find(TRUE);
          
          if (!empty($email->email)) {
           
           $resubscribe = TRUE;
           if ($contact->do_not_email || $group_contact->status == 'Removed') {
             $resubscribe = FALSE;
           }
           
           $subscriber = array (
            'EmailAddress' => $email->email,
            'Name' => $contact->display_name,
            'Resubscribe' => $resubscribe,
            'RestartSubscriptionBasedAutoResponders' => FALSE,
          );
          $result = $subscribers->add($subscriber);
           
            
          }
          
        }
        
      }
      
    }
    
    
    return CRM_Queue_Task::TASK_SUCCESS;
  }
  
}
