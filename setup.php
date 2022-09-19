<?php

/**
 * -------------------------------------------------------------------------
 * userphotoconv plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of userphotoconv.
 *
 * userphotoconv is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * any later version.
 *
 * userphotoconv is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with userphotoconv. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2022-2022 by Oleg Кapeshko
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/exilitywork/userphotoconv
 * -------------------------------------------------------------------------
 */

define('PLUGIN_USERPHOTOCONV_VERSION', '0.1.0');

// Minimal GLPI version, inclusive
define("PLUGIN_USERPHOTOCONV_MIN_GLPI_VERSION", "10.0.1");
// Maximum GLPI version, exclusive
define("PLUGIN_USERPHOTOCONV_MAX_GLPI_VERSION", "10.0.99");

use Glpi\Plugin\Hooks;

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_userphotoconv()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['userphotoconv'] = true;

    $PLUGIN_HOOKS['post_show_item']['userphotoconv'] = ['GlpiPlugin\Userphotoconv\Process', 'postShowItem'];
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['userphotoconv'] = ['User' => ['GlpiPlugin\Userphotoconv\Process', 'updateUser']];
    $PLUGIN_HOOKS['item_get_datas']['userphotoconv'] = ['NotificationTargetTicket' => ['GlpiPlugin\Userphotoconv\Process', 'modifyNotification']];
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_userphotoconv()
{
    return [
        'name'           => 'UserPhotoConv',
        'version'        => PLUGIN_USERPHOTOCONV_VERSION,
        'author'         => '<a href="https://www.linkedin.com/in/oleg-kapeshko-webdev-admin/">Oleg Kapeshko</a>',
        'license'        => 'GPL-2.0-or-later',
        'homepage'       => 'https://github.com/exilitywork/userphotoconv',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_USERPHOTOCONV_MIN_GLPI_VERSION,
                'max' => PLUGIN_USERPHOTOCONV_MAX_GLPI_VERSION,
            ]
        ]
    ];
}