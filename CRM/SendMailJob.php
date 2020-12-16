<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Queue item for sending emails to contacts
 */
class CRM_Mailbatch_SendMailJob
{
    /** @var string job title */
    public $title = '';

    /** @var array list of (int) contact IDs */
    protected $contact_ids;

    /** @var array template to send to */
    protected $config;

    public function __construct($contact_ids, $config, $title)
    {
        $this->contact_ids = $contact_ids;
        $this->config = $config;
        $this->title = $title;
    }

    /**
     * Execute the batch of emails to be sent
     * @return true
     */
    public function run(): bool
    {
        if (!empty($this->contact_ids)) {
            // load the contacts
            $contacts = civicrm_api3('Contact', 'get', [
                'id'           => ['IN' => $this->contact_ids],
                'option.limit' => 0,
            ]);
            // trigger sendMessageTo for each one of them
            foreach ($contacts['values'] as $contact) {
                try {

                } catch (Exception $exception) {
                    // this shouldn't happen, sendMessageTo has it's own error handling
                    Civi::log()->notice("EventMessages.SendMailJob: Error sending to contact [{$contact['id']}]: " . $exception->getMessage());
                }
            }
        }
        return true;
    }
}