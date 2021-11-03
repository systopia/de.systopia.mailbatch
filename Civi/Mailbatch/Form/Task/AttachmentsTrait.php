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
     * @var array $attachments
     */
    protected $attachments = [];

    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    public function addAttachment($id, $settings = NULL) {
        $this->attachments[$id] = $settings;
    }

    /**
     * @var string
     */
    protected $_ajax_action;

    public function addAttachmentElements()
    {
        $attachment_count = 0;
        $attachment_elements = [];

        // Get all current attachment fields when adding via Ajax.
        if (
            ($this->_ajax_action = \CRM_Utils_Request::retrieve('ajax_action', 'String', $this))
            && $this->_ajax_action == 'add_attachment'
        ) {
            while (true) {
                $attachment_count++;
                // TODO: Retrieve type for each attachment and retrieve settings depending on type from separate fields.
                $current_attachment = \CRM_Utils_Request::retrieve(
                    'attachments--' . $attachment_count,
                    'Json',
                    $this
                );
                $this->addAttachment($attachment_count, $current_attachment);
                if (is_null($current_attachment)) {
                    break;
                }
            }
        }

        $attachment_count = 0;
        foreach ($this->getAttachments() as $attachment_settings) {
            $attachment_count++;
            // TODO: Add type element.

            // TODO: Add settings elements depending on type.
            $this->add(
                'textarea',
                'attachments--' . $attachment_count,
                E::ts('Attachment settings'),
                [
                    'rows' => 3,
                    'cols' => 80,
                ]
            );
            $attachment_elements[$attachment_count] = 'attachments--' . $attachment_count;
        }
        $this->assign('attachment_elements', $attachment_elements);

        $this->add(
            'button',
            'attachments_more',
            E::ts('Add attachment')
        );
        \CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/attachments.js', 1, 'html-header');
        $this->addClass('crm-mailbatch-attachments-form');
    }

}
