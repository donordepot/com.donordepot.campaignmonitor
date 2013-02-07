<?php

class CRM_Campaignmonitor_Page_WebHook extends CRM_Core_Page {

  function run() {
    
    $data = array(
      'hello' => 'world!',
    );
    
    // Return the JSON output
    header('Content-type: application/json');
    $json = new Services_JSON();
    $return = $json->encode($data);
    print $return;
    CRM_Utils_System::civiExit();
    
  }
  
}
