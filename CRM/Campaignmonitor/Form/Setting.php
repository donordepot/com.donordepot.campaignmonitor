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
    return ts('Track and Respond');
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
  
    $this->addElement('checkbox', 'override_verp', ts('Track Replies?'));

    $defaults['override_verp'] = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'track_civimail_replies', NULL, FALSE
    );

    $this->add('checkbox', 'forward_replies', ts('Forward Replies?'));
    $defaults['forward_replies'] = FALSE;

    $this->add('checkbox', 'url_tracking', ts('Track Click-throughs?'));
    $defaults['url_tracking'] = TRUE;

    $this->add('checkbox', 'open_tracking', ts('Track Opens?'));
    $defaults['open_tracking'] = TRUE;

    $this->add('checkbox', 'auto_responder', ts('Auto-respond to Replies?'));
    $defaults['auto_responder'] = FALSE;

    $this->add('select', 'visibility', ts('Mailing Visibility'),
      CRM_Core_SelectValues::ufVisibility(TRUE), TRUE
    );

    $this->add('select', 'reply_id', ts('Auto-responder'),
      CRM_Mailing_PseudoConstant::component('Reply'), TRUE
    );

    $this->add('select', 'unsubscribe_id', ts('Unsubscribe Message'),
      CRM_Mailing_PseudoConstant::component('Unsubscribe'), TRUE
    );

    $this->add('select', 'resubscribe_id', ts('Resubscribe Message'),
      CRM_Mailing_PseudoConstant::component('Resubscribe'), TRUE
    );

    $this->add('select', 'optout_id', ts('Opt-out Message'),
      CRM_Mailing_PseudoConstant::component('OptOut'), TRUE
    );

    //FIXME : currently we are hiding save an continue later when
    //search base mailing, we should handle it when we fix CRM-3876
    if ($this->_searchBasedMailing) {
      $buttons = array(
        array('type' => 'back',
          'name' => ts('<< Previous'),
        ),
        array(
          'type' => 'next',
          'name' => ts('Next >>'),
          'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      );
    }
    else {
      $buttons = array(
        array('type' => 'back',
          'name' => ts('<< Previous'),
        ),
        array(
          'type' => 'next',
          'name' => ts('Next >>'),
          'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'submit',
          'name' => ts('Save & Continue Later'),
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      );
    }

    $this->addButtons($buttons);

    $this->setDefaults($defaults);
    
  }
  
}
