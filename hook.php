<?php

/**
 * -------------------------------------------------------------------------
 * Extended Ticket's Notification plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Extended Ticket's Notification.
 *
 * Extended Ticket's Notification is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * any later version.
 *
 * Extended Ticket's Notification is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Extended Ticket's Notification. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2022-2023 by Oleg Кapeshko
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/exilitywork/etn
 * -------------------------------------------------------------------------
 */

//include ("../../inc/includes.php");
use GlpiPlugin\Etn\InactionTime;
use GlpiPlugin\Etn\Process;
use GlpiPlugin\Etn\Ldap;
use GlpiPlugin\Etn\User;
use GlpiPlugin\Etn\NotificationTargetTopRequesters;
use GlpiPlugin\Etn\NotificationTargetInactionTime;
use GlpiPlugin\Etn\NotificationTargetExpiredSla;
use GlpiPlugin\Etn\NotificationTargetTicketCategory;
use GlpiPlugin\Etn\NotificationTargetItemtype;
use GlpiPlugin\Etn\NotificationTargetTakeIntoAccountTime;
use GlpiPlugin\Etn\TakeIntoAccountTime;

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_etn_install() {
    global $DB;

    if(!$DB->runFile(GLPI_ROOT . "/plugins/etn/sql/install.sql")) die("SQL error");

    NotificationTargetTopRequesters::init();
    NotificationTargetInactionTime::init();
    NotificationTargetExpiredSla::init();
    NotificationTargetTicketCategory::init();
    NotificationTargetItemtype::init();
    NotificationTargetTakeIntoAccountTime::init();

    $cron = new \CronTask();
    if (!$cron->getFromDBbyName('GlpiPlugin\Etn\Cron', 'SendMessageTelegeramETN')) {
        \CronTask::Register('GlpiPlugin\Etn\Cron', 'SendMessageTelegeramETN', 300,
                            ['state' => \CronTask::STATE_WAITING, 'mode' => 2]);
    }
    if (!$cron->getFromDBbyName('GlpiPlugin\Etn\Cron', 'ListenMessageTelegramETN')) {
        \CronTask::Register('GlpiPlugin\Etn\Cron', 'ListenMessageTelegramETN', 300,
                            ['state' => \CronTask::STATE_WAITING, 'mode' => 2]);
    }
    if (!$cron->getFromDBbyName('GlpiPlugin\Etn\Cron', 'TicketStatCalculationETN')) {
        \CronTask::Register('GlpiPlugin\Etn\Cron', 'TicketStatCalculationETN', HOUR_TIMESTAMP,
                            ['state' => \CronTask::STATE_WAITING, 'mode' => 2]);
    }
    if (!$cron->getFromDBbyName('GlpiPlugin\Etn\Cron', 'SendTopRequestersETN')) {
        \CronTask::Register('GlpiPlugin\Etn\Cron', 'SendTopRequestersETN', HOUR_TIMESTAMP,
                            ['state' => \CronTask::STATE_WAITING, 'mode' => 2]);
    }
    if (!$cron->getFromDBbyName('GlpiPlugin\Etn\Cron', 'CheckInactionTimeTicketETN')) {
        \CronTask::Register('GlpiPlugin\Etn\Cron', 'CheckInactionTimeTicketETN', HOUR_TIMESTAMP,
                            ['state' => \CronTask::STATE_WAITING, 'mode' => 2]);
    }
    if (!$cron->getFromDBbyName('GlpiPlugin\Etn\Cron', 'ExpiredSlaETN')) {
        \CronTask::Register('GlpiPlugin\Etn\Cron', 'ExpiredSlaETN', HOUR_TIMESTAMP,
                            ['state' => \CronTask::STATE_WAITING, 'mode' => 2]);
    }
    if (!$cron->getFromDBbyName('GlpiPlugin\Etn\Cron', 'CalculateTakeIntoAccountTimeETN')) {
        \CronTask::Register('GlpiPlugin\Etn\Cron', 'CalculateTakeIntoAccountTimeETN', HOUR_TIMESTAMP,
                            ['state' => \CronTask::STATE_WAITING, 'mode' => 2]);
    }
    if (!$cron->getFromDBbyName('GlpiPlugin\Etn\Cron', 'SendTakeIntoAccountTimeETN')) {
        \CronTask::Register('GlpiPlugin\Etn\Cron', 'SendTakeIntoAccountTimeETN', HOUR_TIMESTAMP,
                            ['state' => \CronTask::STATE_WAITING, 'mode' => 2]);
    }

    Ldap::updateConfig();

    $user = new \User();
    $users = $user->find();
    foreach($users as $user) {
        if(!$user['picture']) continue;
        $file = $user['picture'];
        $tag = trim(Document::getImageTag(Rule::getUuid()), '#');
        $filename = mb_substr($file, strpos($file, '_') + 1);
        $prefix = mb_substr($file, strpos($file, '/') + 1, strpos($file, '_') - strpos($file, '/'));
        if(file_exists(GLPI_PICTURE_DIR.'/'.$file)) {
            copy(GLPI_PICTURE_DIR.'/'.$file, GLPI_TMP_DIR.'/'.$prefix.$filename);
        } else {
            continue;
        }

        // search and add user photo as document
        $input['_filename']        = [$prefix.$filename];
        $input['_tag_filename']    = [$tag];
        $input['_prefix_filename'] = [$prefix];
        $doc = new \Document();
        if($curDoc = current($doc->find(['filename' => $filename], [], 1))) {
            $docID = $curDoc['id'];
        } else {
            $docID = $doc->add($input);
        }

        // add or update users and docs relations
        $proc = new Process();
        $proc->fields['users_id'] = $user['id'];
        $proc->fields['documents_id'] = $docID;
        if($curProc = current($proc->find(['users_id' => $user['id']], [], 1))) {
            $proc->fields['id'] = $curProc['id'];
            $proc->updateInDB(array_keys($proc->fields));
        } else {
            $proc->addToDB();
        }
    }

    return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_etn_uninstall() {
   global $DB;

   //if(!$DB->runFile(GLPI_ROOT . "/plugins/etn/sql/uninstall.sql")) die("SQL error");  

   return true;
}

function plugin_etn_hook_post_item_form(array $params) {
   $item = $params['item'];

   if (in_array($item->getType(), ['User', 'Ticket', 'Preference'])) {
      User::showUsernameField($params);
   }
   if ($item->getType() == 'ITILCategory') {
      InactionTime::showTimeField($item);
   }
}