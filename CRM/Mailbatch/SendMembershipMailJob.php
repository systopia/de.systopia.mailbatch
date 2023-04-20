<?php
/*-------------------------------------------------------+
| SYSTOPIA MailBatch Extension                           |
| Copyright (C) 2023 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
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
 * Queue item for sending emails to membership contacts.
 */
class CRM_Mailbatch_SendMembershipMailJob extends CRM_Mailbatch_SendMailJob {

  const MEMBERSHIP_ID = 0;

  const CONTACT_ID = 1;

  const EMAIL = 2;

  /** @var array list of tuples (membership_id, contact_id, email) */
  protected $membership_contact_email_tuples;

  public function __construct($membership_contact_email_tuples, $config, $title) {
    $this->membership_contact_email_tuples = $membership_contact_email_tuples;
    parent::__construct(NULL, $config, $title);
  }

  /**
   * Executes the batch of e-mail to be sent.
   *
   * @return true
   */
  public function run(): bool {
    if (!empty($this->membership_contact_email_tuples)) {
      // Load the relevant contacts.
      $contact_ids = [];
      foreach ($this->membership_contact_email_tuples as $contact_email_tuple) {
        $contact_ids[] = $contact_email_tuple[self::CONTACT_ID];
      }
      $contact_ids = array_unique($contact_ids);

      // Load the contacts' names.
      $contacts = civicrm_api3('Contact', 'get', [
        'id' => ['IN' => $contact_ids],
        'return' => 'id,display_name',
        'option.limit' => 0,
        'sequential' => 0,
      ])['values'];

      // Get sender.
      $from_addresses = CRM_Core_OptionGroup::values('from_email_address');
      if (isset($from_addresses[$this->config['sender_email']])) {
        $sender = $from_addresses[$this->config['sender_email']];
      }
      else {
        $sender = reset($from_addresses);
      }

      // Trigger sendMessageTo() for each one of them.
      $mail_successfully_sent = [];
      $mail_sending_failed = [];
      if (class_exists('Civi\Mailattachment\Form\Attachments')) {
        $attachment_types = \Civi\Mailattachment\Form\Attachments::attachmentTypes();
        // TODO: Pre-cache attachments for all contacts in the batch, wrapped in try...catch.
        //                foreach ($this->config['attachments'] as $attachment_id => $attachment_values) {
        //                  $attachment_type['controller']::preCacheAttachments(['contacts' => $contacts['values']], $attachment_values)
        //                }
      }
      foreach ($this->membership_contact_email_tuples as $contact_email_tuple) {
        try {
          // Unpack the values.
          [$membership_id, $contact_id, $email] = $contact_email_tuple;
          $contact = $contacts[$contact_id];

          // Send e-mail.
          $email_data = [
            'id' => (int) $this->config['template_id'],
            'messageTemplateID' => (int) $this->config['template_id'],
            'toName' => $contact['display_name'],
            'toEmail' => $email,
            'from' => $sender,
            'replyTo' => CRM_Utils_Array::value('sender_reply_to', $this->config, ''),
            'cc' => CRM_Utils_Array::value('sender_cc', $this->config, ''),
            'bcc' => CRM_Utils_Array::value('sender_bcc', $this->config, ''),
            'contactId' => $contact_id,
            'tplParams' => [
              'contact_id' => $contact_id,
              'membership_id' => $membership_id,
            ],
          ];

          // Add attachments.
          if (class_exists('Civi\Mailattachment\Form\Attachments')) {
            foreach ($this->config['attachments'] as $attachment_id => $attachment_values) {
              $attachment_type = $attachment_types[$attachment_values['type']];
              /* @var \Civi\Mailattachment\AttachmentType\AttachmentTypeInterface $controller */
              $controller = $attachment_type['controller'];
              if (
                !($attachment = $controller::buildAttachment(
                  [
                    'entity_type' => 'membership',
                    'entity_id' => $membership_id,
                    'entity_ids' => array_column($this->membership_contact_email_tuples, 0),
                    'extra' => ['contact_id' => $contact_id],
                  ],
                  $attachment_values)
                )
                && empty($this->config['send_wo_attachment'])
              ) {
                // No attachment -> cannot send.
                throw new Exception(
                  E::ts("Attachment '%1' could not be generated or found.", [
                    1 => $attachment_id,
                  ])
                );
              }
              $email_data['attachments'][] = $attachment;
            }
          }

          // Send e-mail.
          civicrm_api3('MessageTemplate', 'send', $email_data);

          // Mark as success.
          $mail_successfully_sent[] = $membership_id;

        } catch (Exception $exception) {
          // This shouldn't happen, sendMessageTo has its own error handling.
          $mail_sending_failed[] = $membership_id;
          $this->errors[$membership_id] = $exception->getMessage();
        }
      }

      // Create activities.
      if (!empty($mail_successfully_sent) && !empty($this->config['sent_activity_type_id'])) {
        $mail_successfully_sent_contacts = $this->getContactIdsFromMemberships($mail_successfully_sent);

        if (!empty($this->config['activity_grouped'])) {
          // Create one grouped activity with all contacts.
          self::createActivity(
            $this->config['sent_activity_type_id'],
            $this->config['sent_activity_subject'],
            $this->config['sender_contact_id'],
            $mail_successfully_sent_contacts,
            'Completed'
          );
        }
        else {
          // Create individual activities per membership.
          foreach ($mail_successfully_sent as $membership_id) {
            $contact_id = $this->getContactIdFromMembership($membership_id);
            self::createActivity(
              $this->config['sent_activity_type_id'],
              $this->config['sent_activity_subject'],
              $this->config['sender_contact_id'],
              [$contact_id],
              'Completed',
              NULL,
              '',
              $membership_id
            );
          }
        }
      }

      if (!empty($mail_sending_failed) && !empty($this->config['failed_activity_type_id'])) {
        // Render list of errors.
        $details = '<p>' . E::ts("The following errors occurred (with contact/membership IDs):") . '</p><ul>';
        foreach ($this->errors as $membership_id => $error) {
          $contact_id = $this->getContactIdFromMembership($membership_id);
          $details .= '<li>'
            . E::ts("Membership [%1] (Contact [%2]): %3", [
              1 => $membership_id,
              2 => $contact_id,
              3 => $error,
            ])
            . '</li>';
        }
        $details .= "</ul></p>";

        if (!empty($this->config['activity_grouped'])) {
          // Create one grouped activity.
          self::createActivity(
            $this->config['failed_activity_type_id'],
            $this->config['failed_activity_subject'],
            $this->config['sender_contact_id'],
            $mail_sending_failed,
            'Scheduled',
            $details,
            $this->config['failed_activity_assignee']
          );
        }
        else {
          // Create individual activities.
          foreach ($mail_sending_failed as $membership_id) {
            $contact_id = $this->getContactIdFromMembership($membership_id);
            self::createActivity(
              $this->config['failed_activity_type_id'],
              $this->config['failed_activity_subject'],
              $this->config['sender_contact_id'],
              [$contact_id],
              'Scheduled',
              E::ts("Error was: %1", [1 => $this->errors[$membership_id]]),
              $this->config['failed_activity_assignee']
            );
          }
        }
      }

    }
    return TRUE;
  }

  /**
   * Retrieves the distinct contact IDs of the contacts by the given
   * memberships.
   *
   * @param array $membership_ids
   *   A list of membership IDs.
   *
   * @return array
   *   A list of contact IDs.
   */
  protected function getContactIdsFromMemberships($membership_ids) {
    $contact_ids = [];
    foreach ($membership_ids as $membership_id) {
      $contact_ids[] = $this->getContactIdFromMembership($membership_id);
    }
    return array_unique($contact_ids);
  }

  /**
   * Retrieves the contact ID of the contact assigned to the given membership
   *
   * @param int $membership_id
   *   A membership ID
   *
   * @return int
   *   The contact ID.
   */
  protected function getContactIdFromMembership($membership_id) {
    $membership_id = (int) $membership_id;
    if (empty($membership_id)) {
      return NULL;
    }

    // Get from the given list.
    foreach ($this->membership_contact_email_tuples as $contact_email_tuple) {
      if ($contact_email_tuple[self::MEMBERSHIP_ID] == $membership_id) {
        return $contact_email_tuple[self::CONTACT_ID];
      }
    }

    // We shouldn't even get here.
    return CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM civicrm_membership WHERE id = {$membership_id}");
  }
}
