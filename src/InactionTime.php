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

class InactionTime extends \CommonDBTM {
    
    /**
     * Show additional field of inaction time for category
     *
     * @param $item            class ITILCategory
     *
     * @return bool
    **/
    static function showTimeField($item) {
        global $CFG_GLPI, $DB;
        try {
            $inact = current((new self)->find(['categories_id' => $item->fields['id']]));
            $out = '<table class="tab_cadre_fixe" style="width: auto;">';
            $out .= '<tr class="tab_bg_1"><th colspan="2">'.__('Настройки времени бездействия', 'etn') . '</th></tr>';
            $out .= '<tr class="tab_bg_1">';
            $out .= '<td>'.__('Максимальное время бездействия', 'etn').'</td>';
            $out .= '<td>';
            $out .= \Dropdown::showTimeStamp('inaction_time', [
                'min'       => 0,
                'max'       => 7 * DAY_TIMESTAMP,
                'step'      => $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP * 6,
                'value'     => isset($inact['inaction_time']) ? $inact['inaction_time'] : '',
                'display'   => false
            ]);
            $out .= '</td>';
            $out .= '</tr>';
            $out .= '</table>';
            echo $out;
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Update additional field of inaction time for category
     *
     * @param $item            class ITILCategory
     *
     * @return bool
    **/
    static function updateITILCategory($item) {
        try {
            $inact = new self;
            $inact->fields['categories_id'] = $item->input['id'];
            $inact->fields['inaction_time'] = $item->input['inaction_time'];
            if($i = current($inact->find(['categories_id' => $item->input['id']], [], 1))) {
                $inact->fields['id'] = $i['id'];
                $inact->updateInDB(array_keys($inact->fields));
            } else {
                $inact->addToDB();
            }
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Check violation of inaction time of ticket
     * 
     * @param $id            int
     *
     * @return bool
    **/
    static function checkExpiredInactionTime($id) {
        global $DB;
        $ticket = current((new \Ticket)->find(['id' => $id], [], 1));
        $inact = current((new self)->find(['categories_id' => $ticket['itilcategories_id']], [], 1));
        if(isset($inact['inaction_time']) && $inact['inaction_time'] > 0) {
            $inactionTime = $inact['inaction_time'];
        } else {
            $config = Config::getConfig();
            $inactionTime = $config['inaction_time'];
        }
        $deadline = date('Y-m-d H:i:s', strtotime('-'.$inactionTime.' seconds'));
        print_r($deadline.' -- ');
        $count = count($DB->request([
            'SELECT' => 'id', 
            'FROM' => 'glpi_itilfollowups',
            'WHERE' => [
                'items_id' => $id,
                'date_mod' => ['>', $deadline]
            ]
        ]));
        if ($count) return false;

        $count += count($DB->request([
            'SELECT' => 'id', 
            'FROM' => 'glpi_tickettasks',
            'WHERE' => [
                'tickets_id' => $id,
                'date_mod' => ['>', $deadline]
            ]
        ]));
        if ($count) return false;

        $count += count($DB->request([
            'SELECT' => 'id', 
            'FROM' => 'glpi_documents_items',
            'WHERE' => [
                'items_id' => $id,
                'date_mod' => ['>', $deadline]
            ]
        ]));
        if ($count) return false;

        $count += count($DB->request([
            'SELECT' => 'id', 
            'FROM' => 'glpi_ticketvalidations',
            'WHERE' => [
                'tickets_id' => $id,
                'OR' => [
                    'submission_date' => ['>', $deadline], 
                    'validation_date' => ['>', $deadline]
                ]
            ]
        ]));
        if ($count) return false;
        return true;
    }
}