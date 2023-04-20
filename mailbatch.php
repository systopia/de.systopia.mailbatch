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

require_once 'mailbatch.civix.php';

// phpcs:disable
use CRM_Mailbatch_ExtensionUtil as E;

// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function mailbatch_civicrm_config(&$config)
{
    _mailbatch_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function mailbatch_civicrm_install()
{
    _mailbatch_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function mailbatch_civicrm_enable()
{
    _mailbatch_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_searchTasks,
 *  to inject our 'Send E-Mail' task
 */
function mailbatch_civicrm_searchTasks($objectType, &$tasks)
{
    // Add "Send E-Mail" task to contact search result.
    if ($objectType == 'contact') {
        $tasks[] = [
            'title' => E::ts('Send E-Mail (via MailBatch)'),
            'class' => 'CRM_Mailbatch_Form_Task_ContactEmail',
            'result' => false
        ];
        return;
    }

    // Add "Send E-Mail" task to contribution search result.
    if ($objectType == 'contribution') {
        $tasks[] = [
            'title' => E::ts('Send E-Mail (via MailBatch)'),
            'class' => 'CRM_Mailbatch_Form_Task_ContributionEmail',
            'result' => false
        ];
    }

    // Add "Send E-Mail" task to membership search result
    if ($objectType == 'membership') {
        $tasks[] = [
            'title' => E::ts('Send E-Mail (via MailBatch)'),
            'class' => 'CRM_Mailbatch_Form_Task_MembershipEmail',
            'result' => false
        ];
    }
}
