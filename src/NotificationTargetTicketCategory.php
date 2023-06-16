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

namespace GlpiPlugin\Etn;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class NotificationTargetTicketCategory extends \NotificationTarget {

    const ADD_FOLLOWUP_CATEGORY     = 'add_followup_category';
    const NEW_TICKET_CATEGORY       = 'new_ticket_category';
    const SOLVED_TICKET_CATEGORY    = 'solved_ticket_category';

    /**
     * @return array
     */
    function getEvents() {
        return [
            self::ADD_FOLLOWUP_CATEGORY     => 'ETN - Добавление комментария в заявке',
            self::NEW_TICKET_CATEGORY       => 'ETN - Новая заявка',
            self::SOLVED_TICKET_CATEGORY    => 'ETN - Заявка решена'
        ];
    }

    function getDatasForTemplate($event, $options = []) {
    }
 
    function getSpecificTargets($data, $options) {
    }

    function getDataForObject(\CommonDBTM $item, array $options, $simple = false) {
        $notification_target_ticket = new NotificationTargetTicket();
        $data = $notification_target_ticket->getDataForObject($item, $options, $simple);
        return $data;
    }

    /**
     *
    */
    function getTags() {
        $notification_target_ticket = new NotificationTargetTicket();
        $notification_target_ticket->getTags();
        $this->tag_descriptions = $notification_target_ticket->tag_descriptions;
     }

    /**
     * Notification initialization
    */
    static function init() {
        global $DB;
        try {
            foreach((new self)->getEvents() as $event => $name) {

                $notificationId = 0;
                $notificationTemplateId = 0;

                $notificationTemplateDBTM = new \NotificationTemplate();
                if(!$notificationTemplateDBTM->getFromDBByCrit(['name' => $name])) {
                    $notificationTemplateId = $notificationTemplateDBTM->add([
                        'name'     => $name,
                        'itemtype' => 'Ticket',
                        'comment'  => "Created by the plugin ETN"
                    ]);
                }

                $notificationDBTM = new \Notification();
                if(!$notificationDBTM->getFromDBByCrit(['name' => $name])){
                    $notificationId = $notificationDBTM->add([
                        'name'                     => $name,
                        'entities_id'              => 0,
                        'is_recursive'             => 1,
                        'is_active'                => 1,
                        'itemtype'                 => 'Ticket',
                        'event'                    => $event,
                        'comment'                  => "Created by the plugin ETN"
                    ]);
                }

                if($notificationId && $notificationTemplateId){
                    $notifNotifTemplateDBTM = new \Notification_NotificationTemplate();
                    $fields = [
                        'notifications_id'          => $notificationId,
                        'mode'                      => 'mailing',
                        'notificationtemplates_id'  => $notificationTemplateId
                    ];
                    $notifications_id   = $notifNotifTemplateDBTM->add($fields);
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    static function addEvents(\NotificationTargetTicket $target) {
        $notif = new self;
        $target->events = array_merge($target->events, $notif->getEvents());
     }
}