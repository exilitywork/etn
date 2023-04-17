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

class ExpiredSla extends \CommonDBTM {

    public $deduplicate_queued_notifications = false;

    static function addRecipient($item) {
        
        unset($item->target);

        $recipients = $item->options['recipients'];

        foreach($recipients as $id) {
            $email = current((new \UserEmail)->find(['users_id' => $id, 'is_default' => 1], [], 1))['email'];
            $user = current((new \User)->find(['id' => $id], [], 1));

            if ($item->getType() == 'GlpiPlugin\Etn\NotificationTargetExpiredSla') {
                $item->target[$email]['language'] = 'ru_RU';
                $item->target[$email]['additionnaloption']['usertype'] = 2;
                $item->target[$email]['username'] = $user['realname'].' '.$user['firstname'];
                $item->target[$email]['users_id'] = $id;
                $item->target[$email]['email'] = $email;
            }
        }
        //error_log(date('Y-m-d H:i:s')."TEST\n", 3, '/var/www/glpi/files/_log/test.log');
    }

    static function getUsers() {
        $users = [];
        $items = (new self)->find();
        foreach($items as $item) {
            array_push($users, $item['users_id']);
        }
        return array_unique($users);
    }
}