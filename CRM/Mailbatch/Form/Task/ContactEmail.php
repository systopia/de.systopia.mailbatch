<?php
/*-------------------------------------------------------+
| SYSTOPIA MailBatch Extension                           |
| Copyright (C) 2020 SYSTOPIA                            |
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
 * Send E-Mail to contacts task
 */
class CRM_Mailbatch_Form_Task_ContactEmail extends CRM_Contact_Form_Task
{
    /**
     * Compile task form
     */
    function buildQuickForm()
    {
        $contact_count = count($this->_contactIds);

        // now build the form
        CRM_Utils_System::setTitle(E::ts('Send Email to %1 Contacts', [1 => $contact_count]));

        // calculate and add the number of contacts with no valid E-Mail
        $no_email_count = $this->getNoEmailCount();
        $this->assign('no_email_count', $no_email_count);

        $this->add(
            'select',
            'template_id',
            E::ts('Message Template'),
            $this->getMessageTemplates(),
            true,
            ['class' => 'crm-select2 huge']
        );

//        $this->add(
//            'checkbox',
//            'enable_smarty',
//            E::ts('Enable SMARTY for this template')
//        );

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

        if (class_exists('Civi\Mailattachment\Form\Attachments')) {
            $this->add(
                'checkbox',
                'send_wo_attachment',
                E::ts('Send if attachment not found?')
            );

            \Civi\Mailattachment\Form\Attachments::addAttachmentElements($this, ['entity_type' => 'contact']);
        }

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
        $defaults = [
            'template_id'              => Civi::settings()->get('batchmail_template_id'),
            'batch_size'               => Civi::settings()->get('batchmail_batch_size'),
            'sender_email'             => Civi::settings()->get('batchmail_sender_email'),
            'sender_cc'                => Civi::settings()->get('batchmail_sender_cc'),
            'sender_bcc'               => Civi::settings()->get('batchmail_sender_bcc'),
            'sender_reply_to'          => Civi::settings()->get('batchmail_sender_reply_to'),
            //'enable_smarty'            => Civi::settings()->get('batchmail_enable_smarty'),
            'sent_activity_type_id'    => Civi::settings()->get('batchmail_sent_activity_type_id'),
            'sent_activity_grouped'    => Civi::settings()->get('batchmail_sent_activity_grouped'),
            'sent_activity_subject'    => Civi::settings()->get('batchmail_sent_activity_subject'),
            'failed_activity_type_id'  => Civi::settings()->get('batchmail_failed_activity_type_id'),
            'failed_activity_subject'  => Civi::settings()->get('batchmail_failed_activity_subject'),
            'failed_activity_subject2' => Civi::settings()->get('batchmail_failed_activity_subject2'),
            'failed_activity_assignee' => Civi::settings()->get('batchmail_failed_activity_assignee'),
        ];
        if (class_exists('Civi\Mailattachment\Form\Attachments')) {
            $defaults['send_wo_attachment'] = Civi::settings()->get('batchmail_send_wo_attachment');
            // TODO: Set default values for attachments?
        }
        $this->setDefaults($defaults);

        CRM_Core_Form::addDefaultButtons(E::ts("Send %1 Emails", [1 => $contact_count - $no_email_count]));
    }


    function postProcess()
    {
        $values = $this->exportValues();
        $values['sender_contact_id'] = CRM_Core_Session::getLoggedInContactID();
        $no_email_count = $this->getNoEmailCount();
        $contact_count = count($this->_contactIds) - $no_email_count;

        // store default values
        // TODO: Use contactSettings().
        Civi::settings()->set('batchmail_template_id', $values['template_id']);
        Civi::settings()->set('batchmail_sender_email', $values['sender_email']);
        Civi::settings()->set('batchmail_batch_size', $values['batch_size']);
        Civi::settings()->set('batchmail_sender_cc', $values['sender_cc']);
        Civi::settings()->set('batchmail_sender_bcc', $values['sender_bcc']);
        Civi::settings()->set('batchmail_sender_reply_to', $values['sender_reply_to']);
        //Civi::settings()->set('batchmail_enable_smarty',            CRM_Utils_Array::value('enable_smarty', $values, 0));
        Civi::settings()->set('batchmail_sent_activity_type_id', $values['sent_activity_type_id']);
        Civi::settings()->set('batchmail_sent_activity_subject', $values['sent_activity_subject']);
        Civi::settings()->set('batchmail_failed_activity_type_id', $values['failed_activity_type_id']);
        Civi::settings()->set('batchmail_activity_grouped', $values['activity_grouped']);
        Civi::settings()->set('batchmail_failed_activity_subject', $values['failed_activity_subject']);
        Civi::settings()->set('batchmail_failed_activity_assignee', $values['failed_activity_assignee']);
        if (isset($values['failed_activity_subject2'])) {
            Civi::settings()->set('batchmail_failed_activity_subject2', $values['failed_activity_subject2']);
        }

        if (class_exists('Civi\Mailattachment\Form\Attachments')) {
            Civi::settings()->set('batchmail_send_wo_attachment', CRM_Utils_Array::value('send_wo_attachment', $values, 0));
            $values['attachments'] = \Civi\Mailattachment\Form\Attachments::processAttachments($this);
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
            'name' => 'mailbatch_email_task_' . CRM_Core_Session::singleton()->getLoggedInContactID(),
            'reset' => true,
        ]);
        // add a dummy item to display the 'upcoming' message
        $queue->createItem(new CRM_Mailbatch_SendMailJob(
            [],
            $values['template_id'],
            E::ts("Sending Emails %1 - %2", [
                1 => 1, // keep in mind that this is showing when the _next_ task is running
                2 => min($values['batch_size'], $contact_count)])
        ));

        // run query to get all contacts
        $contact_id_list = implode(',', $this->_contactIds);
        $contact_query = CRM_Core_DAO::executeQuery("
            SELECT contact.id AS contact_id
            FROM civicrm_contact contact
            LEFT JOIN civicrm_email email
                   ON email.contact_id = contact.id
                   AND email.is_primary = 1
                   AND email.on_hold = 0
            WHERE contact.id IN ({$contact_id_list})
              AND email.id IS NOT NULL");

        // batch the contacts into bite-sized jobs
        $current_batch = [];
        $next_offset = $values['batch_size'];
        while ($contact_query->fetch()) {
            $current_batch[] = $contact_query->contact_id;
            if (count($current_batch) >= $values['batch_size']) {
                $queue->createItem(
                    new CRM_Mailbatch_SendMailJob(
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
            new CRM_Mailbatch_SendMailJob(
                $current_batch,
                $values,
                E::ts("Finishing")
            )
        );

        // start a runner on the queue
        $runner = new CRM_Queue_Runner([
                'title'     => E::ts("Sending %1 Event Emails", [1 => $contact_count]),
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
            '150' => E::ts("%1 E-Mails per Batch", [1 => 150]),
            '250' => E::ts("%1 E-Mails per Batch", [1 => 250]),
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
        $contact_id_list = implode(',', $this->_contactIds);
        return CRM_Core_DAO::singleValueQuery("
            SELECT COUNT(DISTINCT(contact.id))
            FROM civicrm_contact contact
            LEFT JOIN civicrm_email email
                   ON email.contact_id = contact.id
                   AND email.is_primary = 1
                   AND email.on_hold = 0
            WHERE contact.id IN ({$contact_id_list})
              AND email.id IS NULL");
    }

    /**
     * Get the number of contacts that
     *   do not have a viable email address
     */
    private function getNoEmailContacts()
    {
        $contacts_without_email = [];
        $full_contact_id_list = implode(',', $this->_contactIds);
        $contact_query = CRM_Core_DAO::executeQuery("
            SELECT DISTINCT(contact.id) contact_id
            FROM civicrm_contact contact
            LEFT JOIN civicrm_email email
                   ON email.contact_id = contact.id
                   AND email.is_primary = 1
                   AND email.on_hold = 0
            WHERE contact.id IN ({$full_contact_id_list})
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
}

