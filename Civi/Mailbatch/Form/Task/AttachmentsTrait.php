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
 * For use in classes extending CRM_Core_Form.
 */
// TODO: Rename trait to denote being for forms
trait AttachmentsTrait
{

    public function addAttachmentElements($context = [])
    {
        $attachment_forms = [];
        $attachment_types = self::attachmentTypes($context);
        foreach ($attachment_types as &$attachment_type) {
            /* @var \Civi\Mailbatch\AttachmentType\AttachmentTypeInterface $controller */
            $controller = $attachment_type['controller'];
            if (is_callable([$controller, 'getAttachmentFormTemplate'])) {
                $attachment_type['form_template'] = $controller::getAttachmentFormTemplate();
            }
        }
        unset($attachment_type);
        // TODO: As default values, load from settings, which attachments used to be there the last time the form was built.
        $defaults = \Civi::settings()->get('batchmail_attachments');
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
            $attachment_forms[$attachment_id]['elements'] = $attachment_type['controller']::buildAttachmentForm(
                $this,
                $attachment_id
            );
            $attachment_forms[$attachment_id]['form_template'] = $attachment_type['form_template'] ?? NULL;
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
        $this->assign('attachment_forms', $attachment_forms);

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
    public static function attachmentTypes($context = [])
    {
        $attachment_types = [];

        $attachment_types['file_on_server'] = [
            'label' => E::ts('File on Server'),
            'controller' => '\Civi\Mailbatch\AttachmentType\FileOnServer',
            'context' => [
                'entity_types' => ['contact', 'contribution'],
            ],
        ];
        // TODO: Implement "invoice" attachment type.
//        $attachment_types['invoice'] = [
//            'label' => E::ts('Contribution Invoice'),
//            'controller' => '\Civi\Mailbatch\AttachmentType\ContributionInvoice',
//            'context' => [
//                'entity_types' => ['contribution'],
//            ],
//        ];

        // TODO: Add supported entity types to context for not allowing e.g. generating invoices for contacts.
        $event = GenericHookEvent::create(['attachment_types' => &$attachment_types]);
        \Civi::dispatcher()->dispatch('civi.mailbatch.attachmentTypes', $event);

        return !empty($context['entity_type']) ? array_filter($attachment_types, function($attachment_type) use ($context) {
            return
                empty($attachment_type['context']['entity_types'])
                || in_array($context['entity_type'], $attachment_type['context']['entity_types']);
        }) : $attachment_types;
    }

    /**
     * get the mime type of the given file
     *
     * @param string $path
     *
     * @return string mime type
     */
    public static function getMimeType($path)
    {
        static $known_files = [];
        if (!isset($known_files[$path])) {
            $known_files[$path] = mime_content_type($path);
        }
        return $known_files[$path];
    }

}
