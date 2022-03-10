<?php
/*-------------------------------------------------------+
| SYSTOPIA MailBatch Extension                           |
| Copyright (C) 2022 SYSTOPIA                            |
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

namespace Civi\Mailbatch\AttachmentType;

use Civi\Mailbatch\Form\Task\AttachmentsTrait;
use CRM_Mailbatch_ExtensionUtil as E;

class ContributionInvoice implements AttachmentTypeInterface
{
    /**
     * @param \CRM_Core_Form_Task $form
     *
     * @param int $attachment_id
     *
     * @return array
     */
    public static function buildAttachmentForm(&$form, $attachment_id)
    {
        $form->add(
            'text',
            'attachments--' . $attachment_id . '--name',
            E::ts('Attachment Name'),
            ['class' => 'huge'],
            false
        );
        return [
            'attachments--' . $attachment_id . '--name' => 'attachment-contribution_invoice-name',
        ];
    }

    public static function processAttachmentForm(&$form, $attachment_id)
    {
        $values = $form->exportValues();
        return [
            'name' => $values['attachments--' . $attachment_id . '--name'],
        ];
    }

    public static function buildAttachment($context, $attachment_values)
    {
        // Generate an invoice.
        $params = ['output' => 'pdf_invoice', 'forPage' => 'confirmpage'];
        $invoice_html = \CRM_Contribute_Form_Task_Invoice::printPDF(
            [$context['entity_id']],
            $params,
            [$context['extra']['contact_id']]
        );
        $invoice_pdf = \CRM_Utils_PDF_Utils::html2pdf($invoice_html, 'invoice.pdf', true);
        $tmp_file_path = tempnam(sys_get_temp_dir(), "invoice-") . '.pdf';
        file_put_contents($tmp_file_path, $invoice_pdf);
        return [
            'fullPath' => $tmp_file_path,
            'mime_type' => AttachmentsTrait::getMimeType($tmp_file_path),
            'cleanName' => $attachment_values['name'] ?: basename($tmp_file_path),
        ];
    }
}
