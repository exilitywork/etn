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

define('PLUGIN_ETN_VERSION', '0.4.0');

// Minimal GLPI version, inclusive
define("PLUGIN_ETN_MIN_GLPI_VERSION", "10.0.1");
// Maximum GLPI version, exclusive
define("PLUGIN_ETN_MAX_GLPI_VERSION", "10.0.99");

use Glpi\Plugin\Hooks;
use GlpiPlugin\Etn\Process;

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_etn()
{
    global $PLUGIN_HOOKS;
    
    $PLUGIN_HOOKS['csrf_compliant']['etn'] = true;

    //$PLUGIN_HOOKS['post_show_item']['etn'] = ['GlpiPlugin\Etn\Process', 'postShowItem'];
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['etn'] = ['User' => ['GlpiPlugin\Etn\Process', 'updateUser']];
    $PLUGIN_HOOKS['item_get_datas']['etn'] = ['NotificationTargetTicket' => ['GlpiPlugin\Etn\Process', 'modifyNotification']];
    //$PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['etn'] = ['GlpiPlugin\Etn\Process', 'postShowItem'];
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_etn()
{
    return [
        'name'           => 'Extended Ticket\'s Notification',
        'version'        => PLUGIN_ETN_VERSION,
        'author'         => '<a href="https://www.linkedin.com/in/oleg-kapeshko-webdev-admin/">Oleg Kapeshko</a>',
        'license'        => 'GPL-2.0-or-later',
        'homepage'       => 'https://github.com/exilitywork/etn',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_ETN_MIN_GLPI_VERSION,
                'max' => PLUGIN_ETN_MAX_GLPI_VERSION,
            ]
        ]
    ];
}