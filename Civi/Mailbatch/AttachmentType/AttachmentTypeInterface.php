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
    public static function buildAttachmentForm(&$form, $attachment_id);

    public static function processAttachmentForm(&$form, $attachment_id);

    public static function buildAttachment($context, $attachment_values);
}
