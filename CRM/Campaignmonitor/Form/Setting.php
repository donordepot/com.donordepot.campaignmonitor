<?php

require_once 'packages/Net/Curl.php';

class CRM_Campaignmonitor_Form_Setting extends CRM_Core_Form {
  
  /**
   * Function to actually build the form
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
  
    $this->addElement('text', 'api_key', ts('API Key'), array(
      'size' => 32,
    ));

    $defaults['api_key'] = CRM_Core_BAO_Setting::getItem('Campaign Monitor Preferences',
      'api_key', NULL, FALSE
    );
    
    $groups = CRM_Contact_BAO_Group::getGroups();
    
    $checkboxes = array();
    foreach ($groups as $group) {
      $checkboxes[] = &HTML_QuickForm::createElement('checkbox', $group->id, $group->name);
    }
    $this->addGroup($checkboxes, 'groups', ts('Groups'));
    
    $current = CRM_Core_BAO_Setting::getItem('Campaign Monitor Preferences',
      'groups', NULL, FALSE
    );
    
    if (!empty($current)) {
      foreach ($current as $key => $value) {
        $defaults['groups'][$key] = $value;
      }
    }

    $buttons = array(
      array(
        'type' => 'submit',
        'name' => ts('Save'),
      ),
    );
    

    $this->addButtons($buttons);

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
  
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    if (CRM_Utils_Array::value('api_key', $params)) {
      // Save the api_key.
      CRM_Core_BAO_Setting::setItem($params['api_key'],
        'Campaign Monitor Preferences',
        'api_key'
      );
    }
    
    if (CRM_Utils_Array::value('groups', $params)) {
      CRM_Core_BAO_Setting::setItem($params['groups'],
        'Campaign Monitor Preferences',
        'groups'
      );
    }
    
    if (CRM_Utils_Array::value('api_key', $params) && CRM_Utils_Array::value('groups', $params)) {
    
      // Update the Group Map.
      $group_map = CRM_Core_BAO_Setting::getItem('Campaign Monitor Preferences',
        'group_map', NULL, FALSE
      );
      
      $group_map = !empty($group_map) ? $group_map : array();
      
      $groups = CRM_Contact_BAO_Group::getGroups();
      
      $group_ids = array();
      foreach ($groups as $group) {
        $group_ids[$group->id] = $group->id;
      }
      
      // print '<pre>'.print_r($groups, TRUE).'</pre>';
      
      // If any Groups have been removed, remove the map.
      foreach ($group_map as $key => $value) {
        if (!in_array($key, $group_ids)) {
          unset($group_map[$key]);
        }
      }
      
      // For each Group, make sure it is mapped
      foreach ($groups as $group) {
        
        // Only Map Groups that are upposed to be synced.
        if (empty($params['groups'][$group->id])) {
          continue;
        }
        
        $curl = new Net_Curl();
        $curl->url = 'https://graph.facebook.com/davidbarratt';
        $result = $curl->execute();
        
        // print '<pre>'.print_r($result, TRUE).'</pre>';
        
      }
    
    }
    
  }
  
}
