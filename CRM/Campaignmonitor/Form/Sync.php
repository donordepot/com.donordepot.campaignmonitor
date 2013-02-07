<?php

require_once 'campaignmonitor.php';

require_once 'packages/createsend-php/csrest_clients.php';
require_once 'packages/createsend-php/csrest_lists.php';

class CRM_Campaignmonitor_Form_Sync extends CRM_Core_Form {
  
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
    
  }
  
}
