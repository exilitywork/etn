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

class Config extends \CommonDBTM
{

    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0) {
        return __('Config', 'etn');
    }

    /**
     * Update config of plugin
     *
    **/
    static function updateConfig() {
        $configLDAP = new \AuthLDAP();
        $ldaps = $configLDAP->find();
        foreach($ldaps as $ldap) {
            $cfg = new Config();
            $config = current($cfg->find(['ldap_id' => $ldap['id']], [], 1));
            if(empty($ldap['picture_field']) && $config) {
                $cfg->getFromDB($config['id']);
                $cfg->delete($cfg->fields, 1);
                continue;
            }
            $cfg->fields['ldap_id'] = $ldap['id'];
            $cfg->fields['ldap_photo_field'] = $ldap['picture_field'];
            if($config) {
                if(isset($config['ldap_photo_field']) && $config['ldap_photo_field'] == $ldap['picture_field']) continue;
                $cfg->fields['id'] = $config['id'];
                $cfg->updateInDB(array_keys($cfg->fields));
            } else {
                $cfg->addToDB();
            }
        }
    }

    /**
     * Switch config of plugin
     *
    **/
    static function switchConfig($item) {
        $configLDAP = new \AuthLDAP();
        $configLDAP->getFromDB($item->fields['auths_id']);       
        $configLDAP->fields['id'] = $item->fields['auths_id']; 
        if(in_array(6, \Profile_User::getUserProfiles($item->fields['id']))) {
            $configLDAP->fields['picture_field'] = '';
            $configLDAP->updateInDB(array_keys($configLDAP->fields));
        } else {
            $cfg = new Config();
            $config = current($cfg->find(['ldap_id' => $configLDAP->fields['id']], [], 1));
            if($config && $configLDAP->fields['picture_field'] != $config['ldap_photo_field']) {
                $configLDAP->fields['picture_field'] = $config['ldap_photo_field'];
                $configLDAP->updateInDB(array_keys($configLDAP->fields));
            }
        }
    }
}
?>