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

class Menu extends \CommonGLPI
{

    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getMenuName($nb = 0) {
        return __('Menu', 'etn');
    }

    /**
     * @return array
    **/
    static function getMenuContent() {
        //print_r('TEST');
        //$url             = "";
        //$default_context = 0;
        /*if (class_exists("PluginTasklistsPreference")) {
        $default_context = PluginTasklistsPreference::checkDefaultType(Session::getLoginUserID());
        }
        if ($default_context > 0) {
        $url = "?itemtype=PluginTasklistsKanban&glpi_tab=PluginTasklistsKanban$" . $default_context;
        }*/

        $menu          = [];
        $menu['title'] = self::getMenuName(2);

        //$menu['title']  = __('Оценки', 'etn');
        $menu['icon']   = '';
        $menu['page']   = '/plugins/etn/front/config.php';
        //$menu['page']  = '/plugins/hardwareaudit/front/change.php';
        //$menu['links']['search'] = '/plugins/hardwareaudit/front/change.php';
        //if (PluginTasklistsTask::canCreate()) {
        //$menu['links']['add']      = '/plugins/hardwareaudit/front/change.php';
        //$menu['links']['template'] = '/plugins/hardwareaudit/front/change.php';
        //}
        //$menu['links']['config'] = '/plugins/hardwareaudit/front/config.php';

        return $menu;
    }
}
?>