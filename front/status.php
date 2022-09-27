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
use GlpiPlugin\Etn\Rating;
use GlpiPlugin\Etn\Priority;

include("../../../inc/includes.php");
$noLogin = true;
if(Session::getLoginUserID()) {
    if (Session::getCurrentInterface() == "helpdesk") {
        Html::helpHeader(Ticket::getTypeName(Session::getPluralNumber()), 'tickets', 'ticket');
    } else {
        Html::header(Central::getTypeName(1), $_SERVER['PHP_SELF'], 'central', 'central');
    }
    $noLogin = false;
}

$successRate        = false;
$multipleRate       = false;
$successPriority    = false;
$multiplePriority   = false;
$status             = 0;
$error              = false;
$isSolved           = false;

if(isset($_REQUEST['tickets_id']) && isset($_REQUEST['users_id'])){
    if(isset($_REQUEST['rating'])){
        $r = new Rating();
        $ts = new \TicketSatisfaction();
        $ticket = current($ts->find(['tickets_id' => $_REQUEST['tickets_id']], [], 1));
        if($rating = current($r->find(['tickets_id' => $_REQUEST['tickets_id'], 'status' => 1], [], 1))) {
            $multipleRate = true;
            $status = isset($ticket['satisfaction']) ? $ticket['satisfaction'] : -1;
        } else {
            if(count($ticket)) {
                $ts->fields['id'] = $ticket['id'];
                $ts->fields['date_answered'] = date('Y-m-d H:i:s');
                $ts->fields['satisfaction'] = $_REQUEST['rating'];
                $ts->updateInDB(array_keys($ts->fields));
            } else {
                $_REQUEST['date_answered'] = date('Y-m-d H:i:s');
                $ts->add($_REQUEST);
            }
            $_REQUEST['status'] = 1;
            $r->add($_REQUEST);
            $successRate = true;
            $status = $_REQUEST['rating'];
        }

    }
    if(isset($_REQUEST['priority_up'])){
        $t = new \Ticket();
        $ticket = current($t->find(['id' => $_REQUEST['tickets_id']], [], 1));
        if(count($ticket)) {
            if($ticket['status'] > 4) {
                $isSolved = true;
            } elseif($ticket['priority'] == 4) {
                $multiplePriority   = true;
            } else {
                $t->fields['id'] = $ticket['id'];
                $t->fields['date_mod'] = date('Y-m-d H:i:s');
                $t->fields['priority'] = $_REQUEST['priority_up'];
                $t->fields['urgency'] = $_REQUEST['priority_up'];
                $t->fields['impact'] = $_REQUEST['priority_up'];
                $t->updateInDB(array_keys($t->fields));
                $successPriority = true;
            }
        } else {
            $error = true;
        }
    }
}

TemplateRenderer::getInstance()->display('@etn/template.html.twig', [
    'no_login'              => $noLogin,
    'login_page'            => $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'],
    'ticket_url'            => $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/front/ticket.form.php?id='.$_REQUEST['tickets_id'],
    'success_rate'          => $successRate,
    'status'                => $status,
    'multiple_rate'         => $multipleRate,
    'success_priority_up'   => $successPriority,
    'multiple_priority_up'  => $multiplePriority,
    'copyright_message'     => Html::getCopyrightMessage(false),
    'card_md_width'         => true,
    'error'                 => $error,
    'is_solved'             => $isSolved
]);

if(Session::getLoginUserID()) {
    if (Session::getCurrentInterface() == "helpdesk") {
        Html::helpFooter();
    } else {
        Html::footer();
    }
}