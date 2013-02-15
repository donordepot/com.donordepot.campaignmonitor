com.donordepot.campaignmonitor
==============================

CiviCRM Extension to Sync CiviCRM Group Contacts with Campaingn Monitor List Subscribers.

Requirements
------------

 * CiviCRM 4.0+

 * CampaignMonitor API Key

Installation
------------
1. Copy this folder, with all of its contents to your civicrm extensions directory.
This directory and be set at: http://example.com/civicrm/admin/setting/path?reset=1

2. Go to the Extension Manager: http://example.com/civicrm/admin/extensions?reset=1

3. Install the "Campaign Monitor" Extension

4. Go to the Campaign Monitor Settings: http://example.com/civicrm/admin/setting/campaignmonitor

5. Add your Campaign Monitor API Key & Client ID and select which groups you would like to be synced.

6. Click Save.


Usage
-----
* Existing Campaign Monitor Lists will not be touched or synced. Everytime the Campgain Monitor Settings are saved the lists are saved, lists are created for the selected groups (if they do not already exist).
* Groups to Lists can be manually synced at http://civicrm/admin/campaignmonitor/sync
* Lists will be automatically updated when Contacts are added, removed, or deleted from a group.
* When a user subscribes to a list, they will be added to the CiviCRM Group.
* When a user unsubscribes from a list, they will be removed from the CiviCRM Group.
* When deselecting a list from the Campaign Monitor Settings, the map will be removed, but the list will not be touched.
* When a Group is deleted in CiviCRM, the matching Campaign Monitor List will be removed.

License
-------
This software is licensed under the GNU Affero General Public License 3 (GNU AGPL 3)
