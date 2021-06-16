<?php
/*-------------------------------------------------------+
| SYSTOPIA MailBatch Extension                           |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Mailbatch_ExtensionUtil as E;

/**
 * Send E-Mail to contacts based on contributions task
 */
class CRM_Mailbatch_Form_Task_ContributionEmail extends CRM_Contribute_Form_Task
{
    /**
     * Compile task form
     */
    function buildQuickForm()
    {
        $contribution_count = count($this->_contributionIds);
        $contact_count = $this->getContactCount();

        // now build the form
        CRM_Utils_System::setTitle(E::ts('Send %1 Email(s) to %2 Contact(s)', [1 => $contribution_count, 2 => $contact_count]));

        $this->add(
            'select',
            'template_id',
            E::ts('Message Template'),
            $this->getMessageTemplates(),
            true,
            ['class' => 'crm-select2 huge']
        );

        $this->add(
            'select',
            'sender_email',
            E::ts('Sender'),
            $this->getSenderOptions(),
            true,
            ['class' => 'crm-select2 huge']
        );

        $this->add(
            'text',
            'sender_cc',
            E::ts('CC'),
            ['class' => 'huge'],
            false
        );

        $this->add(
            'text',
            'sender_bcc',
            E::ts('BCC'),
            ['class' => 'huge'],
            false
        );

        $this->add(
            'text',
            'sender_reply_to',
            E::ts('Reply-To'),
            ['class' => 'huge'],
            false
        );

        $this->add(
            'select',
            'batch_size',
            E::ts('Batch Size'),
            $this->getBatchSizes(),
            true,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'location_type_id',
            E::ts('E-Mail Type'),
            $this->getEmailTypes(),
            false,
            ['class' => 'crm-select2']
        );

        $this->add(
            'checkbox',
            'send_wo_attachment',
            E::ts('Send if attachment not found?')
        );

        $this->add(
            'select',
            'attachment1_type',
            E::ts('Type'),
            [
                'file'         => E::ts("File on Server"),
                'invoice'      => E::ts("Invoice"),
            ],
            true,
            ['class' => 'crm-select2']
        );

        $this->add(
            'text',
            'attachment1_path',
            E::ts('Attachment Path/URL'),
            ['class' => 'huge'],
            false
        );

        $this->add(
            'text',
            'attachment1_name',
            E::ts('Attachment Name'),
            ['class' => 'huge'],
            false
        );

        $activity_types = $this->getActivityTypes();
        $this->add(
            'select',
            'sent_activity_type_id',
            E::ts('Activity (when sent)'),
            $activity_types,
            false,
            ['class' => 'huge']
        );

        $this->add(
            'text',
            'sent_activity_subject',
            E::ts('Activity Subject'),
            ['class' => 'huge'],
            false
        );

        $this->add(
            'select',
            'failed_activity_type_id',
            E::ts('Activity (when failed)'),
            $activity_types,
            false,
            ['class' => 'huge']
        );

        $this->add(
            'select',
            'activity_grouped',
            E::ts('Activity Style'),
            [0 => E::ts("Individual"), 1 => E::ts("Grouped")],
            false,
            ['class' => 'huge']
        );

        $this->add(
            'text',
            'failed_activity_subject',
            E::ts('Subject (Sending Failed)'),
            ['class' => 'huge']
        );

        if (!empty($no_email_count)) {
            $this->add(
                'text',
                'failed_activity_subject2',
                E::ts('Subject (No Email)'),
                ['class' => 'huge']
            );
        }

        $this->addEntityRef(
            'failed_activity_assignee',
            E::ts('Assign to'),
            [
                'multiple' => TRUE,
                'api'      => ['params' => ['is_deceased' => 0]]
            ],
            false
        );

        // set default values
        $this->setDefaults([
            'template_id'              => Civi::settings()->get('batchmail_template_id'),
            'batch_size'               => Civi::settings()->get('batchmail_batch_size'),
            'sender_email'             => Civi::settings()->get('batchmail_sender_email'),
            'sender_cc'                => Civi::settings()->get('batchmail_sender_cc'),
            'sender_bcc'               => Civi::settings()->get('batchmail_sender_bcc'),
            'sender_reply_to'          => Civi::settings()->get('batchmail_sender_reply_to'),
            'send_wo_attachment'       => Civi::settings()->get('batchmail_send_wo_attachment'),
            'location_type_id'         => Civi::settings()->get('batchmail_location_type_id'),
            'attachment1_path'         => Civi::settings()->get('batchmail_attachment1_path'),
            'attachment1_name'         => Civi::settings()->get('batchmail_attachment1_name'),
            'attachment1_type'         => Civi::settings()->get('batchmail_attachment1_type'),
            'sent_activity_type_id'    => Civi::settings()->get('batchmail_sent_activity_type_id'),
            'sent_activity_grouped'    => Civi::settings()->get('batchmail_sent_activity_grouped'),
            'sent_activity_subject'    => Civi::settings()->get('batchmail_sent_activity_subject'),
            'failed_activity_type_id'  => Civi::settings()->get('batchmail_failed_activity_type_id'),
            'failed_activity_subject'  => Civi::settings()->get('batchmail_failed_activity_subject'),
            'failed_activity_subject2' => Civi::settings()->get('batchmail_failed_activity_subject2'),
            'failed_activity_assignee' => Civi::settings()->get('batchmail_failed_activity_assignee'),
        ]);


        // calculate and add the number of contacts with no valid E-Mail
        if (!isset($this->_submitValues['location_type_id'])) {
            $this->_submitValues['location_type_id'] = Civi::settings()->get('batchmail_location_type_id');
        }
        $no_email_count = $this->getNoEmailCount();
        $this->assign('no_email_count', $no_email_count);

        // add buttons
        $this->addButtons([
              [
                  'type' => 'submit',
                  'name' => E::ts("Send %1 Emails", [1 => $contribution_count - $no_email_count]),
                  'isDefault' => true,
              ],
              [
                  'type' => 'refresh',
                  'name' => E::ts('Refresh'),
                  'isDefault' => false,
              ],
          ]);

    }


    function postProcess()
    {
        $values = $this->exportValues();
        $values['sender_contact_id'] = CRM_Core_Session::getLoggedInContactID();
        $no_email_count = $this->getNoEmailCount();
        $contribution_count = count($this->_contributionIds) - $no_email_count;

        // store default values
        Civi::settings()->set('batchmail_template_id', $values['template_id']);
        Civi::settings()->set('batchmail_sender_email', $values['sender_email']);
        Civi::settings()->set('batchmail_batch_size', $values['batch_size']);
        Civi::settings()->set('batchmail_sender_cc', $values['sender_cc']);
        Civi::settings()->set('batchmail_sender_bcc', $values['sender_bcc']);
        Civi::settings()->set('batchmail_sender_reply_to', $values['sender_reply_to']);
        Civi::settings()->set('batchmail_send_wo_attachment', CRM_Utils_Array::value('send_wo_attachment', $values, 0));
        Civi::settings()->set('batchmail_location_type_id', CRM_Utils_Array::value('location_type_id', $values, 0));
        Civi::settings()->set('batchmail_attachment1_path', $values['attachment1_path']);
        Civi::settings()->set('batchmail_attachment1_name', $values['attachment1_name']);
        Civi::settings()->set('batchmail_attachment1_type', $values['attachment1_type']);
        Civi::settings()->set('batchmail_sent_activity_type_id', $values['sent_activity_type_id']);
        Civi::settings()->set('batchmail_sent_activity_subject', $values['sent_activity_subject']);
        Civi::settings()->set('batchmail_failed_activity_type_id', $values['failed_activity_type_id']);
        Civi::settings()->set('batchmail_activity_grouped', $values['activity_grouped']);
        Civi::settings()->set('batchmail_failed_activity_subject', $values['failed_activity_subject']);
        Civi::settings()->set('batchmail_failed_activity_assignee', $values['failed_activity_assignee']);
        if (isset($values['failed_activity_subject2'])) {
            Civi::settings()->set('batchmail_failed_activity_subject2', $values['failed_activity_subject2']);
        }

        // if this is just a refresh, don't go any further
        if ($this->controller->_actionName[1] == 'refresh') {
            parent::postProcess();
            return;
        }


        // generate no-email activities for contacts with no emails if required
        if ($no_email_count > 0
            && !empty($values['failed_activity_type_id'])
            && !empty($values['failed_activity_subject2'])) {
            $this->createNoEmailActivities(
                $values['failed_activity_type_id'],
                $values['failed_activity_subject2'],
                $values['activity_grouped'],
                $values['failed_activity_assignee']
            );
        }

        // init a queue
        $queue = CRM_Queue_Service::singleton()->create([
            'type' => 'Sql',
            'name' => 'mailbatch_contribution_email_task_' . CRM_Core_Session::singleton()->getLoggedInContactID(),
            'reset' => true,
        ]);
        // add a dummy item to display the 'upcoming' message
        $queue->createItem(new CRM_Mailbatch_SendContributionMailJob(
            [],
            $values['template_id'],
            E::ts("Sending Emails %1 - %2", [
                1 => 1, // keep in mind that this is showing when the _next_ task is running
                2 => min($values['batch_size'], $contribution_count)])
        ));

        // run query to get all contacts
        $contribution_list = implode(',', $this->_contributionIds);
        $EMAIL_SELECTOR_CRITERIA = $this->getSQLEmailSelectorCriteria();
        CRM_Core_DAO::disableFullGroupByMode();
        $contact_query = CRM_Core_DAO::executeQuery("
            SELECT 
                   contribution.id AS contribution_id,
                   contact.id      AS contact_id,
                   email.email     AS email
            FROM civicrm_contribution contribution
            LEFT JOIN civicrm_contact contact
                   ON contact.id = contribution.contact_id
            LEFT JOIN civicrm_email email
                   ON email.contact_id = contact.id
                   AND {$EMAIL_SELECTOR_CRITERIA}
                   AND email.on_hold = 0
            WHERE contribution.id IN ({$contribution_list})
              AND email.id IS NOT NULL
            GROUP BY contribution.id");
        CRM_Core_DAO::reenableFullGroupByMode();

        // batch the contacts into bite-sized jobs
        $current_batch = [];
        $next_offset = $values['batch_size'];
        while ($contact_query->fetch()) {
            $current_batch[] = [$contact_query->contribution_id, $contact_query->contact_id, $contact_query->email];
            if (count($current_batch) >= $values['batch_size']) {
                $queue->createItem(
                    new CRM_Mailbatch_SendContributionMailJob(
                        $current_batch,
                        $values,
                        E::ts("Sending Emails %1 - %2", [
                            1 => $next_offset, // keep in mind that this is showing when the _next_ task is running
                            2 => $next_offset + $values['batch_size']])
                    )
                );
                $next_offset += $values['batch_size'];
                $current_batch = [];
            }
        }

        // add final runner
        $queue->createItem(
            new CRM_Mailbatch_SendContributionMailJob(
                $current_batch,
                $values,
                E::ts("Finishing")
            )
        );

        // start a runner on the queue
        $runner = new CRM_Queue_Runner([
                'title'     => E::ts("Sending %1 Event Emails", [1 => $contribution_count]),
                'queue'     => $queue,
                'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
                'onEndUrl'  => html_entity_decode(CRM_Core_Session::singleton()->readUserContext())
        ]);
        $runner->runAllViaWeb();
    }

    /**
     * Get a list of eligible templates
     * @return array
     *   list if id -> template name
     */
    private function getMessageTemplates(): array
    {
        $list = [];
        $query = civicrm_api3(
            'MessageTemplate',
            'get',
            [
                'is_active' => 1,
                'workflow_id' => ['IS NULL' => 1],
                'option.limit' => 0,
                'return' => 'id,msg_title',
            ]
        );

        foreach ($query['values'] as $status) {
            $list[$status['id']] = $status['msg_title'];
        }

        return $list;
    }

    /**
     * Get a list of the available/allowed sender email addresses
     */
    protected function getSenderOptions() {
        $dropdown_list = [];
        $from_email_addresses = CRM_Core_OptionGroup::values('from_email_address');
        foreach ($from_email_addresses as $key => $from_email_address) {
            $dropdown_list[$key] = htmlentities($from_email_address);
        }
        return $dropdown_list;
    }

    /**
     * get the different batch sizes
     *
     * @return array
     *   batch size options
     */
    private function getBatchSizes() {
        return [
            '10'  => E::ts("%1 E-Mails per Batch", [1 => 10]),
            '25'  => E::ts("%1 E-Mails per Batch", [1 => 25]),
            '50'  => E::ts("%1 E-Mails per Batch", [1 => 50]),
            '100' => E::ts("%1 E-Mails per Batch", [1 => 100]),
//            '150' => E::ts("%1 E-Mails per Batch", [1 => 150]),
//            '250' => E::ts("%1 E-Mails per Batch", [1 => 250]),
        ];
    }

    /**
     * Get a list of activity types
     */
    private function getActivityTypes()
    {
        $types = ['' => E::ts("--disabled--")];
        $query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'activity_type',
            'is_reserved'     => 0,
            'component_id'    => ['IS NULL' => 1],
            'option.limit'    => 0,
            'return'          => 'label,value'
        ]);
        foreach ($query['values'] as $type) {
            $types[$type['value']] = $type['label'];
        }
        return $types;
    }

    /**
     * Get the number of contacts that
     *   do not have a viable email address
     */
    private function getNoEmailCount()
    {
        $contribution_id_list = implode(',', $this->_contributionIds);
        $EMAIL_SELECTOR_CRITERIA = $this->getSQLEmailSelectorCriteria();
        return CRM_Core_DAO::singleValueQuery("
            SELECT COUNT(DISTINCT(contribution.id))
            FROM civicrm_contribution contribution
            LEFT JOIN civicrm_contact contact
                   ON contact.id = contribution.contact_id
            LEFT JOIN civicrm_email email
                   ON email.contact_id = contact.id
                   AND {$EMAIL_SELECTOR_CRITERIA}
                   AND email.on_hold = 0
            WHERE contribution.id IN ({$contribution_id_list})
              AND email.id IS NULL");
    }

    /**
     * Get the number of contacts belong to the selected emails
     */
    private function getContactCount()
    {
        $contribution_id_list = implode(',', $this->_contributionIds);
        return CRM_Core_DAO::singleValueQuery("
            SELECT COUNT(DISTINCT(contact.id))
            FROM civicrm_contribution contribution
            LEFT JOIN civicrm_contact contact
                   ON contact.id = contribution.contact_id
            WHERE contribution.id IN ({$contribution_id_list})");
    }

    /**
     * Get the number of contacts that
     *   do not have a viable email address
     */
    private function getNoEmailContacts()
    {
        $contacts_without_email = [];
        $EMAIL_SELECTOR_CRITERIA = $this->getSQLEmailSelectorCriteria();
        $contribution_id_list = implode(',', $this->_contributionIds);
        $contact_query = CRM_Core_DAO::executeQuery("
            SELECT COUNT(DISTINCT(contact.id)) AS contact_id
            FROM civicrm_contribution contribution
            LEFT JOIN civicrm_contact contact
                   ON contact.id = contribution.contact_id
            INNER JOIN civicrm_email email
                   ON email.contact_id = contact.id
                   AND {$EMAIL_SELECTOR_CRITERIA}
                   AND email.on_hold = 0
            WHERE contribution.id IN ({$contribution_id_list})
              AND email.id IS NULL");
        while ($contact_query->fetch()) {
            $contacts_without_email[] = (int) $contact_query->contact_id;
        }
        return $contacts_without_email;
    }

    /**
     * Create failed activities for contacts without valid email
     *
     * @param integer $activity_type_id
     *   activity type
     *
     * @param string $activity_subject
     *   subject of the activity
     *
     * @param boolean $activity_grouped
     *   one activity for all contacts?
     *
     * @param array $assignees
     *   list of assignees
     *
     */
    protected function createNoEmailActivities($activity_type_id, $activity_subject, $activity_grouped, $assignees)
    {
        $contacts_without_email = $this->getNoEmailContacts();
        if (!empty($activity_grouped)) {
            // create one grouped activity:
            CRM_Mailbatch_SendMailJob::createActivity(
                $activity_type_id,
                $activity_subject,
                CRM_Core_Session::getLoggedInContactID(),
                $contacts_without_email,
                'Completed',
                null,
                $assignees
            );
        } else {
            // create individual activities
            foreach ($contacts_without_email as $contact_id) {
                CRM_Mailbatch_SendMailJob::createActivity(
                    $activity_type_id,
                    $activity_subject,
                    CRM_Core_Session::getLoggedInContactID(),
                    [$contact_id],
                    'Completed',
                    null,
                    $assignees
                );
            }
        }
    }

    /**
     * get the list of email types
     *
     * @return array
     *  list of id => display name
     */
    private function getEmailTypes()
    {
        $location_types = [
            'P' => E::ts("primary"),
            'B' => E::ts("billing (flag)"),
        ];

        // add the specific ones
        $system_location_type_query = civicrm_api3('LocationType', 'get', [
            'option.limit' => 0,
            'is_active'    => 1,
            'sequential'   => 0,
            'return'       => 'id,display_name',
        ]);
        foreach ($system_location_type_query['values'] as $location_type) {
            $location_types[$location_type['id']] = $location_type['display_name'];
        }

        return $location_types;
    }

    /**
     * Generate the selective term for the email
     *
     * @param string $table_name
     *   the table name currently used for the email entity
     *
     * @return string
     *   a (safe) SQL clause (as long as $table_name is safe)
     */
    private function getSQLEmailSelectorCriteria($table_name = "email")
    {
        $location_type_id = CRM_Utils_Array::value('location_type_id', $this->_submitValues);
        switch ($location_type_id) {
            case 'P': // primary
                return "{$table_name}.is_primary";

            case 'B': // billing
                return "{$table_name}.is_billing";

            default:  // location type
                $location_type_id = (int) $location_type_id;
                return "{$table_name}.location_type_id = {$location_type_id}";
        }
    }
}

