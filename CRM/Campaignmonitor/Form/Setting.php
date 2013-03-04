<?php

class CRM_Campaignmonitor_Form_Setting extends CRM_Core_Form {
  
  /**
   * Function to return the Form Name.
   *
   * @return None
   * @access public
   */
  public function getTitle() {
    return ts('Campaign Monitor');
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    
    // Add the API Key Element
    $this->addElement('text', 'api_key', ts('API Key'), array(
      'size' => 48,
    ));

    $defaults['api_key'] = CRM_Core_BAO_Setting::getItem('Campaign Monitor Preferences',
      'api_key', NULL, FALSE
    );
    
    // Add the Client ID Element
    $this->addElement('text', 'client_id', ts('Client ID'), array(
      'size' => 32,
    ));
    
    $defaults['client_id'] = CRM_Core_BAO_Setting::getItem('Campaign Monitor Preferences',
      'client_id', NULL, FALSE
    );
    
    // Retrieve the CiviCRM Groups
    $groups = CRM_Contact_BAO_Group::getGroups();
    
    // Create a Checkbox for each Group
    $checkboxes = array();
    foreach ($groups as $group) {
      $checkboxes[] = &HTML_QuickForm::createElement('checkbox', $group->id, $group->title);
    }
    
    // Add the Group of Checkboxes
    $this->addGroup($checkboxes, 'groups', ts('Groups'));
    
    // Get the Currently Selected Groups
    $current = CRM_Core_BAO_Setting::getItem('Campaign Monitor Preferences',
      'groups', NULL, FALSE
    );
    
    // Set the Currently Selected Groups as the Default.
    // If non is availble, the default will be unchecked.
    if (!empty($current)) {
      foreach ($current as $key => $value) {
        $defaults['groups'][$key] = $value;
      }
    }
    
    // Create the Submit Button.
    $buttons = array(
      array(
        'type' => 'submit',
        'name' => ts('Save'),
      ),
    );
    
    // Add the Buttons.
    $this->addButtons($buttons);
    
    // Set the Default Field Values.
    $this->setDefaults($defaults);
    
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
    
    // Save the API Key.
    if (CRM_Utils_Array::value('api_key', $params)) {
      CRM_Core_BAO_Setting::setItem($params['api_key'],
        'Campaign Monitor Preferences',
        'api_key'
      );
    }
    
    // Save the Client ID.
    if (CRM_Utils_Array::value('client_id', $params)) {
      CRM_Core_BAO_Setting::setItem($params['client_id'],
        'Campaign Monitor Preferences',
        'client_id'
      );
    }
    
    // Save the selected groups.
    if (CRM_Utils_Array::value('groups', $params)) {
      CRM_Core_BAO_Setting::setItem($params['groups'],
        'Campaign Monitor Preferences',
        'groups'
      );
    }
    
    // If all the necessary data is availible,
    // map the CiviCRM Groups to Campaign Monitor Lists.
    if (CRM_Utils_Array::value('api_key', $params) && CRM_Utils_Array::value('client_id', $params) && CRM_Utils_Array::value('groups', $params)) {
      $this->mapGroups($params);
    }
        
  }
  
  /**
   * Function to Map CiviCRM Groups to Campaign Monitor Lists.
   *
   * @access private
   *
   * @return None
   */
  private function mapGroups($params = array()) {
  
    // Get the Current Group Map
    $group_map = CRM_Core_BAO_Setting::getItem('Campaign Monitor Preferences',
      'group_map', NULL, FALSE
    );
    
    // Ensure that the value is not NULL
    $group_map = !empty($group_map) ? $group_map : array();
    
    // Retrieve the CiviCRM Groups.
    $groups = CRM_Contact_BAO_Group::getGroups();
    
    // Put all of the Group ID's into an array
    $group_ids = array();
    foreach ($groups as $group) {
      $group_ids[$group->id] = $group->id;
    }
          
    // If any Groups have been removed, remove the map.
    foreach ($group_map as $key => $value) {
      if (!in_array($key, $group_ids)) {
        unset($group_map[$key]);
      }
    }
    
    // Connect to Campaign Monitor
    $cs_client = new CS_REST_Clients($params['client_id'], $params['api_key']);
    
    // Get the Lists from Campaign Monitor
    $result = $cs_client->get_lists();
    
    // Ensure that the value is not NULL
    $lists = !empty($result->response) ? $result->response : array();
    
    // Put all of the List ID's into an array
    $list_ids = array();
    foreach ($lists as $list) {
      $list_ids[$list->ListID] = $list->ListID;
    }
          
    // For each Group, make sure it is mapped
    foreach ($groups as $group) {
      
      // Only Map Groups that are upposed to be synced.
      if (empty($params['groups'][$group->id])) {
        continue;
      }
      
      // Skip Groups that have already been mapped.
      if (!empty($group_map[$group->id]) && !empty($list_ids[$group_map[$group->id]])) {
        continue;
      }
      
      // Connect to Campaign Monitor
      $cs_lists = new CS_REST_Lists(NULL, $params['api_key']);
      
      // Build the List Details
      $list_details = array(
        'Title' => $group->title,
        'ConfirmedOptIn' => FALSE,
      );
      
      // Create the List
      $result = $cs_lists->create($params['client_id'], $list_details);
      $list_id = !empty($result->response) ? $result->response : '';
      
      // Save the List ID in the Map
      $group_map[$group->id] = $list_id;
      
      // If there is a list id, create the webhook
      if (!empty($list_id)) {
      
        // Reconnect to Campaign Monitor
        $cs_lists = new CS_REST_Lists($list_id, $params['api_key']);
        
        // Build the webhook.
        $webhook = array(
          'Events' => array(
            CS_REST_LIST_WEBHOOK_SUBSCRIBE,
            CS_REST_LIST_WEBHOOK_DEACTIVATE,
            CS_REST_LIST_WEBHOOK_UPDATE,
          ),
          'Url' => CRM_Utils_System::url('civicrm/campaignmonitor/webhook', NULL, TRUE),
          'PayloadFormat' => CS_REST_WEBHOOK_FORMAT_JSON,
        );
        
        // Create the webhook.
        $cs_lists->create_webhook($webhook);
        
      }
                      
    }
    
    // Save the new Group Map
    CRM_Core_BAO_Setting::setItem($group_map,
      'Campaign Monitor Preferences',
      'group_map'
    );
    
  }
  
}
