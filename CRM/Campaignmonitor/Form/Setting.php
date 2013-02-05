<?php

require_once 'CRM/Core/Form.php';

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


    $buttons = array(
      array(
        'type' => 'submit',
        'name' => ts('Save'),
      ),
    );
    

    $this->addButtons($buttons);

    $this->setDefaults($defaults);
    
  }
  
}
