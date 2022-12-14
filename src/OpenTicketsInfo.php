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

class OpenTicketsInfo extends \CommonDBTM
{
    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0) {
        return __('OpenTicketsInfo', 'etn');
    }

    /**
     * Calculate and save to DB SLA statisctics
     *
    **/
    static function calculateInfo() {
        global $DB;

        $data = [];

        $info = new self;

        $g = new \Group();
        $groups = $g->find();

        $dates = [];
        $sub1 = new \QuerySubQuery([
            'SELECT' => new \QueryExpression('DATE_FORMAT(date_mod, \'%Y-%m-%d\') as date'),
            'DISTINCT' => true,
            'FROM'   => 'glpi_tickets'
        ]);
        $sub2 = new \QuerySubQuery([
            'SELECT' => new \QueryExpression('DATE_FORMAT(closedate, \'%Y-%m-%d\') as date'),
            'DISTINCT' => true,
            'FROM'   => 'glpi_tickets'
        ]);
        $sub3 = new \QuerySubQuery([
            'SELECT' => new \QueryExpression('DATE_FORMAT(solvedate, \'%Y-%m-%d\') as date'),
            'DISTINCT' => true,
            'FROM'   => 'glpi_tickets'
        ]);
        $union = new \QueryUnion([$sub1, $sub2, $sub3], true);
        $req = $DB->request([
            'FROM'       => $union,
            'ORDERBY'   => 'date DESC'
        ]);
        foreach ($req as $id => $row) {
            if($row['date']) {
                array_push($dates, $row['date']);

            }
        }

        foreach($groups as $group) {

            $req = $DB->request([
                'SELECT'    => [
                    'glpi_tickets.id AS id',
                    'glpi_tickets.status AS status',
                    'glpi_groups_users.groups_id as groups_id',
                    new \QueryExpression('DATE_FORMAT(`glpi_tickets`.`date`, \'%Y-%m-%d\') as `date_begin`'),
                    new \QueryExpression('DATE_FORMAT(IFNULL(`glpi_tickets`.`closedate`, IFNULL(`glpi_tickets`.`solvedate`, `glpi_tickets`.`date_mod`)), \'%Y-%m-%d\') as `date_end`'),
                    'glpi_ticketsatisfactions.satisfaction as satisfaction',
                    new \QueryExpression('TIMESTAMPDIFF(MINUTE, glpi_tickets.date, glpi_tickets.solvedate) as time_to_solve'),
                ],
                'DISTINCT' => true,
                'FROM'      => 'glpi_tickets',
                'LEFT JOIN' => [
                    'glpi_tickets_users' => [
                        'FKEY' => [
                            'glpi_tickets' => 'id',
                            'glpi_tickets_users' => 'tickets_id',
                        ]
                    ],
                    'glpi_groups_users' => [
                        'FKEY' => [
                            'glpi_groups_users' => 'users_id',
                            'glpi_tickets_users' => 'users_id',
                        ]
                    ],
                    'glpi_users' => [
                        'FKEY' => [
                            'glpi_users' => 'id',
                            'glpi_tickets_users' => 'users_id',
                        ]
                    ],
                    'glpi_ticketsatisfactions' => [
                        'FKEY' => [
                            'glpi_tickets' => 'id',
                            'glpi_ticketsatisfactions' => 'tickets_id',
                        ]
                    ]
                ],
                'WHERE'     => [
                    'glpi_groups_users.groups_id'   => $group['id'],
                    'glpi_users.is_active'          => 1,
                    'glpi_tickets_users.type'       => 2,
                    'glpi_tickets.is_deleted'       => 0
                ],
            ]);
            if(count($req)) {
                foreach ($req as $id => $row) {
                    if(isset($data[$row['id']]['groups_id'])) {
                        $groups = explode(',', $data[$row['id']]['groups_id']);
                        if(!in_array($group['id'], $groups)) $data[$row['id']]['groups_id'] = $data[$row['id']]['groups_id'].','.$group['id'];
                    } else {
                        $data[$row['id']]['groups_id'] = $group['id'];
                    }
                    $data[$row['id']]['date_begin']     = $row['date_begin'];
                    $data[$row['id']]['date_end']       = $row['date_end'];
                    $data[$row['id']]['status']         = $row['status'];
                    $data[$row['id']]['satisfaction']   = $row['satisfaction'];
                    $data[$row['id']]['time_to_solve']  = $row['time_to_solve'];
                }
            }
        }
        foreach($data as $id => $item) {
	    unset($info->fields['satisfaction']);
	    unset($info->fields['time_to_solve']);
	    
            $info->fields['id']             = $id;
            $info->fields['groups_id']      = $item['groups_id'];
            $info->fields['status']         = $item['status'];
            if($item['satisfaction'] > 0) $info->fields['satisfaction']   = $item['satisfaction'];
            if($item['time_to_solve'] > 0) $info->fields['time_to_solve']  = $item['time_to_solve'];
            $info->fields['date_begin']     = (isset($item['date_begin']) ? $item['date_begin'] : '');
            $info->fields['date_end']       = (isset($item['date_end']) ? $item['date_end'] : '');
            if($itemCur = current($info->find(['id' => $id]))) {
                if($itemCur['date_begin'] != $info->fields['date_begin'] 
                        || $itemCur['date_end']        != $info->fields['date_end'] 
                        || $itemCur['groups_id']       != $info->fields['groups_id']
                        || $itemCur['status']          != $info->fields['status']
                        || ($info->fields['satisfaction'] > 0 && $itemCur['satisfaction'] != $info->fields['satisfaction'])
                        || ($info->fields['time_to_solve'] > 0 && $itemCur['time_to_solve']   != $info->fields['time_to_solve'])
                        ) {
                    $info->updateInDB(array_keys($info->fields));
                }
            } else {
                $info->addToDB();
            }  
        }        
    }
}