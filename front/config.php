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
 * @copyright Copyright (C) 2022-2022 by Oleg Кapeshko
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/exilitywork/etn
 * -------------------------------------------------------------------------
 */
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Etn\Config;
use GlpiPlugin\Etn\ExpiredSla;
use GlpiPlugin\Etn\InactionTime_Group_User;

global $CFG_GLPI, $DB;

include("../../../inc/includes.php");

Session::checkRight('config', READ);

if(Session::getLoginUserID()) {
    if (Session::getCurrentInterface() == "helpdesk") {
        Html::displayRightError();
    } else {
        Html::header(Config::getTypeName(1), $_SERVER['PHP_SELF'], 'config', 'GlpiPlugin\Etn\Config');
    }
}
if(!empty($_POST) && isset($_POST['update'])) {
    if(isset($_POST['entities_id'])) unset($_POST['entities_id']);
    if(isset($_POST['update'])) unset($_POST['update']);
    if(isset($_POST['id'])) unset($_POST['id']);
    if(isset($_POST['_glpi_csrf_token'])) unset($_POST['_glpi_csrf_token']);
    Config::updateConfig($_POST);
}

// add or delete users for InactionTime notify
if(!empty($_POST) && isset($_POST['add'])) {
    $itgu = new InactionTime_Group_User;
    $itgu->fields['groups_id'] = $_POST['groups_id'];
    $itgu->fields['users_id'] = $_POST['users_id'];
    if(!(current($itgu->find(['groups_id' => $_POST['groups_id'], 'users_id' => $_POST['users_id']], [], 1)))) {
        $itgu->addToDB();
    }
}
if(!empty($_REQUEST) && isset($_REQUEST['delete'])) {
    $itgu = new InactionTime_Group_User;
    $itgu->deleteByCriteria(['id' => $_REQUEST['delete']]);
}

// add or delete users for SLA notify
if(!empty($_POST) && isset($_POST['add_sla_user'])) {
    $expiredSlaUser = new ExpiredSla;
    $expiredSlaUser->fields['users_id'] = $_POST['users_id'];
    if(!(current($expiredSlaUser->find(['users_id' => $_POST['users_id']], [], 1)))) {
        $expiredSlaUser->addToDB();
    }
}
if(!empty($_REQUEST) && isset($_REQUEST['delete_sla_user'])) {
    $itgu = new ExpiredSla;
    $itgu->deleteByCriteria(['id' => $_REQUEST['delete_sla_user']]);
}

$config = new Config();
$config->getFromDB(1);
$config->display(['withtemplate' => 1]);

if(Session::getLoginUserID()) {
    if (Session::getCurrentInterface() == "helpdesk") {
        Html::helpFooter();
    } else {
        Html::footer();
    }
}