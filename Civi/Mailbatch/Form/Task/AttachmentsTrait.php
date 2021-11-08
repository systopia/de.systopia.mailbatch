<?php
/*-------------------------------------------------------+
| SYSTOPIA MailBatch Extension                           |
| Copyright (C) 2021 SYSTOPIA                            |
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

namespace Civi\Mailbatch\Form\Task;

use CRM_Mailbatch_ExtensionUtil as E;

/**
 * For use in classes extending CRM_Core_Form_Task.
 */
trait AttachmentsTrait
{
    /**
     * @var string
     */
    protected $_ajax_action;

    public function addAttachmentElements()
    {
        $attachment_elements = [];
        $attachments = $this->get('attachments');

        $ajax_action = \CRM_Utils_Request::retrieve('ajax_action', 'String');
        if ($ajax_action == 'remove_attachment') {
            $attachment_id = \CRM_Utils_Request::retrieve('ajax_attachment_id', 'String');
            unset($attachments[$attachment_id]);
        }
        if ($ajax_action == 'add_attachment') {
            $attachments[] = NULL;
        }
        $this->set('attachments', $attachments);

        foreach ($attachments as $attachment_id => $attachment) {
            // TODO: Add type element.

            // TODO: Add settings elements depending on type.
            $this->add(
                'textarea',
                'attachments--' . $attachment_id,
                E::ts('Attachment settings'),
                [
                    'rows' => 3,
                    'cols' => 80,
                ]
            );
            $this->add(
                'button',
                'attachments--' . $attachment_id . '_remove',
                E::ts('Remove attachment'),
                [
                    'data-attachment_id' => $attachment_id,
                    'class' => 'crm-mailbatch-attachment-remove'
                ]
            );
            $attachment_elements[$attachment_id] = [
                'attachments--' . $attachment_id,
                'attachments--' . $attachment_id . '_remove',
            ];
        }
        $this->assign('attachments', $attachment_elements);

        $this->add(
            'button',
            'attachments_more',
            E::ts('Add attachment')
        );
        \CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/attachments.js', 1, 'html-header');
        $this->addClass('crm-mailbatch-attachments-form');
    }

}
