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

class Chat extends \CommonDBTM
{

    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0) {
        return __('Chat', 'etn');
    }

    /**
     * Add or update telegram user's chat ID
     *
     * @param $username     string
     * @param $id           integer
     *
     * @return integer
    **/
    static function updateChat(string $username, int $id) {
        try {
            $chat = new self;
            $chat->fields['chat_id'] = $id;
            $chat->fields['username'] = $username;
            if($chatCur = current($chat->find(['username' => $username, 'chat_id' => $id], [], 1))) {
                return 3;
            }
            if($chatCur = current($chat->find(['username' => $username], [], 1))) {
                $chat->fields['id'] = $chatCur['id'];
                $chat->updateInDB(array_keys($chat->fields));
                return 2;
            } else {
                $chat->addToDB();
            }
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
        return 1;
    }

    /**
     * Get telegram user's chat ID
     *
     * @param $username     string
     *
     * @return string
    **/
    static function getChat($username) {
        $id = false;
        $chat = new self;
        try {
            if($c = current($chat->find(['username' => $username], [], 1))) $id = $c['chat_id'];
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
        return $id;
    }
}
?>