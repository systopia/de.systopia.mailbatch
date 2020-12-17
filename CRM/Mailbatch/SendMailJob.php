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

use CRM_Mailbatch_ExtensionUtil as E;

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

    /** @var array errors by contact ID */
    protected $errors;

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
                'return'       => 'id,display_name,email',
                'option.limit' => 0,
            ]);

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
            foreach ($contacts['values'] as $contact) {
                try {
                    // send email
                    $email_data = [
                        'id'        => $this->config['template_id'],
                        'toName'    => $contact['display_name'],
                        'toEmail'   => $contact['email'],
                        'from'      => $sender,
                        'replyTo'   => CRM_Utils_Array::value('sender_reply_to', $this->config, ''),
                        'cc'        => CRM_Utils_Array::value('sender_cc', $this->config, ''),
                        'bcc'       => CRM_Utils_Array::value('sender_bcc', $this->config, ''),
                        'contactId' => $contact['id'],
                    ];

                    // add attachments
                    $attachments = [];
                    $attachment_file = $this->findAttachmentFile($contact['id'], 1);
                    if ($attachment_file) {
                        $file_name = empty($this->config['attachment1_name']) ? basename($attachment_file) : $this->config['attachment1_name'];
                        $attachments[] = [
                            'fullPath'  => $attachment_file,
                            'mime_type' => $this->getMimeType($attachment_file),
                            'cleanName' => $file_name,
                        ];
                        $email_data['attachments'] = $attachments;

                    } elseif (empty($this->config['send_wo_attachment'])) {
                        // no attachment -> cannot send
                        throw new Exception(E::ts("Attachment '%1' not found.", [
                            1 => $this->config['attachment1_path']]));
                    }

                    // send email
                    civicrm_api3('MessageTemplate', 'send', $email_data);

                    // mark as success
                    $mail_successfully_sent[] = $contact['id'];

                } catch (Exception $exception) {
                    // this shouldn't happen, sendMessageTo has it's own error handling
                    $mail_sending_failed[] = $contact['id'];
                    $this->errors[$contact['id']] = $exception->getMessage();
                }
            }

            // create activities
            if (!empty($mail_successfully_sent) && !empty($this->config['sent_activity_type_id'])) {
                $this->createActivity(
                    $this->config['sent_activity_type_id'],
                    $this->config['sent_activity_subject'],
                    $this->config['sender_contact_id'],
                    $mail_successfully_sent,
                    'Completed'
                );
            }

            if (!empty($mail_sending_failed) && !empty($this->config['failed_activity_type_id'])) {
                // render list of errors
                $error_to_contact_id = [];
                foreach ($this->errors as $contact_id => $error) {
                    $error_to_contact_id[$error][] = $contact_id;
                }
                $details = E::ts("<p>The following errors occurred (with contact IDs):<ul>");
                foreach ($error_to_contact_id as $error => $contact_ids) {
                    $contact_id_list = implode(',', $contact_ids);
                    $details.= E::ts("<li>%1 (%2)</li>", [1 => $error, 2 => $contact_id_list]);
                }
                $details.= "</ul></p>";

                $this->createActivity(
                    $this->config['failed_activity_type_id'],
                    $this->config['failed_activity_subject'],
                    $this->config['sender_contact_id'],
                    $mail_sending_failed,
                    'Scheduled',
                    $details
                );
            }

        }
        return true;
    }

    /**
     * Create an activity
     *
     * @param integer $activity_type_id
     * @param string $subject
     * @param string $status
     * @param string $details
     * @param integer $sender_contact_id
     * @param array $target_contact_ids
     */
    protected function createActivity($activity_type_id, $subject, $sender_contact_id, $target_contact_ids, $status, $details = null)
    {
        civicrm_api3('Activity', 'create', [
            'activity_type_id'  => $activity_type_id,
            'status_id'         => $status,
            'source_contact_id' => $sender_contact_id,
            'target_contact_id' => $target_contact_ids,
            'subject'           => $subject,
            'details'           => $details,
        ]);
    }

    /**
     * Try to find the attachment #{$index} based on the file path
     *   and the contact
     *
     * @param integer $contact_id
     *   contact ID
     *
     * @param integer $index
     *   index
     *
     * @return string|null
     *   full file path or null
     */
    protected function findAttachmentFile($contact_id, $index = 1)
    {
        if (!empty($this->config["attachment{$index}_path"])) {
            $path = $this->config["attachment{$index}_path"];
            // replace {contact_id} token
            $path = preg_replace('/[{]contact_id[}]/', $contact_id, $path);
            if (is_readable($path) && !is_dir($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * get the mime type of the given file
     *
     * @param string $path
     * @return string mime type
     */
    protected function getMimeType($path)
    {
        static $known_files = [];
        if (!isset($known_files[$path])) {
            $known_files[$path] = mime_content_type($path);
        }
        return $known_files[$path];
    }
}