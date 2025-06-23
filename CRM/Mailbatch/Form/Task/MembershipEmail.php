<?php
/*-------------------------------------------------------+
| SYSTOPIA MailBatch Extension                           |
| Copyright (C) 2023 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
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

use Civi\Mailbatch\MailUtils;
use CRM_Mailbatch_ExtensionUtil as E;

/**
 * Send E-Mail to contacts based on membership task
 */
class CRM_Mailbatch_Form_Task_MembershipEmail extends CRM_Member_Form_Task {

  /**
   * {@inheritDoc}
   */
  function buildQuickForm() {
    $membership_count = count($this->_memberIds);
    $contact_count = $this->getContactCount();

    // now build the form
    CRM_Utils_System::setTitle(E::ts('Send %1 Email(s) to %2 Contact(s)', [
      1 => $membership_count,
      2 => $contact_count,
    ]));

    $this->add(
      'select',
      'template_id',
      E::ts('Message Template'),
      $this->getMessageTemplates(),
      TRUE,
      ['class' => 'crm-select2 huge']
    );

    $this->add(
      'select',
      'sender_email',
      E::ts('Sender'),
      MailUtils::getSenderEmails(),
      TRUE,
      ['class' => 'crm-select2 huge']
    );

    $this->add(
      'text',
      'sender_cc',
      E::ts('CC'),
      ['class' => 'huge'],
      FALSE
    );

    $this->add(
      'text',
      'sender_bcc',
      E::ts('BCC'),
      ['class' => 'huge'],
      FALSE
    );

    $this->add(
      'text',
      'sender_reply_to',
      E::ts('Reply-To'),
      ['class' => 'huge'],
      FALSE
    );

    $this->add(
      'select',
      'batch_size',
      E::ts('Batch Size'),
      $this->getBatchSizes(),
      TRUE,
      ['class' => 'crm-select2']
    );

    $this->add(
      'select',
      'location_type_id',
      E::ts('E-Mail Type'),
      $this->getEmailTypes(),
      FALSE,
      ['class' => 'crm-select2']
    );

    if (class_exists('Civi\Mailattachment\Form\Attachments')) {
      $this->add(
        'checkbox',
        'send_wo_attachment',
        E::ts('Send if attachment not found?')
      );

      \Civi\Mailattachment\Form\Attachments::addAttachmentElements($this, ['entity_type' => 'membership']);
    }

    $activity_types = $this->getActivityTypes();
    $this->add(
      'select',
      'sent_activity_type_id',
      E::ts('Activity (when sent)'),
      $activity_types,
      FALSE,
      ['class' => 'huge']
    );

    $this->add(
      'text',
      'sent_activity_subject',
      E::ts('Activity Subject'),
      ['class' => 'huge'],
      FALSE
    );

    $this->add(
      'select',
      'failed_activity_type_id',
      E::ts('Activity (when failed)'),
      $activity_types,
      FALSE,
      ['class' => 'huge']
    );

    $this->add(
      'select',
      'activity_grouped',
      E::ts('Activity Style'),
      [0 => E::ts("Individual"), 1 => E::ts("Grouped")],
      FALSE,
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
        'api' => ['params' => ['is_deceased' => 0]],
      ],
      FALSE
    );

    // set default values
    $defaults = [
      'template_id' => Civi::settings()->get('batchmail_template_id'),
      'batch_size' => Civi::settings()->get('batchmail_batch_size'),
      'sender_email' => Civi::settings()->get('batchmail_sender_email'),
      'sender_cc' => Civi::settings()->get('batchmail_sender_cc'),
      'sender_bcc' => Civi::settings()->get('batchmail_sender_bcc'),
      'sender_reply_to' => Civi::settings()
        ->get('batchmail_sender_reply_to'),
      'location_type_id' => Civi::settings()
        ->get('batchmail_location_type_id'),
      'sent_activity_type_id' => Civi::settings()
        ->get('batchmail_sent_activity_type_id'),
      'sent_activity_grouped' => Civi::settings()
        ->get('batchmail_sent_activity_grouped'),
      'sent_activity_subject' => Civi::settings()
        ->get('batchmail_sent_activity_subject'),
      'failed_activity_type_id' => Civi::settings()
        ->get('batchmail_failed_activity_type_id'),
      'failed_activity_subject' => Civi::settings()
        ->get('batchmail_failed_activity_subject'),
      'failed_activity_subject2' => Civi::settings()
        ->get('batchmail_failed_activity_subject2'),
      'failed_activity_assignee' => Civi::settings()
        ->get('batchmail_failed_activity_assignee'),
    ];
    if (class_exists('Civi\Mailattachment\Form\Attachments')) {
      $defaults['send_wo_attachment'] = Civi::settings()
        ->get('batchmail_send_wo_attachment');
      // TODO: Set default values for attachments?
    }
    $this->setDefaults($defaults);

    // calculate and add the number of contacts with no valid E-Mail
    if (!isset($this->_submitValues['location_type_id'])) {
      $this->_submitValues['location_type_id'] = Civi::settings()
        ->get('batchmail_location_type_id');
    }
    $no_email_count = $this->getNoEmailCount();
    $this->assign('no_email_count', $no_email_count);

    // add buttons
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts("Send %1 Emails", [1 => $membership_count - $no_email_count]),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'refresh',
        'name' => E::ts('Refresh'),
        'isDefault' => FALSE,
      ],
    ]);
  }

  /**
   * {@inheritDoc}
   */
  function postProcess() {
    $values = $this->exportValues();
    $values['sender_contact_id'] = CRM_Core_Session::getLoggedInContactID();
    $no_email_count = $this->getNoEmailCount();
    $membership_count = count($this->_memberIds) - $no_email_count;

    // store default values
    // TODO: Use contactSettings().
    Civi::settings()->set('batchmail_template_id', $values['template_id']);
    Civi::settings()->set('batchmail_sender_email', $values['sender_email']);
    Civi::settings()->set('batchmail_batch_size', $values['batch_size']);
    Civi::settings()->set('batchmail_sender_cc', $values['sender_cc']);
    Civi::settings()->set('batchmail_sender_bcc', $values['sender_bcc']);
    Civi::settings()
      ->set('batchmail_sender_reply_to', $values['sender_reply_to']);
    Civi::settings()
      ->set('batchmail_location_type_id', CRM_Utils_Array::value('location_type_id', $values, 0));
    Civi::settings()
      ->set('batchmail_sent_activity_type_id', $values['sent_activity_type_id']);
    Civi::settings()
      ->set('batchmail_sent_activity_subject', $values['sent_activity_subject']);
    Civi::settings()
      ->set('batchmail_failed_activity_type_id', $values['failed_activity_type_id']);
    Civi::settings()
      ->set('batchmail_activity_grouped', $values['activity_grouped']);
    Civi::settings()
      ->set('batchmail_failed_activity_subject', $values['failed_activity_subject']);
    Civi::settings()
      ->set('batchmail_failed_activity_assignee', $values['failed_activity_assignee']);
    if (isset($values['failed_activity_subject2'])) {
      Civi::settings()
        ->set('batchmail_failed_activity_subject2', $values['failed_activity_subject2']);
    }

    if (class_exists('Civi\Mailattachment\Form\Attachments')) {
      Civi::settings()
        ->set('batchmail_send_wo_attachment', CRM_Utils_Array::value('send_wo_attachment', $values, 0));
      $values['attachments'] = \Civi\Mailattachment\Form\Attachments::processAttachments($this);
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
      'name' => 'mailbatch_membership_email_task_' . CRM_Core_Session::singleton()
          ->getLoggedInContactID(),
      'reset' => TRUE,
    ]);
    // add a dummy item to display the 'upcoming' message
    $queue->createItem(new CRM_Mailbatch_SendMembershipMailJob(
      [],
      $values['template_id'],
      E::ts("Sending Emails %1 - %2", [
        1 => 1,
        // keep in mind that this is showing when the _next_ task is running
        2 => min($values['batch_size'], $membership_count),
      ])
    ));

    // run query to get all contacts
    $membership_list = implode(',', $this->_memberIds);
    $EMAIL_SELECTOR_CRITERIA = $this->getSQLEmailSelectorCriteria();
    CRM_Core_DAO::disableFullGroupByMode();
    $contact_query = CRM_Core_DAO::executeQuery("
            SELECT
                   membership.id AS membership_id,
                   contact.id      AS contact_id,
                   email.email     AS email
            FROM civicrm_membership membership
            LEFT JOIN civicrm_contact contact
                   ON contact.id = membership.contact_id
            LEFT JOIN civicrm_email email
                   ON email.contact_id = contact.id
                   AND {$EMAIL_SELECTOR_CRITERIA}
                   AND email.on_hold = 0
            WHERE membership.id IN ({$membership_list})
              AND email.id IS NOT NULL
            GROUP BY membership.id");
    CRM_Core_DAO::reenableFullGroupByMode();

    // batch the contacts into bite-sized jobs
    $current_batch = [];
    $next_offset = $values['batch_size'];
    while ($contact_query->fetch()) {
      $current_batch[] = [
        $contact_query->membership_id,
        $contact_query->contact_id,
        $contact_query->email,
      ];
      if (count($current_batch) >= $values['batch_size']) {
        $queue->createItem(
          new CRM_Mailbatch_SendMembershipMailJob(
            $current_batch,
            $values,
            E::ts("Sending Emails %1 - %2", [
              1 => $next_offset,
              // keep in mind that this is showing when the _next_ task is running
              2 => $next_offset + $values['batch_size'],
            ])
          )
        );
        $next_offset += $values['batch_size'];
        $current_batch = [];
      }
    }

    // add final runner
    $queue->createItem(
      new CRM_Mailbatch_SendMembershipMailJob(
        $current_batch,
        $values,
        E::ts("Finishing")
      )
    );

    // start a runner on the queue
    $runner = new CRM_Queue_Runner([
      'title' => E::ts("Sending %1 Event Emails", [1 => $membership_count]),
      'queue' => $queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
      'onEndUrl' => html_entity_decode(CRM_Core_Session::singleton()
        ->readUserContext()),
    ]);
    $runner->runAllViaWeb();
  }

  /**
   * Retrieves a list of eligible message templates.
   *
   * @return array
   *   A list of message templates with message template IDs as keys and their
   *   titles as values.
   */
  private function getMessageTemplates(): array {
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
   * Retrieves a list of batch sizes.
   *
   * @return array
   *   The list of available batch size options.
   */
  private function getBatchSizes() {
    return [
      '10' => E::ts("%1 E-Mails per Batch", [1 => 10]),
      '25' => E::ts("%1 E-Mails per Batch", [1 => 25]),
      '50' => E::ts("%1 E-Mails per Batch", [1 => 50]),
      '100' => E::ts("%1 E-Mails per Batch", [1 => 100]),
      //            '150' => E::ts("%1 E-Mails per Batch", [1 => 150]),
      //            '250' => E::ts("%1 E-Mails per Batch", [1 => 250]),
    ];
  }

  /**
   * Retrieves a list of activity types.
   */
  private function getActivityTypes() {
    $types = ['' => E::ts("--disabled--")];
    $query = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => 'activity_type',
      'is_reserved' => 0,
      'component_id' => ['IS NULL' => 1],
      'option.limit' => 0,
      'return' => 'label,value',
    ]);
    foreach ($query['values'] as $type) {
      $types[$type['value']] = $type['label'];
    }
    return $types;
  }

  /**
   * Retrieves the number of contacts wqithout a viable e-mail address.
   *
   * @return int
   *   The number of contacts without a viable e-mail address.
   */
  private function getNoEmailCount() {
    $membership_id_list = implode(',', $this->_memberIds);
    $EMAIL_SELECTOR_CRITERIA = $this->getSQLEmailSelectorCriteria();
    return (int) CRM_Core_DAO::singleValueQuery("
            SELECT COUNT(DISTINCT(membership.id))
            FROM civicrm_membership membership
            LEFT JOIN civicrm_contact contact
                   ON contact.id = membership.contact_id
            LEFT JOIN civicrm_email email
                   ON email.contact_id = contact.id
                   AND {$EMAIL_SELECTOR_CRITERIA}
                   AND email.on_hold = 0
            WHERE membership.id IN ({$membership_id_list})
              AND email.id IS NULL");
  }

  /**
   * Retrieves the distinct number of contacts for the selected memberships.
   */
  private function getContactCount() {
    $membership_id_list = implode(',', $this->_memberIds);
    return CRM_Core_DAO::singleValueQuery("
            SELECT COUNT(DISTINCT(contact.id))
            FROM civicrm_membership membership
            LEFT JOIN civicrm_contact contact
                   ON contact.id = membership.contact_id
            WHERE membership.id IN ({$membership_id_list})");
  }

  /**
   * Retrieves a list of contacts without a viable e-mail address.
   *
   * @return array
   *   A list of contact IDs.
   */
  private function getNoEmailContacts() {
    $contacts_without_email = [];
    $EMAIL_SELECTOR_CRITERIA = $this->getSQLEmailSelectorCriteria();
    $membership_id_list = implode(',', $this->_memberIds);
    $contact_query = CRM_Core_DAO::executeQuery("
            SELECT COUNT(DISTINCT(contact.id)) AS contact_id
            FROM civicrm_membership membership
            LEFT JOIN civicrm_contact contact
                   ON contact.id = membership.contact_id
            INNER JOIN civicrm_email email
                   ON email.contact_id = contact.id
                   AND {$EMAIL_SELECTOR_CRITERIA}
                   AND email.on_hold = 0
            WHERE membership.id IN ({$membership_id_list})
              AND email.id IS NULL");
    while ($contact_query->fetch()) {
      $contacts_without_email[] = (int) $contact_query->contact_id;
    }
    return $contacts_without_email;
  }

  /**
   * Creates failed activities for contacts without a valid e-mail address.
   *
   * @param integer $activity_type_id
   *   The activity type ID.
   *
   * @param string $activity_subject
   *   the subject of the activity.
   *
   * @param boolean $activity_grouped
   *   Whether to use one activity for all contacts.
   *
   * @param array $assignees
   *   A list of activityassignees.
   *
   */
  protected function createNoEmailActivities($activity_type_id, $activity_subject, $activity_grouped, $assignees) {
    $contacts_without_email = $this->getNoEmailContacts();
    if (!empty($activity_grouped)) {
      // create one grouped activity:
      CRM_Mailbatch_SendMailJob::createActivity(
        $activity_type_id,
        $activity_subject,
        CRM_Core_Session::getLoggedInContactID(),
        $contacts_without_email,
        'Completed',
        NULL,
        $assignees
      );
    }
    else {
      // create individual activities
      foreach ($contacts_without_email as $contact_id) {
        CRM_Mailbatch_SendMailJob::createActivity(
          $activity_type_id,
          $activity_subject,
          CRM_Core_Session::getLoggedInContactID(),
          [$contact_id],
          'Completed',
          NULL,
          $assignees
        );
      }
    }
  }

  /**
   * Retrieves the list of e-mail location types.
   *
   * @return array
   *   A list of e-mail location types with location type IDs as keys and their
   *   labels as values.
   */
  private function getEmailTypes() {
    $location_types = [
      'P' => E::ts("primary"),
      'B' => E::ts("billing (flag)"),
    ];

    // add the specific ones
    $system_location_type_query = civicrm_api3('LocationType', 'get', [
      'option.limit' => 0,
      'is_active' => 1,
      'sequential' => 0,
      'return' => 'id,display_name',
    ]);
    foreach ($system_location_type_query['values'] as $location_type) {
      $location_types[$location_type['id']] = $location_type['display_name'];
    }

    return $location_types;
  }

  /**
   * Generates SQL conditions for e-mail records.
   *
   * @param string $table_name
   *   The table name currently used for the email entity.
   *
   * @return string
   *   A (safe) SQL clause (as long as $table_name is safe).
   */
  private function getSQLEmailSelectorCriteria($table_name = "email") {
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
