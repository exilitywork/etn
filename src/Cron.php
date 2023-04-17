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

class Cron extends \CommonDBTM
{

    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0) {
        return __('Cron', 'etn');
    }

    static function cronInfo($name) {
        switch ($name) {
            case 'SendMessageTelegeramETN':
                return array('description' => __('Отправка уведомлений в Telegram', 'etn'));
            case 'ListenMessageTelegramETN':
                return array('description' => __('Обработка новых сообщений в Telegram', 'etn'));
            case 'TicketStatCalculationETN':
                return array('description' => __('Расчет статистики по заявкам для Grafana', 'etn'));
            case 'SendTopRequestersETN':
                return array('description' => __('Отправка списка ТОП инициаторов по завкам за месяц', 'etn'));
            case 'CheckInactionTimeTicketETN':
                return array('description' => __('Проверка времени бездействия по заявкам', 'etn'));
        }
  
        return array();
    }
  
    static function cronSendMessageTelegeramETN($task) {
        global $CFG_GLPI, $DB;
        return true;
        try {
            $bot = new \TelegramBot\Api\BotApi(Config::getOption('bot_token'));

            $rate = 4;
            $ticketID = 262357;
            $requesters = [];
            $assigns = [];
            $reqTitle = __('Инициатор', 'etn').': ';
            $specTitle = __('Специалист', 'etn').': ';

            $ticket = new \Ticket();
            $ticket->getFromDB($ticketID);
            $ticketURL = $CFG_GLPI['url_base'].$ticket->getLinkURL();
            $class = new $ticket->userlinkclass();
            $ticketsUser = $class->getActors($ticketID);
            $reqs = $ticketsUser[\CommonITILActor::REQUESTER];
            $specs = $ticketsUser[\CommonITILActor::ASSIGN];
            $u = new \User();
            foreach($reqs as $requester) {
                $user = current($u->find(['id' => $requester['users_id']]));
                array_push($requesters, $user['realname'].' '.$user['firstname']);
            }
            foreach($specs as $assign) {
                $user = current($u->find(['id' => $assign['users_id']]));
                array_push($assigns, $user['realname'].' '.$user['firstname']);
            }
            if(count($reqs) > 1) $reqTitle = __('Инициаторы', 'etn').': ';
            if(count($specs) > 1) $specTitle = __('Специалисты', 'etn').': ';
            $message = "По заявке ".$ticketURL." получена низкая оценка - ".$rate."\n";
            $message .= $reqTitle.implode(', ', $requesters)."\n";
            $message .= $specTitle.implode(', ', $assigns)."\n";
            $config = Config::getConfig();                
        } catch (Exception $e) {
            $e->getMessage();
            print_r($e->getMessage());
            $task->log("Error.");
            return false;
        }

        $task->addVolume(5);
        $task->log("Все хорошо");
        return true;
        $task->log("Error.");
        return false;
    }

    static function cronListenMessageTelegramETN($task) {
        global $DB;
        try {
            $bot = new \TelegramBot\Api\BotApi(Config::getOption('bot_token'));
            $config = Config::getConfig();
            $config['updateId'] = isset($config['updateId']) ? $config['updateId'] : 0;
            foreach($bot->getUpdates($config['updateId'] + 1) as $update){
                if($message = $update->getMessage()) {
                    $id = $message->getChat()->getId();
                    if($username = $message->getChat()->getUsername()) {
                        switch(Chat::updateChat($username, $id)) {
                            case 1:
                                $text = __('Ваш ID успешно зарегистрирован!', 'etn');
                                break;
                            case 2:
                                $text = __('Ваш ID успешно обновлен!', 'etn');
                                break;
                            case 3:
                                $text = __('Вы уже зарегистрированы!', 'etn');
                                break;
                            default:
                                $text = __('Ошибка регистрации!', 'etn');
                        }
                        $bot->sendMessage($id, $text);
                    }
                }
            }

            if(isset($update)) {
                Config::updateConfig(['updateId' => $update->getUpdateId()]);
            }
            
        } catch (Exception $e) {
            $e->getMessage();
            print_r($e->getMessage());
            $task->log($e->getMessage());
            return false;
        }
        $task->addVolume(1);
        return true;
    }

    static function cronTicketStatCalculationETN($task) {
        global $DB;
        try {
            SlaInfo::calculateSlaInfo();
            OpenTicketsInfo::calculateInfo();
        } catch (Exception $e) {
            $e->getMessage();
            print_r($e->getMessage());
            $task->log($e->getMessage());
            return false;
        }
        $task->addVolume(1);
        return true;
    }

    static function cronSendTopRequestersETN($task) {
        global $DB;

        if(\Session::isCron() && date('Y-m-d H') != gmdate('Y-m-d', strtotime('last sat of')).' 10') {
            return true;
        }
        
        try {
            $top = [];
            $iterator = $DB->request([
                'SELECT'    => [
                    'COUNT DISTINCT' => 'glpi_tickets.id AS cnt',
                    new \QueryExpression('CONCAT(glpi_users.realname, " ", glpi_users.firstname) AS requester'),
                ],
                'FROM'      => 'glpi_tickets',
                'LEFT JOIN' => [
                    'glpi_tickets_users' => [
                        'FKEY' => [
                            'glpi_tickets' => 'id',
                            'glpi_tickets_users' => 'tickets_id',
                        ]
                    ],
                    'glpi_users' => [
                        'FKEY' => [
                            'glpi_users' => 'id',
                            'glpi_tickets_users' => 'users_id',
                        ]
                    ],
                ],
                'WHERE'     => [
                    'glpi_users.is_active'          => 1,
                    'glpi_tickets_users.type'       => 1,
                    'glpi_tickets.is_deleted'       => 0,
                    new \QueryExpression('glpi_tickets.date >= DATE_FORMAT(NOW(), \'%Y-%m-01\')')
                ],
                'GROUPBY' => 'requester',
                'ORDERBY' => 'cnt DESC',
                'LIMIT' => 10
            ]);
            foreach($iterator as $id => $row) {
                $row['number'] = $id + 1;
                array_push($top, $row);
            }
            
            if ($cnt = count($top)) {
                $params =  [
                    'entities_id'  => 0,
                    'toprequesters' => $top
                ];
                if(\NotificationEvent::raiseEvent('top_requesters', new TopRequesters(), $params)) {
                    $task->addVolume($cnt);
                    $task->log("Action successfully completed");
                    return true;
                }
            }
        } catch (Exception $e) {
            $e->getMessage();
            print_r($e->getMessage());
            $task->log($e->getMessage());
            return false;
        }
        return false;
    }

    static function cronCheckInactionTimeTicketETN($task) {
        global $DB;

        $config = Config::getConfig();

        if(\Session::isCron() && date('H') != explode(':', $config['inaction_send_hour'])[0]) {
            return true;
        }

        $allCnt = 0;
        $groups = InactionTime_Group_User::getGroups();
        try {
            foreach($groups as $group) {
                $expired = [];
                $iterator = $DB->request([
                    'SELECT'    => [
                        'glpi_tickets.id AS id',
                        'glpi_tickets.name AS name',
                        'glpi_tickets.date AS date'
                    ],
                    'DISTINCT' => true,
                    'FROM'      => 'glpi_tickets',
                    'LEFT JOIN' => [
                        'glpi_tickets_users' => [
                            'FKEY' => [
                                'glpi_tickets'          => 'id',
                                'glpi_tickets_users'    => 'tickets_id'
                            ]
                        ],
                        'glpi_users' => [
                            'FKEY' => [
                                'glpi_users'            => 'id',
                                'glpi_tickets_users'    => 'users_id',
                            ]
                        ],
                        'glpi_groups_users' => [
                            'FKEY' => [
                                'glpi_tickets_users'    => 'users_id',
                                'glpi_groups_users'     => 'users_id',
                            ]
                        ],
                    ],
                    'WHERE'     => [
                        'glpi_tickets.is_deleted'       => 0,
                        'glpi_tickets.status'           => ['<', 5],
                        'glpi_groups_users.groups_id'   => $group,
                        'glpi_users.is_active'          => 1,
                        'glpi_tickets_users.type'       => 2
                    ],
                    'ORDERBY'   => 'id'
                ]);

                foreach($iterator as $id => $row) {
                    if(InactionTime::checkExpiredInactionTime($row['id'])) {
                        $row['requesters'] = '';
                        $row['specs'] = '';
                        $req = $DB->request([
                            'SELECT'    => [
                                'glpi_users.realname AS realname',
                                'glpi_users.firstname AS firstname'
                            ],
                            'FROM'      => 'glpi_users',
                            'LEFT JOIN' => [
                                'glpi_tickets_users' => [
                                    'FKEY' => [
                                        'glpi_users' => 'id',
                                        'glpi_tickets_users' => 'users_id',
                                    ]
                                ],
                            ],
                            'WHERE'     => [
                                'glpi_users.is_active'          => 1,
                                'glpi_tickets_users.type'       => 1,
                                'glpi_tickets_users.tickets_id' => $row['id']
                            ]
                        ]);
                        foreach($req as $user) {
                            if($row['requesters']) $row['requesters'] .= '<br>';
                            $row['requesters'] .= $user['realname'].' '.$user['firstname'];
                        }
                        $spec = $DB->request([
                            'SELECT'    => [
                                'glpi_users.realname AS realname',
                                'glpi_users.firstname AS firstname'
                            ],
                            'FROM'      => 'glpi_users',
                            'LEFT JOIN' => [
                                'glpi_tickets_users' => [
                                    'FKEY' => [
                                        'glpi_users' => 'id',
                                        'glpi_tickets_users' => 'users_id',
                                    ]
                                ],
                            ],
                            'WHERE'     => [
                                'glpi_users.is_active'          => 1,
                                'glpi_tickets_users.type'       => 2,
                                'glpi_tickets_users.tickets_id' => $row['id']
                            ]
                        ]);
                        foreach($spec as $user) {
                            if($row['specs']) $row['specs'] .= '<br>';
                            $row['specs'] .= $user['realname'].' '.$user['firstname'];
                        }
                        array_push($expired, $row);
                    }
                }
                //error_log(date('Y-m-d H:i:s')." Группа: ".$group." expired: ".count($iterator)."\n", 3, '/var/www/glpi/files/_log/test.log');
                if ($cnt = count($expired)) {
                    $groupname = current((new \Group)->find(['id' => $group], [], 1))['name'];
                    $recipients = InactionTime_Group_User::getUsersForGroup($group);
                    $params =  [
                        'entities_id'  => 0,
                        'inactiontime' => $expired,
                        'recipients' => $recipients,
                        'groupname' => $groupname
                    ];
                    if(\NotificationEvent::raiseEvent('inaction_time', new InactionTime(), $params)) {
                        $allCnt += $cnt;
                    }
                }
            }
            $task->addVolume($allCnt);
            $task->log("Action successfully completed");
            return true;
        } catch (Exception $e) {
            $e->getMessage();
            print_r($e->getMessage());
            $task->log($e->getMessage());
            return false;
        }
        return false;
    }

    static function cronExpiredSlaETN($task) {
        global $DB;

        $config = Config::getConfig();

        if(\Session::isCron() && date('H') != explode(':', $config['expiredsla_send_hour'])[0]) {
            return true;
        }

        $allCnt = 0;
        $category = new \ITILCategory;
        $categories = getSonsOf($category->getTable(), $config['expiredsla_categories_id']);
        
        try {
            $expired = [];
            $iterator = $DB->request([
                'SELECT'    => [
                    'glpi_tickets.id AS id',
                    'glpi_tickets.name AS name',
                    'glpi_tickets.date AS date'
                ],
                'DISTINCT' => true,
                'FROM'      => 'glpi_tickets',
                'LEFT JOIN' => [
                    'glpi_tickets_users' => [
                        'FKEY' => [
                            'glpi_tickets'          => 'id',
                            'glpi_tickets_users'    => 'tickets_id'
                        ]
                    ],
                    'glpi_users' => [
                        'FKEY' => [
                            'glpi_users'            => 'id',
                            'glpi_tickets_users'    => 'users_id',
                        ]
                    ],
                    'glpi_groups_users' => [
                        'FKEY' => [
                            'glpi_tickets_users'    => 'users_id',
                            'glpi_groups_users'     => 'users_id',
                        ]
                    ],
                ],
                'WHERE'     => [
                    'glpi_tickets.is_deleted'       => 0,
                    'glpi_tickets.itilcategories_id'   => $categories,
                    'glpi_users.is_active'          => 1,
                    'glpi_tickets_users.type'       => 2,
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
                ],
                'ORDERBY'   => 'id'
            ]);
            echo '<pre>';
            
            foreach($iterator as $id => $row) {
                $row['requesters'] = '';
                $row['specs'] = '';
                $req = $DB->request([
                    'SELECT'    => [
                        'glpi_users.realname AS realname',
                        'glpi_users.firstname AS firstname'
                    ],
                    'FROM'      => 'glpi_users',
                    'LEFT JOIN' => [
                        'glpi_tickets_users' => [
                            'FKEY' => [
                                'glpi_users' => 'id',
                                'glpi_tickets_users' => 'users_id',
                            ]
                        ],
                    ],
                    'WHERE'     => [
                        'glpi_users.is_active'          => 1,
                        'glpi_tickets_users.type'       => 1,
                        'glpi_tickets_users.tickets_id' => $row['id']
                    ]
                ]);
                foreach($req as $user) {
                    if($row['requesters']) $row['requesters'] .= '<br>';
                    $row['requesters'] .= $user['realname'].' '.$user['firstname'];
                }
                $spec = $DB->request([
                    'SELECT'    => [
                        'glpi_users.realname AS realname',
                        'glpi_users.firstname AS firstname'
                    ],
                    'FROM'      => 'glpi_users',
                    'LEFT JOIN' => [
                        'glpi_tickets_users' => [
                            'FKEY' => [
                                'glpi_users' => 'id',
                                'glpi_tickets_users' => 'users_id',
                            ]
                        ],
                    ],
                    'WHERE'     => [
                        'glpi_users.is_active'          => 1,
                        'glpi_tickets_users.type'       => 2,
                        'glpi_tickets_users.tickets_id' => $row['id']
                    ]
                ]);
                foreach($spec as $user) {
                    if($row['specs']) $row['specs'] .= '<br>';
                    $row['specs'] .= $user['realname'].' '.$user['firstname'];
                }
                array_push($expired, $row);
            }

            if ($cnt = count($expired)) {
                $categoryname = current($category->find(['id' => $config['expiredsla_categories_id']], [], 1))['name'];
                $recipients = ExpiredSla::getUsers();
                $params =  [
                    'entities_id'  => 0,
                    'expiredsla' => $expired,
                    'recipients' => $recipients,
                    'categoryname' => $categoryname
                ];
                if(\NotificationEvent::raiseEvent('expired_sla', new ExpiredSla(), $params)) {
                    $allCnt += $cnt;
                }
            }
            $task->addVolume($allCnt);
            $task->log("Action successfully completed");
            return true;
        } catch (Exception $e) {
            $e->getMessage();
            print_r($e->getMessage());
            $task->log($e->getMessage());
            return false;
        }
        return false;
    }

}
?>