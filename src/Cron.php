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

class Cron extends \CommonDBTM
{

    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0) {
        return __('Cron', 'etn');
    }

    static function cronInfo($name) {
        switch ($name) {
            case 'SendMessageTelegeramETN':
                return array('description' => __('Отправка уведомлений в Telegram', 'etn'));
            case 'ListenMessageTelegramETN':
                return array('description' => __('Обработка новых сообщений в Telegram', 'etn'));
        }
  
        return array();
    }
  
    static function cronSendMessageTelegeramETN($task) {
        global $CFG_GLPI, $DB;

        try {
            $bot = new \TelegramBot\Api\BotApi(Config::getOption('bot_token'));
            //$bot = new \TelegramBot\Api\BotApi($token);

            //$bot->sendMessage('383009633', "TEST \n MESSAGE");
            //die();
            $rate = 4;
            $ticketID = 262357;
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
            $message = "По заявке ".$ticketURL." получена низкая оценка - ".$rate."\n";
            $message .= $reqTitle.implode(', ', $requesters)."\n";
            $message .= $specTitle.implode(', ', $assigns)."\n";
            $bot->sendMessage('383009633', $message);
            $config = Config::getConfig();

            foreach($bot->getUpdates() as $update){
                print_r($update);
                print_r('<br>');
                print_r('<br>');
                /*$message = $update->getMessage();
                $id = $message->getChat()->getId();
                $username = $message->getChat()->getUsername();
                Chat::updateChat($username, $id);
                $bot->sendMessage($id, 'Your message: ' . $message->getText());*/
            }

            if(isset($update)) {
                //print_r($update->getUpdateId());
                //Config::updateConfig(['updateId' => $update->getUpdateId()]);
            }
            //print_r(Chat::getChat($username));
            //die();
                
        } catch (Exception $e) {
            $e->getMessage();
            print_r($e->getMessage());
            $task->log("Error.");
            return false;
        }

        $task->addVolume(5);
        $task->log("Все хорошо");
        return true;
        $task->log("Error.");
        return false;
    }

    static function cronListenMessageTelegramETN($task) {
        global $DB;
        try {
            $bot = new \TelegramBot\Api\BotApi(Config::getOption('bot_token'));
            $config = Config::getConfig();

            foreach($bot->getUpdates($config['updateId'] + 1) as $update){
                $message = $update->getMessage();
                $id = $message->getChat()->getId();
                $username = $message->getChat()->getUsername();
                switch(Chat::updateChat($username, $id)) {
                    case 1:
                        $text = __('Ваш ID успешно зарегистрирован!', 'etn');
                        break;
                    case 2:
                        $text = __('Ваш ID успешно обновлен!', 'etn');
                        break;
                    case 3:
                        $text = __('Вы уже зарегистрированы!', 'etn');
                        break;
                    default:
                        $text = __('Ошибка регистрации!', 'etn');
                }
                $bot->sendMessage($id, $text);
            }

            if(isset($update)) {
                Config::updateConfig(['updateId' => $update->getUpdateId()]);
            }
                
        } catch (Exception $e) {
            $e->getMessage();
            print_r($e->getMessage());
            $task->log($e->getMessage());
            return false;
        }
        $task->addVolume(1);
        return true;
    }
}
?>