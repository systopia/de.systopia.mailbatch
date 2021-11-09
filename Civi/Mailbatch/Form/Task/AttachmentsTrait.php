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

use Civi\Core\Event\GenericHookEvent;
use Civi\FormProcessor\API\Exception;
use CRM_Mailbatch_ExtensionUtil as E;

/**
 * For use in classes extending CRM_Core_Form_Task.
 */
trait AttachmentsTrait
{

    public function addAttachmentElements()
    {
        $attachment_elements = [];
        $attachment_types = self::attachmentTypes();
        $attachments = $this->get('attachments');

        $ajax_action = \CRM_Utils_Request::retrieve('ajax_action', 'String');
        if ($ajax_action == 'remove_attachment') {
            $attachment_id = \CRM_Utils_Request::retrieve('ajax_attachment_id', 'String');
            unset($attachments[$attachment_id]);
        }
        if ($ajax_action == 'add_attachment') {
            $attachment_type = \CRM_Utils_Request::retrieve('ajax_attachment_type', 'String');
            $attachments[] = ['type' => $attachment_type];
        }
        $this->set('attachments', $attachments);

        foreach ($attachments as $attachment_id => $attachment) {
            if (!$attachment_type = $attachment_types[$attachment['type']] ?? null) {
                throw new Exception(E::ts('Unregistered attachment type %1', [1 => $attachment['type']]));
            }
            $attachment_elements[$attachment_id] = $attachment_type['controller']::buildAttachmentForm(
                $this,
                $attachment_id
            );
            $this->add(
                'button',
                'attachments--' . $attachment_id . '_remove',
                E::ts('Remove attachment'),
                [
                    'data-attachment_id' => $attachment_id,
                    'class' => 'crm-mailbatch-attachment-remove',
                ]
            );
        }
        $this->assign('attachments', $attachment_elements);

        $this->add(
            'select',
            'attachments_more_type',
            E::ts('Attachment type'),
            array_map(function ($attachment_type) {
                return $attachment_type['label'];
            }, $attachment_types)
        );
        $this->add(
            'button',
            'attachments_more',
            E::ts('Add attachment')
        );
        \CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/attachments.js', 1, 'html-header');
        $this->addClass('crm-mailbatch-attachments-form');
    }

    public function processAttachments()
    {
        $attachment_values = [];
        $attachments = $this->get('attachments');
        $attachment_types = self::attachmentTypes();
        foreach ($attachments as $attachment_id => $attachment) {
            if (!$attachment_type = $attachment_types[$attachment['type']] ?? null) {
                throw new Exception(E::ts('Unregistered attachment type %1', [1 => $attachment['type']]));
            }
            $attachment_values[$attachment_id] = $attachment_type['controller']::processAttachmentForm(
                $this,
                $attachment_id
            ) + ['type' => $attachment['type']];
        }
        // TODO: Is this setting even necessary?
        \Civi::settings()->set('batchmail_attachments', $attachment_values);
        return $attachment_values;
    }

    /**
     * Builds a list of registered attachment types.
     *
     * @return array
     *   The list of registered attachment types, indexed by their internal name.
     *
     */
    public static function attachmentTypes()
    {
        $attachment_types = [];

        $attachment_types['generic'] = [
            'label' => E::ts('Generic'),
            'controller' => '\Civi\Mailbatch\AttachmentType\Generic',
        ];

        $event = GenericHookEvent::create(['attachment_types' => &$attachment_types]);
        \Civi::dispatcher()->dispatch('civi.mailbatch.attachmentTypes', $event);
        return $attachment_types;
    }

}
