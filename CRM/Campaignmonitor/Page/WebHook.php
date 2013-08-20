<?php

require_once 'vendor/campaignmonitor/createsend-php/class/serialisation.php';
require_once 'vendor/campaignmonitor/createsend-php/class/log.php';

class CRM_Campaignmonitor_Page_WebHook extends CRM_Core_Page {

  function run() {

    // Initialize the data variable
    $data = array();

    // Get the API Key
    $api_key = campaignmonitor_variable_get('api_key');

    // Get the Groups
    $groups = campaignmonitor_variable_get('groups', array());

    // Get the Groups
    $group_map = campaignmonitor_variable_get('group_map', array());

    // Get a serialiser for the webhook data - We assume here that we're dealing with json
    $serialiser = CS_REST_SERIALISATION_get_available(new CS_REST_Log(CS_REST_LOG_NONE));

    // Read all the posted data from the input stream
    $raw_post = file_get_contents("php://input");

    // And deserialise the data
    $data = $serialiser->deserialise($raw_post);

    if (!empty($data->ListID)) {

      // Connect to Campaign Monitor.
      // This also initalizes the PHP Contants
      $cs_lists = new CS_REST_Lists($data->ListID, $api_key);

      $group_id = array_search($data->ListID, $group_map);

      // Make sure this List is Mapped and is supposed to by synced.
      if ($group_id !== FALSE && !empty($groups[$group_id])) {

        $contact_ids = array(
          'add' => array(),
          'remove' => array(),
        );

        // And now just do something with the data
        foreach ($data->Events as $event) {
            // If the Event is a New Subscriber
            if ($event->Type == CS_REST_LIST_WEBHOOK_SUBSCRIBE) {

              // Find the Email.
              $email = new CRM_Core_BAO_Email();
              $email->get('email', $event->EmailAddress);

              // If the Email was found.
              if (!empty($email->contact_id)) {
                $contact_ids['add'][] = $email->contact_id;
              }

            }
            // If the Event is a an Unsubscriber
            elseif ($event->Type == CS_REST_LIST_WEBHOOK_DEACTIVATE) {

              // Find the Email.
              $email = new CRM_Core_BAO_Email();
              $email->get('email', $event->EmailAddress);

              // If the Email was found.
              if (!empty($email->contact_id)) {
                $contact_ids['remove'][] = $email->contact_id;
              }

            }
            // If the Event is a an Update
            elseif ($event->Type == CS_REST_LIST_WEBHOOK_UPDATE) {

              // Find the Email.
              $email = new CRM_Core_BAO_Email();
              $email->get('email', $event->OldEmailAddress);
              if($email->email != $event->EmailAddress){
              	$email->email = $event->EmailAddress;
              	$email->save();
              }


            }

        }

        // Setup the GroupContact Object
        $group_contact = new CRM_Contact_BAO_GroupContact();

		$filtered_contact_ids = array(
		          'add' => array(),
		          'remove' => array(),
        );

        // Add the Contacts to the Group
        if (!empty($contact_ids['add'])) {
          foreach($contact_ids['add'] as $contact_id){

            $group_contact->group_id = $group_id;
		    $group_contact->contact_id = $contact_id;
		    $group_contact->orderBy('id ASC');
		    $group_contact->limit(0, 1);
		    $group_contact->find(true);

            if(empty($group_contact->status) || ($group_contact->status != 'Added')){
          	   $filtered_contact_ids['add'][] = $contact_id;
          	 }
          }
        }

        // Remove the Contacts from the Group
        if (!empty($contact_ids['remove'])) {
          foreach($contact_ids['remove'] as $contact_id){

            $group_contact->group_id = $group_id;
		    $group_contact->contact_id = $contact_id;
		    $group_contact->orderBy('id ASC');
		    $group_contact->limit(0, 1);
		    $group_contact->find(true);

            if($group_contact->status != 'Removed'){
             $filtered_contact_ids['remove'][] = $contact_id;
            }
          }
        }

        // Add the Contacts to the Group
		 if (!empty($filtered_contact_ids['add'])) {
		   $group_contact->addContactsToGroup($filtered_contact_ids['add'], $group_id, 'API');
		}

		// Remove the Contacts from the Group
		if (!empty($filtered_contact_ids['remove'])) {
		   $group_contact->removeContactsFromGroup($contact_ids['remove'], $group_id, 'API');
        }

      }

    }

    // Return the JSON output
    header('Content-type: application/json');
    print json_encode($data);
    CRM_Utils_System::civiExit();

  }

}
