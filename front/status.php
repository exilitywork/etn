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
use GlpiPlugin\Etn\Telegram;
use GlpiPlugin\Etn\Config;

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
$noComment          = false;
$comment            = '';

if(isset($_REQUEST['tickets_id']) && isset($_REQUEST['users_id'])){
    /*$t = new \Ticket();
    $ticket = current($t->find(['id' => $_REQUEST['tickets_id']], [], 1));
    if(isset($_REQUEST['rating']) && count($ticket) && $ticket['status'] < 6){*/
    if(isset($_REQUEST['rating'])){
        $status = $_REQUEST['rating'];
        $r = new Rating();
        $ts = new \TicketSatisfaction();
        $ticket = current($ts->find(['tickets_id' => $_REQUEST['tickets_id']], [], 1));
        $rating = current($r->find(['tickets_id' => $_REQUEST['tickets_id']], [], 1));
        if(isset($rating['status']) && $rating['status']) {
            $multipleRate = true;
            $status = isset($ticket['satisfaction']) ? $ticket['satisfaction'] : -1;
        } else if($_REQUEST['rating'] < 4 && empty($_REQUEST['comment'])) {
            $noComment = true;
        } else {
            if(!$ticket) {
                $_REQUEST['date_answered'] = date('Y-m-d H:i:s');
                $ts->add($_REQUEST);
                $ticket = current($ts->find(['tickets_id' => $_REQUEST['tickets_id']], [], 1));
                $rating = current($r->find(['tickets_id' => $_REQUEST['tickets_id']], [], 1));
            }
            $ts->fields['date_answered'] = date('Y-m-d H:i:s');
            $ts->fields['satisfaction'] = $_REQUEST['rating'];
            if(isset($_REQUEST['comment'])) $ts->fields['comment'] = $_REQUEST['comment'];
            $ts->fields['id'] = $ticket['id'];
            $ts->updateInDB(array_keys($ts->fields));
            if(!$rating) {
                $r->add(['tickets_id' => $_REQUEST['tickets_id']]);
                $rating = current($r->find(['tickets_id' => $_REQUEST['tickets_id']], [], 1));
            }
            $r->fields['status'] = 1;
            $r->fields['id'] = $rating['id'];
            $r->fields['tickets_id'] = $_REQUEST['tickets_id'];
            $r->fields['users_id'] = $_REQUEST['users_id'];
            $r->updateInDB(array_keys($r->fields));
            $successRate = true;

            $minRating = Config::getOption('min_rating');
            try {
                if($status < ($minRating ? $minRating : 4)) Telegram::sendRatingMessage($_REQUEST['tickets_id'], $status, $_REQUEST['comment']);
            } catch (\TelegramBot\Api\Exception $e) {
                $e->getMessage();
            } 
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
                try {
                    Telegram::sendPriorityUpMessage($_REQUEST['tickets_id']);
                } catch (\TelegramBot\Api\Exception $e) {
                    //$e->getMessage();
                    error_log($e, 3, GLPI_LOG_DIR.'/telegram_etn.log');
                } 

                $notification = new \Notification();
                $n = current($notification->find(['itemtype' => 'Ticket', 'event' => 'update', 'is_active' => 0], [], 1));
                if($n) {
                    $notification->fields['id'] = $n['id'];
                    $notification->fields['is_active'] = 1;
                    $notification->updateInDB(array_keys($notification->fields));
                }
                $t->getFromDb($ticket['id']);
                \NotificationEvent::raiseEvent("update", $t);
                if($n) {
                    $notification->fields['is_active'] = 0;
                    $notification->updateInDB(array_keys($notification->fields));
                }
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
    'tickets_id'            => $_REQUEST['tickets_id'],
    'users_id'              => $_REQUEST['users_id'],
    'multiple_rate'         => $multipleRate,
    'success_priority_up'   => $successPriority,
    'multiple_priority_up'  => $multiplePriority,
    'copyright_message'     => Html::getCopyrightMessage(false),
    'card_md_width'         => true,
    'error'                 => $error,
    'is_solved'             => $isSolved,
    'no_comment'            => $noComment,
    'comment'               => $comment
]);

if(Session::getLoginUserID()) {
    if (Session::getCurrentInterface() == "helpdesk") {
        Html::helpFooter();
    } else {
        Html::footer();
    }
}