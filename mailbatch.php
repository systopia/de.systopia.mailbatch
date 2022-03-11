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
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function mailbatch_civicrm_xmlMenu(&$files)
{
    _mailbatch_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function mailbatch_civicrm_postInstall()
{
    _mailbatch_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function mailbatch_civicrm_uninstall()
{
    _mailbatch_civix_civicrm_uninstall();
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
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function mailbatch_civicrm_disable()
{
    _mailbatch_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function mailbatch_civicrm_upgrade($op, CRM_Queue_Queue $queue = null)
{
    return _mailbatch_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function mailbatch_civicrm_managed(&$entities)
{
    _mailbatch_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function mailbatch_civicrm_caseTypes(&$caseTypes)
{
    _mailbatch_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function mailbatch_civicrm_angularModules(&$angularModules)
{
    _mailbatch_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function mailbatch_civicrm_alterSettingsFolders(&$metaDataFolders = null)
{
    _mailbatch_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function mailbatch_civicrm_entityTypes(&$entityTypes)
{
    _mailbatch_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function mailbatch_civicrm_themes(&$themes)
{
    _mailbatch_civix_civicrm_themes($themes);
}

/**
 * Implementation of hook_civicrm_searchTasks,
 *  to inject our 'Send E-Mail' task
 */
function mailbatch_civicrm_searchTasks($objectType, &$tasks)
{
    // add "Send E-Mail" task to contact search result
    if ($objectType == 'contact') {
        $tasks[] = [
            'title' => E::ts('Send Emails (via MailBatch)'),
            'class' => trait_exists('Civi\Mailattachment\Form\Task\AttachmentsTrait') ? 'CRM_Mailbatch_Form_Task_ContactEmailAttachments' : 'CRM_Mailbatch_Form_Task_ContactEmail',
            'result' => false
        ];
        return;
    }

    // add "Send E-Mail" task to contact search result
    if ($objectType == 'contribution') {
        $tasks[] = [
            'title' => E::ts('Send Emails (via MailBatch)'),
            'class' => trait_exists('Civi\Mailattachment\Form\Task\AttachmentsTrait') ? 'CRM_Mailbatch_Form_Task_ContributionEmailAttachments' : 'CRM_Mailbatch_Form_Task_ContributionEmail',
            'result' => false
        ];
    }
}
