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

namespace GlpiPlugin\Etn;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class Telegram extends \CommonDBTM
{
    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0) {
        return __('Telegram', 'etn');
    }

    /**
     * Send message about the rating to telegram
     *
     * @param $ticketID        integer
     * @param $rate            integer
     *
     * @return bool
    **/
    static function sendRatingMessage($ticketID, $rate, $comment = '') {
        global $CFG_GLPI;

        try {
            $requesters = [];
            $assigns = [];
            $reqTitle = __('Инициатор', 'etn').': ';
            $specTitle = __('Специалист', 'etn').': ';

            $ticket = new \Ticket();
            $ticket->getFromDB($ticketID);
            $ticketURL = $CFG_GLPI['url_base'].$ticket->getLinkURL();
            $class = new $ticket->userlinkclass();
            $ticketsUser = $class->getActors($ticketID);
            $reqs = $ticketsUser[\CommonITILActor::REQUESTER];
            $specs = $ticketsUser[\CommonITILActor::ASSIGN];
            $u = new \User();
            foreach($reqs as $requester) {
                $user = current($u->find(['id' => $requester['users_id']]));
                array_push($requesters, $user['realname'].' '.$user['firstname']);
            }
            foreach($specs as $assign) {
                $user = current($u->find(['id' => $assign['users_id']]));
                array_push($assigns, $user['realname'].' '.$user['firstname']);
            }
            if(count($reqs) > 1) $reqTitle = __('Инициаторы', 'etn').': ';
            if(count($specs) > 1) $specTitle = __('Специалисты', 'etn').': ';
            $message = "По заявке ".$ticketURL." получена низкая оценка - ".$rate.". Комментарий: ".$comment.".\n";
            $message .= $reqTitle.implode(', ', $requesters)."\n";
            $message .= $specTitle.implode(', ', $assigns);

            $bot = new \TelegramBot\Api\BotApi(Config::getOption('bot_token'));
            $bot->sendMessage(Config::getOption('group_chat_id'), $message);
        } catch (Exception $e) {
            $e->getMessage();
            error_log($e, 3, GLPI_LOG_DIR.'/telegram_etn.log');
            return false;
        }
        return true;
    }

    /**
     * Send message about the rating to telegram
     *
     * @param $ticketID        integer
     *
     * @return bool
    **/
    static function sendPriorityUpMessage($ticketID) {
        global $CFG_GLPI;

        try {
            $ticket = new \Ticket();
            $ticket->getFromDB($ticketID);
            $ticketURL = $CFG_GLPI['url_base'].$ticket->getLinkURL();
            $message = "Повышен приоритет заявки ".$ticketURL;
            $bot = new \TelegramBot\Api\BotApi(Config::getOption('bot_token'));
            $bot->sendMessage(Config::getOption('group_chat_id'), $message);
            
            $class = new $ticket->userlinkclass();
            $ticketsUser = $class->getActors($ticketID);
            $specs = $ticketsUser[\CommonITILActor::ASSIGN];
            $u = new \User();
            foreach($specs as $assign) {
                $chatID = Chat::getChat(User::getUsername($assign['users_id']));
                $bot->sendMessage($chatID, $message);
            }
        } catch (Exception $e) {
            $e->getMessage();
            error_log($e, 3, GLPI_LOG_DIR.'/telegram_etn.log');
            return false;
        }
        return true;
    }

    /**
     * Get bot name
     *
     *
     * @return string
    **/
    static function getBotName() {
        try {
            $bot = new \TelegramBot\Api\BotApi(Config::getOption('bot_token'));
            return $bot->getMe()->getUsername();
        } catch (\TelegramBot\Api\Exception $e) {
            $e->getMessage();
            error_log($e, 3, GLPI_LOG_DIR.'/telegram_etn.log');
            return false;
        }
    }

}
?>