<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2022 SYSTOPIA                            |
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
 * Collection of upgrade steps.
 */
class CRM_Mailbatch_Upgrader extends CRM_Mailbatch_Upgrader_Base
{
    /**
     * Added notification for existing users.
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0001(): bool
    {
        CRM_Core_Session::setStatus(
            E::ts("There has been changes to the way attachments are handled in the MailBatch extension. If you were using attachments you should install the <a href=\"https://github.com/systopia/de.systopia.mailattachment\">Mail-Attachment</a> extension."),
            E::ts("MailBatch: Changes to attachments"),
            'info',
            ['expires' => 0]
        );
        return true;
    }
}
