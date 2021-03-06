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
            foreach ($this->contribution_contact_email_tuples as $contact_email_tuple) {
                try {
                    // unpack the values
                    list($contribution_id, $contact_id, $email) = $contact_email_tuple;
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

                    // add attachments
                    $attachments = [];
                    $attachment_file = $this->findAttachmentFile($contact_id, 1, $contribution_id);
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
     *
     * @throws Exception
     *   something went wrong with the invoice generation
     */
    protected function findAttachmentFile($contact_id, $index = 1, $contribution_id = null)
    {
        $attachment_type = CRM_Utils_Array::value("attachment{$index}_type", $this->config, 'none');
        switch ($attachment_type) {
            case 'invoice':
                // generate an invoice
                $params        = ['output' => 'pdf_invoice', 'forPage' => 'confirmpage'];
                $invoice_html  = CRM_Contribute_Form_Task_Invoice::printPDF([$contribution_id], $params, [$contact_id]);
                $invoice_pdf   = CRM_Utils_PDF_Utils::html2pdf($invoice_html, 'invoice.pdf', TRUE);
                $tmp_file_path = tempnam(sys_get_temp_dir(), "invoice-");
                file_put_contents($tmp_file_path, $invoice_pdf);
                return $tmp_file_path;

            case 'file':
                if (!empty($this->config["attachment{$index}_path"])) {
                    $path = $this->config["attachment{$index}_path"];
                    // replace {contact_id} and {contribution_id} token
                    $path = preg_replace('/[{]contact_id[}]/', $contact_id, $path);
                    $path = preg_replace('/[{]contribution_id[}]/', $contribution_id, $path);
                    if (is_readable($path) && !is_dir($path)) {
                        return $path;
                    }
                }
                return null;

            default:
            case 'none':
                break;
        }
        return null;
    }
}