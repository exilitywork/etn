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

class Itemtype extends \CommonDBTM {

    public $deduplicate_queued_notifications = false;

    static function addRecipient($item) {
        
        unset($item->target);

        $recipients = $item->options['recipients'];

        foreach($recipients as $id) {
            $email = current((new \UserEmail)->find(['users_id' => $id, 'is_default' => 1], [], 1))['email'];
            $user = current((new \User)->find(['id' => $id], [], 1));

            if ($item->getType() == 'GlpiPlugin\Etn\NotificationTargetItemtype') {
                $item->target[$email]['language'] = 'ru_RU';
                $item->target[$email]['additionnaloption']['usertype'] = 2;
                $item->target[$email]['username'] = $user['realname'].' '.$user['firstname'];
                $item->target[$email]['users_id'] = $id;
                $item->target[$email]['email'] = $email;
            }
        }
        //error_log(date('Y-m-d H:i:s')."TEST\n", 3, '/var/www/glpi/files/_log/test.log');
    }

    /**
     * Dropdown of itemtypes
     *
     * @param $value    integer / preselected value (default 0)
     * 
     * @return string id of the select
     **/
    static function getItemtypeDropdown($value = 0) {
        global $CFG_GLPI;
        
        $params['value']       = $value;
        $params['toadd']       = [];
        $params['on_change']   = '';
        $params['display']     = true;
        foreach($CFG_GLPI['asset_types'] as $key => $type) { 
            $params['toadd'] += [$type => getItemForItemtype($type)->getTypeName(1)];
        }
        foreach($CFG_GLPI['device_types'] as $key => $type) {
            $params['toadd'] += [$type => getItemForItemtype($type)->getTypeName(1)];
        }

        $items = [];
        if (count($params['toadd']) > 0) {
            $items = $params['toadd'];
        }

        $itemtypes = (new Itemtype)->find();
        foreach($itemtypes as $itemtype) {
            unset($items[$itemtype['itemtypes_id']]);
        }

        return \Dropdown::showFromArray('itemtype', $items, $params);
    }

    /**
     * Post add item 
     *
     * @param $item            
     *
     * @return bool
    **/
    static function addItem($item) {
        global $CFG_GLPI;

        $prefix = strtolower(get_class($item));

        $params =  [
            'entities_id'   => 0,
            'itemtype'      => $item->getTypeName(1),
            'name'          => $item->fields['name'],
            'serial'        => isset($item->fields['serial']) ? $item->fields['serial'] : '',
            'user'          => '',
            'type'          => '',
            'manufacturer'  => '',
            'model'         => '',
            'url'           => '<a href="'.$CFG_GLPI['url_base'].$item->getLinkURL().'">'.$CFG_GLPI['url_base'].$item->getLinkURL().'</a>'
        ];

        if(!empty($item->fields['users_id'])) {
            $user = current((new \User)->find(['id' => $item->fields['users_id']]));
            if(!empty($user['realname']) || !empty($user['firstname'])) {
                $params['user'] = $user['realname'].' '.$user['firstname'];
            } else {
                $params['user'] = $user['name'];
            }
        }

        if(isset($item->fields[$prefix.'types_id']) && $item->fields[$prefix.'types_id']) {
            $type = getItemForItemtype(get_class($item).'Type');
            if($type->getFromDB($item->fields[$prefix.'types_id'])) {
                $params['type'] = $type->fields['name'];
            }
        }

        if(isset($item->fields['manufacturers_id']) && $item->fields['manufacturers_id']) {
            $manufacturer = new \Manufacturer;
            if($manufacturer->getFromDB($item->fields['manufacturers_id'])) {
                $params['manufacturer'] = $manufacturer->fields['name'];
            }
        }
        if(isset($item->fields[$prefix.'models_id']) && $item->fields[$prefix.'models_id']) {
            $model = getItemForItemtype(get_class($item).'Model');
            if($model->getFromDB($item->fields[$prefix.'models_id'])) {
                $params['model'] = $model->fields['name'];
            }
        }
        $recipients = ItemtypeRecipients::getUsers();
        $params['recipients'] = $recipients;
        
        if(\NotificationEvent::raiseEvent('new_item', new Itemtype(), $params)) {
        }
    }

    /**
     * Get the tab name used for item
     *
     * @param object $item the item object
     * @param integer $withtemplate 1 if is a template form
     * @return string|array name of the tab
     */
    function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0) {
        return __('Устройства');
    }

    /**
     * Display the content of the tab
     *
     * @param object $item
     * @param integer $tabnum number of the tab to display
     * @param integer $withtemplate 1 if is a template form
     * @return boolean
     */
    static function displayTabContentForItem($item, $tabnum = 0, $withtemplate = 0) {
        switch ($tabnum) {
        case 1:
            (new self)->showForm();
            return true;
        }
        return false;
    }

    /**
     * Display form
     *
     * @param integer   $ID
     * @param array     $options
     * 
     * @return true
     */
    function showForm($ID = 1, $options = []) {
        global $CFG_GLPI;

        $config = Config::getConfig();

        $rand = mt_rand();

        // Itemtypes for reporting if item added
        echo "<div class='firstbloc'>";
        echo "<form name='item_add_form$rand' id='item_add_form$rand' method='post' action='/plugins/etn/front/config.php'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='6'>" . __('Типы устройств, для которых применяются уведомления') . "</tr>";

        echo "<tr class='tab_bg_2'><td class='center'>".__('Itemtype')."</td><td>";       
        Itemtype::getItemtypeDropdown();
        echo "</td><td class='center'>";
        echo "<input type='submit' name='add_itemtype' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
        echo "</td></tr>";

        echo "</table>";
        \Html::closeForm();
        echo "</div>";

        $iterator = (new Itemtype)->find();
        $num = count($iterator);

        echo "<div class='spaced'>";
        echo "<form name='item_add_table$rand' id='item_add_table$rand' method='post' action=''>";
        if ($num > 0) {
            echo "<table class='tab_cadre_fixehov'>";
            $header = "<tr><th>".__('Itemtype')."</th><th></th></tr>";
            echo $header;
            foreach ($iterator as $data) {
                echo "<tr class='tab_bg_1'>";
                echo "<td>".getItemForItemtype($data['itemtypes_id'])->getTypeName(1)."</td>";
                echo '<td class="center"><a class="btn btn-sm btn-danger" href="?delete_itemtype='.$data['id'].'"><span>Удалить</span></a></td>';
                echo "</tr>";
            }
            echo $header;
            echo "</table>";
        } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __('No item found') . "</th></tr>";
            echo "</table>\n";
        }

        \Html::closeForm();
        echo "</div>";

        // Recipients of new item notification
        echo "<div class='firstbloc'>";
        echo "<form name='item_recipients_form$rand' id='item_recipients_form$rand' method='post' action='/plugins/etn/front/config.php'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='6'>" . __('Управление адресатами уведомлений при добавлении оборудования') . "</tr>";

        echo "<tr class='tab_bg_2'><td class='center'>".__('User')."</td><td>";
        \User::dropdown([
            'right'         => "all",
            'entity'        => 0,
            'with_no_right' => true
        ]);
        echo "</td><td class='center'>";
        echo "<input type='submit' name='add_item_recipients' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
        echo "</td></tr>";

        echo "</table>";
        \Html::closeForm();
        echo "</div>";

        $iterator = (new ItemtypeRecipients)->find();
        $num = count($iterator);

        echo "<div class='spaced'>";
        echo "<form name='item_recipients_table$rand' id='item_recipients_table$rand' method='post' action=''>";
        if ($num > 0) {
            echo "<table class='tab_cadre_fixehov'>";
            $header = "<tr><th>".__('User')."</th><th></th></tr>";
            echo $header;
            foreach ($iterator as $data) {
                $user = current((new \User)->find(['id' => $data["users_id"]], [], 1));
                echo "<tr class='tab_bg_1'>";
                echo "<td>".$user['realname'].' '.$user['firstname']."</td>";
                echo '<td class="center"><a class="btn btn-sm btn-danger" href="?delete_item_recipients='.$data['id'].'"><span>Удалить</span></a></td>';
                echo "</tr>";
            }
            echo $header;
            echo "</table>";
        } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __('No item found') . "</th></tr>";
            echo "</table>\n";
        }

        \Html::closeForm();
        echo "</div>";

        return true;
    }

}