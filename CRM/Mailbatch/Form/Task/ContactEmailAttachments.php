<?php
/*-------------------------------------------------------+
| SYSTOPIA MailBatch Extension                           |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
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

use Civi\Mailattachment\Form\Task\AttachmentsTrait;
use CRM_Mailbatch_ExtensionUtil as E;

/**
 * Send E-Mail to contacts task, supporting attachments.
 */
class CRM_Mailbatch_Form_Task_ContactEmailAttachments extends CRM_Mailbatch_Form_Task_ContactEmail
{
    use AttachmentsTrait;

    public function getTemplateFileName()
    {
        return 'CRM/Mailbatch/Form/Task/ContactEmail.tpl';
    }
}
