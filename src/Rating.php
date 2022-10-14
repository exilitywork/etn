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

class Rating extends \CommonDBTM
{

    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0)
    {
        return __('Оценки', 'etn');
    }

    /**
     *  @see CommonGLPI::getMenuContent()
     *
     *  @since version 0.5.6
    **/
    static function getMenuContent() {
        global $CFG_GLPI;

        $menu = array();

        $menu['title']              = self::getMenuName();
        $menu['icon']               = 'far fa-star';
        $menu['page']               = '/plugins/etn/front/rating.php';
        $menu['links']['config']    = '/plugins/etn/front/config.php';
        return $menu;
    }

    /**
     *  @see CommonGLPI::getMenuContent()
     *
     *  @since version 0.5.6
    **/
    static function getRatingByTicketId($id) {
        global $CFG_GLPI;

        $menu = array();

        $menu['title']              = self::getMenuName();
        $menu['icon']               = 'far fa-star';
        $menu['page']               = '/plugins/etn/front/rating.php';
        $menu['links']['config']    = '/plugins/etn/front/config.php';
        return $menu;
    }

    /**
     *  get user's ID who rated ticket
     *
     *  @param $id            integer
     *
     *  @return string|bool
    **/
    static function getUserNameByTicketId($id) {
        global $DB;

        $rating = new self;
        if($r = current($rating->find(['tickets_id' => $id], [], 1))) {
            $u = new \User();
            if($user = current($u->find(['id' => $r['users_id']]))) {
                return $user['realname'].' '.$user['firstname'];
            };
        }
        return false;
    }
    
}
?>