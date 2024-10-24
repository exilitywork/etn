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

define('PLUGIN_ETN_VERSION', '1.3.1');

// Minimal GLPI version, inclusive
define("PLUGIN_ETN_MIN_GLPI_VERSION", "10.0.1");
// Maximum GLPI version, exclusive
define("PLUGIN_ETN_MAX_GLPI_VERSION", "10.0.99");

use Glpi\Plugin\Hooks;
use GlpiPlugin\Etn\Config;
use GlpiPlugin\Etn\SlaInfo;
use GlpiPlugin\Etn\TicketCategory;
use GlpiPlugin\Etn\TopRequesters;
use GlpiPlugin\Etn\Itemtype;

require_once 'vendor/autoload.php';

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_etn()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;
    
    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['etn'] = true;

    $PLUGIN_HOOKS[Hooks::ADD_CSS]['etn'][] = 'vendor/DataTables/datatables.min.css';
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['etn'] = 'vendor/DataTables/datatables.min.js';

    $menu = [];
    if(isset($_SESSION['glpiactiveprofile']) && Config::getOption('rating_profile') == $_SESSION['glpiactiveprofile']['id']) $menu['helpdesk']  = 'GlpiPlugin\Etn\Rating';
    if(\Session::haveRight('config', READ)) $menu['config'] = 'GlpiPlugin\Etn\Config';
    $PLUGIN_HOOKS['menu_toadd']['etn'] = $menu;
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['etn'] = ['User' => ['GlpiPlugin\Etn\User', 'updateUser']];
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['etn'] = ['ITILCategory' => ['GlpiPlugin\Etn\InactionTime', 'updateITILCategory']];
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['etn'] = ['ITILFollowup' => ['GlpiPlugin\Etn\Followup', 'preAddFollowup']];
    $items = ['ITILFollowup' => ['GlpiPlugin\Etn\Followup', 'addFollowup']];
    $itemtypes = (new Itemtype)->find();
    foreach($itemtypes as $itemtype) {
        $items += [$itemtype['itemtypes_id'] => ['GlpiPlugin\Etn\Itemtype', 'addItem']];
    }
    $PLUGIN_HOOKS[Hooks::ITEM_ADD]['etn'] = $items;
    $PLUGIN_HOOKS[Hooks::ITEM_GET_DATA]['etn'] = ['NotificationTargetTicket' => ['GlpiPlugin\Etn\Process', 'modifyNotification']];
    $PLUGIN_HOOKS[Hooks::POST_SHOW_TAB]['etn'] = ['GlpiPlugin\Etn\User', 'showUsernameField'];
    $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['etn'] = 'plugin_etn_hook_post_item_form';
    $PLUGIN_HOOKS[Hooks::ADD_RECIPIENT_TO_TARGET]['etn'] = ['GlpiPlugin\Etn\NotificationTargetInactionTime' => ['GlpiPlugin\Etn\InactionTime', 'addRecipient']];
    $PLUGIN_HOOKS[Hooks::ADD_RECIPIENT_TO_TARGET]['etn'] += ['GlpiPlugin\Etn\NotificationTargetExpiredSla' => ['GlpiPlugin\Etn\ExpiredSla', 'addRecipient']];
    $PLUGIN_HOOKS[Hooks::ADD_RECIPIENT_TO_TARGET]['etn'] += ['GlpiPlugin\Etn\NotificationTargetItemtype' => ['GlpiPlugin\Etn\Itemtype', 'addRecipient']];
    $PLUGIN_HOOKS[Hooks::ADD_RECIPIENT_TO_TARGET]['etn'] += ['GlpiPlugin\Etn\NotificationTargetTakeIntoAccountTime' => ['GlpiPlugin\Etn\TakeIntoAccountTime', 'addRecipient']];
    $PLUGIN_HOOKS[Hooks::ADD_RECIPIENT_TO_TARGET]['etn'] += ['NotificationTargetTicket' => ['GlpiPlugin\Etn\TicketCategory', 'addRecipient']];
    $PLUGIN_HOOKS[Hooks::ADD_RECIPIENT_TO_TARGET]['etn'] += ['GlpiPlugin\Etn\NotificationTargetProblemInactionTime' => ['GlpiPlugin\Etn\ProblemInactionTime', 'addRecipient']];

    $CFG_GLPI['notificationtemplates_types'][] = TopRequesters::class;
    $PLUGIN_HOOKS['item_get_events']['etn'] = [NotificationTargetTicket::class => ['GlpiPlugin\Etn\NotificationTargetTicketCategory', 'addEvents']];
    $PLUGIN_HOOKS[Hooks::ITEM_UPDATE]['etn'] = ['Ticket' => ['GlpiPlugin\Etn\TicketCategory', 'updateTicket']];
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
