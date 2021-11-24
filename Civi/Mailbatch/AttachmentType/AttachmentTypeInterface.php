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

namespace Civi\Mailbatch\AttachmentType;

use CRM_Mailbatch_ExtensionUtil as E;

interface AttachmentTypeInterface
{
    /**
     * TODO: Document what needs to be returned.
     *
     * @param $form
     * @param $attachment_id
     *
     * @return mixed
     */
    public static function buildAttachmentForm(&$form, $attachment_id);

    public static function processAttachmentForm(&$form, $attachment_id);

    public static function buildAttachment($context, $attachment_values);

    /**
     * TODO: Optional pre-caching of attachments for a batch of entites to be
     *       used in self::buildAttachment() instead of slow generation
     *       one-by-one.
     *
     * @param $context
     * @param $attachment_values
     *
     * @return bool
     *   Whether the caching was successful.
     */
//    public static function preCacheAttachments($context, $attachment_values);

    /**
     * TODO: Inform attachment providers that things are done:
     *       - a batch of contacts
     *       - the entire task
     *       so that generated attachments can be cleaned up.
     *
     * Optional
     *
     * @param $context
     *
     * @return mixed
     */
//    public static function cleanUpAttachments($context);
}
