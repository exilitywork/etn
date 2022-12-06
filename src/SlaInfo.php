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

class SlaInfo extends \CommonDBTM
{
    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0) {
        return __('SLAInfo', 'etn');
    }

    /**
     * Calculate and save to DB SLA statisctics
     *
    **/
    static function calculateSlaInfo() {
        global $DB;

        $data = [];

        $s = new SlaInfo();

        $g = new \Group();
        $groups = $g->find();

        foreach($groups as $group) {

            $reqAllSla = $DB->request([
                'SELECT'    => [
                    'COUNT DISTINCT' => 'glpi_tickets.id AS count',
                    new \QueryExpression('DATE_FORMAT(`glpi_tickets`.`solvedate`, \'%Y-%m-%d\') as `sla_date`')
                ],
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
                    ]
                ],
                'WHERE'     => [
                    'glpi_tickets.status'  => ['<', 7],
                    new \QueryExpression('`glpi_tickets`.`solvedate` IS NOT NULL'),
                    [
                        'OR' => [
                            new \QueryExpression('`glpi_tickets`.`time_to_own` IS NOT NULL'), 
                            new \QueryExpression('`glpi_tickets`.`time_to_resolve` IS NOT NULL')
                        ]
                    ],
                    'glpi_groups_users.groups_id'   => $group['id'],
                    'glpi_users.is_active'          => 1,
                    'glpi_tickets_users.type'       => 2,
                    'glpi_tickets.is_deleted'       => 0
                ],
                'GROUPBY' => 'sla_date'
            ]);
            if(count($reqAllSla)) {
                foreach ($reqAllSla as $id => $row) {
                    $data[$group['id']][$row['sla_date']]['sla_all'] = $row['count'];
                }
            }
            $reqAllFalse = $DB->request([
                'SELECT'    => [
                    'COUNT DISTINCT' => 'glpi_tickets.id AS count',
                    new \QueryExpression('DATE_FORMAT(`glpi_tickets`.`solvedate`, \'%Y-%m-%d\') as `sla_date`')
                ],
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
                    ]
                ],
                'WHERE'     => [
                    'glpi_tickets.status'  => ['<', 7],
                    new \QueryExpression('`glpi_tickets`.`solvedate` IS NOT NULL'),
                    [
                        'OR' => [
                            new \QueryExpression('`glpi_tickets`.`time_to_own` IS NOT NULL'), 
                            new \QueryExpression('`glpi_tickets`.`time_to_resolve` IS NOT NULL')
                        ],
                        'OR' => [
                            'glpi_tickets.takeintoaccount_delay_stat' => ['<', new \QueryExpression('TIMESTAMPDIFF(SECOND, `glpi_tickets`.`date_creation`, `glpi_tickets`.`time_to_own`)')], 
                            new \QueryExpression('TIMESTAMPDIFF(SECOND, `glpi_tickets`.`time_to_resolve`, `glpi_tickets`.`solvedate`) > 0')
                        ]
                    ],
                    'glpi_groups_users.groups_id'   => $group['id'],
                    'glpi_users.is_active'          => 1,
                    'glpi_tickets_users.type'       => 2,
                    'glpi_tickets.is_deleted'       => 0
                ],
                'GROUPBY' => 'sla_date'
            ]);
            if(count($reqAllFalse)) {
                foreach ($reqAllFalse as $id => $row) {
                    $data[$group['id']][$row['sla_date']]['sla_false'] = $row['count'];
                }
            }
            
        }
        foreach($data as $group => $slas) {
            ksort($slas);
            $s->fields['sla_all'] = 0;
            $s->fields['sla_false'] = 0;
            foreach($slas as $date => $sla) {            
                unset($s->fields['id']);
                $s->fields['date']      = $date;
                $s->fields['groups_id'] = $group;
                $s->fields['sla_all']   = $s->fields['sla_all'] + (isset($sla['sla_all']) ? $sla['sla_all'] : 0);
                $s->fields['sla_false'] = $s->fields['sla_false'] + (isset($sla['sla_false']) ? $sla['sla_false'] : 0);
                if($item = current($s->find(['date' => $date, 'groups_id' => $group]))) {
                    if($item['sla_all'] != $s->fields['sla_all'] || $item['sla_false'] != $s->fields['sla_false']) {
                        $s->fields['id'] = $item['id'];
                        $s->updateInDB(array_keys($s->fields));
                    }
                } else {
                    $s->addToDB();
                }
            }
            
        }        
    }
}