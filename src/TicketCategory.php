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

class TicketCategory extends \CommonDBTM {

    public static function getTypeName($nb = 0) {
        return 'TicketCategory';
    }

    public static function addTicket(\Ticket $item) {
        $config = Config::getConfig();
        $category = new \ITILCategory;
        $categories = getSonsOf($category->getTable(), $config['expiredsla_categories_id']);
        if(in_array($item->fields['itilcategories_id'], $categories)) {
            \NotificationEvent::raiseEvent('new_ticket_category', $item);
        }
    }

    public static function updateTicket(\Ticket $item) {
        $config = Config::getConfig();
        $category = new \ITILCategory;
        $categories = getSonsOf($category->getTable(), $config['expiredsla_categories_id']);
        if(in_array($item->fields['itilcategories_id'], $categories) && $item->fields['status'] >= 5) {
            \NotificationEvent::raiseEvent('solved_ticket_category', $item);
        }
    }

    static function addRecipient($item) {
        $events = array_keys((new NotificationTargetTicketCategory)->getEvents());
        $config = Config::getConfig();
        $category = new \ITILCategory;
        $categories = getSonsOf($category->getTable(), $config['expiredsla_categories_id']);
        if(in_array($item->obj->fields['itilcategories_id'], $categories) && !in_array($item->raiseevent, $events)) {
            unset($item->target);
        }
    }
}