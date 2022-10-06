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

use GlpiPlugin\Etn\Config;

include ("../../../inc/includes.php");

Session::checkLoginUser();

if(!(isset($_POST['name']) && isset($_POST['token']))) die();

$name = $_POST['name'];
$token = $_POST['token'];

try {
    $bot = new \TelegramBot\Api\BotApi(Config::getOption('bot_token'));
    $config = Config::getConfig();
    $updates = $bot->getUpdates();
    if(!is_array($updates)) $updates = array($updates);
    krsort($updates);
    foreach($updates as $update){
        if($message = $update->getMessage()) {
            if($name == $message->getChat()->getTitle()) {
                print($message->getChat()->getId());
                die();
            }
        }
    }        
} catch (Exception $e) {
    $e->getMessage();
    print_r($e->getMessage());
    return false;
}