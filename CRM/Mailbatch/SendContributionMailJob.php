<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2021 SYSTOPIA                            |
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

use CRM_Mailbatch_ExtensionUtil as E;

/**
 * Queue item for sending emails to contribution contacts
 */
class CRM_Mailbatch_SendContributionMailJob extends CRM_Mailbatch_SendMailJob
{
    const CONTRIBUTION_ID = 0;
    const CONTACT_ID      = 1;
    const EMAIL           = 2;

    /** @var array list of tuples (contribution_id, contact_id, email) */
    protected $contribution_contact_email_tuples;

    public function __construct($contribution_contact_email_tuples, $config, $title)
    {
        $this->contribution_contact_email_tuples = $contribution_contact_email_tuples;
        parent::__construct(null, $config, $title);
    }

    /**
     * Execute the batch of emails to be sent
     * @return true
     */
    public function run(): bool
    {
        if (!empty($this->contribution_contact_email_tuples)) {
            // load the relevant contacts
            $contact_ids = [];
            foreach ($this->contribution_contact_email_tuples as $contact_email_tuple) {
                $contact_ids[] = $contact_email_tuple[self::CONTACT_ID];
            }
            $contact_ids = array_unique($contact_ids);

            // load the contacts' names
            $contacts = civicrm_api3('Contact', 'get', [
                'id'           => ['IN' => $contact_ids],
                'return'       => 'id,display_name',
                'option.limit' => 0,
                'sequential'   => 0,
            ])['values'];

            // get sender
            $from_addresses = CRM_Core_OptionGroup::values('from_email_address');
            if (isset($from_addresses[$this->config['sender_email']])) {
                $sender = $from_addresses[$this->config['sender_email']];
            } else {
                $sender = reset($from_addresses);
            }

            // trigger sendMessageTo for each one of them
            $mail_successfully_sent = [];
            $mail_sending_failed = [];
            if (trait_exists('Civi\Mailattachment\Form\Task\AttachmentsTrait')) {
                $attachment_types = \Civi\Mailattachment\Form\Task\AttachmentsTrait::attachmentTypes();
                // TODO: Pre-cache attachments for all contacts in the batch, wrapped in try...catch.
//                foreach ($this->config['attachments'] as $attachment_id => $attachment_values) {
//                  $attachment_type['controller']::preCacheAttachments(['contacts' => $contacts['values']], $attachment_values)
//                }
            }
            foreach ($this->contribution_contact_email_tuples as $contact_email_tuple) {
                try {
                    // unpack the values
                    [$contribution_id, $contact_id, $email] = $contact_email_tuple;
                    $contact = $contacts[$contact_id];

                    // send email
                    $email_data = [
                        'id'                => (int) $this->config['template_id'],
                        'messageTemplateID' => (int) $this->config['template_id'],
                        'toName'            => $contact['display_name'],
                        'toEmail'           => $email,
                        'from'              => $sender,
                        'replyTo'           => CRM_Utils_Array::value('sender_reply_to', $this->config, ''),
                        'cc'                => CRM_Utils_Array::value('sender_cc', $this->config, ''),
                        'bcc'               => CRM_Utils_Array::value('sender_bcc', $this->config, ''),
                        'contactId'         => $contact_id,
                        'tplParams'         => [
                            'contact_id'      => $contact_id,
                            'contribution_id' => $contribution_id
                        ],
                    ];

                    // Add attachments.
                    if (trait_exists('Civi\Mailattachment\Form\Task\AttachmentsTrait')) {
                        foreach ($this->config['attachments'] as $attachment_id => $attachment_values) {
                            $attachment_type = $attachment_types[$attachment_values['type']];
                            /* @var \Civi\Mailattachment\AttachmentType\AttachmentTypeInterface $controller */
                            $controller = $attachment_type['controller'];
                            if (
                                !($attachment = $controller::buildAttachment(
                                    [
                                        'entity_type' => 'contribution',
                                        'entity_id' => $contribution_id,
                                        'entity_ids' => array_column($this->contribution_contact_email_tuples, 0),
                                        'extra' => ['contact_id' => $contact_id],
                                    ],
                                    $attachment_values)
                                )
                                && empty($this->config['send_wo_attachment'])
                            ) {
                                // no attachment -> cannot send
                                throw new Exception(
                                    E::ts("Attachment '%1' could not be generated or found.", [
                                        1 => $attachment_id,
                                    ])
                                );
                            }
                            $email_data['attachments'][] = $attachment;
                        }
                    }

                    // send email
                    civicrm_api3('MessageTemplate', 'send', $email_data);

                    // mark as success
                    $mail_successfully_sent[] = $contribution_id;

                } catch (Exception $exception) {
                    // this shouldn't happen, sendMessageTo has it's own error handling
                    $mail_sending_failed[] = $contribution_id;
                    $this->errors[$contribution_id] = $exception->getMessage();
                }
            }

            // create activities
            if (!empty($mail_successfully_sent) && !empty($this->config['sent_activity_type_id'])) {
                $mail_successfully_sent_contacts = $this->getContactIdsFromContributions($mail_successfully_sent);

                if (!empty($this->config['activity_grouped'])) {
                    // create one grouped activity with all contacts
                    self::createActivity(
                        $this->config['sent_activity_type_id'],
                        $this->config['sent_activity_subject'],
                        $this->config['sender_contact_id'],
                        $mail_successfully_sent_contacts,
                        'Completed'
                    );
                } else {
                    // create individual activities per contribution
                    foreach ($mail_successfully_sent as $contribution_id) {
                        $contact_id = $this->getContactIdFromContribution($contribution_id);
                        self::createActivity(
                            $this->config['sent_activity_type_id'],
                            $this->config['sent_activity_subject'],
                            $this->config['sender_contact_id'],
                            [$contact_id],
                            'Completed',
                            null,
                            '',
                            $contribution_id
                        );
                    }
                }
            }

            if (!empty($mail_sending_failed) && !empty($this->config['failed_activity_type_id'])) {
                // render list of errors
                $details = E::ts("<p>The following errors occurred (with contact/contribution IDs):<ul>");
                foreach ($this->errors as $contribution_id => $error) {
                    $contact_id = $this->getContactIdFromContribution($contribution_id);
                    $details.= E::ts("<li>Contribution [%1] (Contact [%2]): %3</li>", [
                        1 => $contribution_id,
                        2 => $contact_id,
                        3 => $error]);
                }
                $details.= "</ul></p>";

                if (!empty($this->config['activity_grouped'])) {
                    // create one grouped activity:
                    self::createActivity(
                        $this->config['failed_activity_type_id'],
                        $this->config['failed_activity_subject'],
                        $this->config['sender_contact_id'],
                        $mail_sending_failed,
                        'Scheduled',
                        $details,
                        $this->config['failed_activity_assignee']
                    );
                } else {
                    // create individual activities
                    foreach ($mail_sending_failed as $contribution_id) {
                        $contact_id = $this->getContactIdFromContribution($contribution_id);
                        self::createActivity(
                            $this->config['failed_activity_type_id'],
                            $this->config['failed_activity_subject'],
                            $this->config['sender_contact_id'],
                            [$contact_id],
                            'Scheduled',
                            E::ts("Error was: %1", [1 => $this->errors[$contribution_id]]),
                            $this->config['failed_activity_assignee']
                        );
                    }
                }
            }

        }
        return true;
    }

    /**
     * Get the distinct contact IDs of the contacts by the given contributions
     *
     * @param array $contribution_ids
     *   list of contribution IDs
     *
     * @return array
     *   list of contact IDs
     */
    protected function getContactIdsFromContributions($contribution_ids)
    {
        $contact_ids = [];
        foreach ($contribution_ids as $contribution_id) {
            $contact_ids[] = $this->getContactIdFromContribution($contribution_id);
        }
        return array_unique($contact_ids);
    }

    /**
     * Get the contact ID of the contact assigned to the given contribution
     *
     * @param integer $contribution_id
     *   contribution ID
     *
     * @return integer
     *   contact ID
     */
    protected function getContactIdFromContribution($contribution_id)
    {
        $contribution_id = (int) $contribution_id;
        if (empty($contribution_id)) return null;

        // get from the given list
        foreach ($this->contribution_contact_email_tuples as $contact_email_tuple) {
            if ($contact_email_tuple[self::CONTRIBUTION_ID] == $contribution_id) {
                return $contact_email_tuple[self::CONTACT_ID];
            }
        }

        // we shouldn't even get here...
        return CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM civicrm_contribution WHERE id = {$contribution_id}");
    }
}