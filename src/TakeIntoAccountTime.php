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

use DateTime;
use DateInterval;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class TakeIntoAccountTime extends \CommonDBTM {

    public $deduplicate_queued_notifications = false;

    /**
     * Get the tab name used for item
     *
     * @param object $item the item object
     * @param integer $withtemplate 1 if is a template form
     * @return string|array name of the tab
     */
    function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0) {
        return __('Время взятия заявок в работу');
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

        // Actions
        echo "<div class='firstbloc'>";
        echo "<form name='item_add_form$rand' id='item_add_form$rand' method='post' action='/plugins/etn/front/config.php'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='6'>".__('Действия и настройки')."</tr>";
        echo "<tr class='tab_bg_2'><td class='center'>";
        \Html::showSimpleForm(
            '/plugins/etn/front/calculatetaketime.php',
            [],
            '<i class="fa-fw far fa-clock"></i><span>'.__('Обновить время', 'etn').'</span>'
        );
        echo "</td><td>Протяженность периода в днях: </td><td class='center'>";
        \Dropdown::showTimeStamp('taketime_period', [
            'min'   => 1 * DAY_TIMESTAMP,
            'max'   => 30 * DAY_TIMESTAMP,
            'step'  => 1 * DAY_TIMESTAMP,
            'value' => isset($config['taketime_period']) ? $config['taketime_period'] : '',
        ]);
        echo "</td><td class='center'>";
        \Html::showSimpleForm(
            '/front/crontask.form.php',
            ['execute' => 'SendTakeIntoAccountTimeETN'],
            '<i class="fa-fw far fa-envelope"></i><span>'.__('Отправить на почту', 'etn').'</span>'
        );
        echo "</td></tr>";

        echo "</table>";
        echo '<div class="row"></div>';
        echo '<div class="card-body mx-n2 mb-4 border-top d-flex flex-row-reverse align-items-start flex-wrap">';
        $options['candel'] = false;
        echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
        \Html::closeForm();
        echo "</div></div>";

        // Recipients of report for avg takeintoaccounttime
        echo "<div class='firstbloc'>";
        echo "<form name='taketime_recipients_form$rand' id='taketime_recipients_form$rand' method='post' action='/plugins/etn/front/config.php'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='6'>" . __('Управление адресатами уведомлений') . "</tr>";
        echo "<tr class='tab_bg_2'><td class='center'>".__('User')."</td><td>";
        \User::dropdown([
            'right'         => "all",
            'entity'        => 0,
            'with_no_right' => true
        ]);
        echo "</td><td class='center'>";
        echo "<input type='submit' name='add_taketime_recipients' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
        echo "</td></tr>";
        echo "</table>";
        \Html::closeForm();
        echo "</div>";

        $iterator = (new TakeIntoAccountTimeRecipients)->find();
        $num = count($iterator);

        echo "<div class='spaced'>";
        echo "<form name='taketime_recipients_table$rand' id='taketime_recipients_table$rand' method='post' action=''>";
        if ($num > 0) {
            echo "<table class='tab_cadre_fixehov'>";
            $header = "<tr><th>".__('User')."</th><th></th></tr>";
            echo $header;
            foreach ($iterator as $data) {
                $user = current((new \User)->find(['id' => $data["users_id"]], [], 1));
                echo "<tr class='tab_bg_1'>";
                echo "<td>".$user['realname'].' '.$user['firstname']."</td>";
                echo '<td class="center"><a class="btn btn-sm btn-danger" href="?delete_taketime_recipients='.$data['id'].'"><span>Удалить</span></a></td>';
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

    static function addRecipient($item) {
        
        unset($item->target);

        $recipients = $item->options['recipients'];

        foreach($recipients as $id) {
            $email = current((new \UserEmail)->find(['users_id' => $id, 'is_default' => 1], [], 1))['email'];
            $user = current((new \User)->find(['id' => $id], [], 1));

            if ($item->getType() == 'GlpiPlugin\Etn\NotificationTargetExpiredSla') {
                $item->target[$email]['language'] = 'ru_RU';
                $item->target[$email]['additionnaloption']['usertype'] = 2;
                $item->target[$email]['username'] = $user['realname'].' '.$user['firstname'];
                $item->target[$email]['users_id'] = $id;
                $item->target[$email]['email'] = $email;
            }
        }
        //error_log(date('Y-m-d H:i:s')."TEST\n", 3, '/var/www/glpi/files/_log/test.log');
    }

    static function getUsers() {
        $users = [];
        $items = (new self)->find();
        foreach($items as $item) {
            array_push($users, $item['users_id']);
        }
        return array_unique($users);
    }

    /**
     * Check if the given DateTime object is a business day.
     *
     * @param DateTime $date
     * @return bool
     */
    static function isBusinessDay(DateTime $date)
    {
        // Weekends
        if ($date->format('N') > 5) {
            return false;
        }

        $holidays = (new \Holiday)->find();

        foreach ($holidays as $holiday) {
            $beginDate = new DateTime($holiday['begin_date']);
            $endDate = new DateTime($holiday['end_date']);
            if ($beginDate->format('Y-m-d') <= $date->format('Y-m-d') && $endDate->format('Y-m-d') >= $date->format('Y-m-d')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the available business time between two dates (in seconds).
     *
     * @param $start
     * @param $end
     * @return mixed
     */
    static function businessTime($start, $end)
    {
        $start = $start instanceof \DateTime ? $start : new DateTime($start);
        $end = $end instanceof \DateTime ? $end : new DateTime($end);
        $dates = [];

        $date = clone $start;

        while ($date <= $end) {

            $datesEnd = (clone $date)->setTime(23, 59, 59);

            if (self::isBusinessDay($date)) {
                $dates[] = (object)[
                    'start' => clone $date,
                    'end'   => clone ($end < $datesEnd ? $end : $datesEnd),
                ];
            }

            $date->modify('+1 day')->setTime(0, 0, 0);
        }
        return array_reduce($dates, function ($carry, $item) {

            $workTime = self::getWorkHours($item->start);

            $businessStart = (clone $item->start)->setTime($workTime['begin']['hours'], $workTime['begin']['minutes'], $workTime['begin']['seconds']);
            $businessEnd = (clone $item->start)->setTime($workTime['end']['hours'], $workTime['end']['minutes'], $workTime['end']['seconds']);

            $start = $item->start < $businessStart ? $businessStart : $item->start;
            $end = $item->end > $businessEnd ? $businessEnd : $item->end;

            //Diff in seconds
            return $carry += max(0, $end->getTimestamp() - $start->getTimestamp());
        }, 0);
    }

    static function getWorkHours(\DateTime $date) {
        $calendarID = 1;
        $cs = current((new \CalendarSegment)->find(['calendars_id' => $calendarID, 'day' => $date->format('w')]));
        $begin = new DateTime($cs['begin']);
        $result['begin']['hours'] = $begin->format('G');
        $result['begin']['minutes'] = $begin->format('i');
        $result['begin']['seconds'] = $begin->format('s');
        $end = new DateTime($cs['end']);
        $result['end']['hours'] = $end->format('G');
        $result['end']['minutes'] = $end->format('i');
        $result['end']['seconds'] = $end->format('s');
        return $result;
    }

    static function updateTime(\Ticket $ticket) {
        echo '<pre>';
        
        $beginDate = new DateTime(isset($ticket->input['date']) ? $ticket->input['date'] : $ticket->fields['date']);
        $endDate = clone $beginDate;
        $delay = isset($ticket->input['takeintoaccount_delay_stat']) ? $ticket->input['takeintoaccount_delay_stat'] : $ticket->fields['takeintoaccount_delay_stat'];
        $endDate->add(DateInterval::createFromDateString($delay.' seconds'));
        $taketime = new self;
        $taketime->fields['id'] = $ticket->input['id'];
        $taketime->fields['takeintoacoount_time'] = self::businessTime($beginDate, $endDate);
        $taketime->fields['date'] = $ticket->input['date'];
        if($curTaketime = current($taketime->find(['id' => $ticket->input['id']], [], 1))) {
            $taketime->updateInDB(array_keys($taketime->fields));
        } else {
            $taketime->addToDB();
        }
    }

    static function calculateTaketime($all = false) {
        global $DB;
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_tickets.id AS id',
                'glpi_tickets.takeintoaccount_delay_stat AS takeintoaccount_delay_stat',
                'glpi_tickets.date AS date'
            ],
            'DISTINCT'  => true,
            'FROM'      => 'glpi_tickets',
            'WHERE'     => [
                'glpi_tickets.is_deleted'   => 0,
                'glpi_tickets.date_mod'         => ['>=', $all ? '1970-01-01 00:00:00' : date('Y-m-d'.' 00:00:00')]
            ],
            'ORDERBY'   => 'id'
        ]);

        foreach($iterator as $id => $row) {
            $ticket = new \Ticket;
            $ticket->input['id'] = $row['id'];
            $ticket->input['date'] = $row['date'];
            $ticket->input['takeintoaccount_delay_stat'] = $row['takeintoaccount_delay_stat'];
            TakeIntoAccountTime::updateTime($ticket);
        }
        return count($iterator);
    }

    static function calculateAvgTaketime($dateBegin, $dateEnd) {
        global $DB;
        $iterator = $DB->request([
            'SELECT'    => [
                'AVG' => 'takeintoacoount_time AS avg_take_time'
            ],
            'DISTINCT'  => true,
            'FROM'      => 'glpi_plugin_etn_takeintoaccounttimes',
            'WHERE'     => [
                'takeintoacoount_time'   => ['>', 0],
                new \QueryExpression('date BETWEEN "'.$dateBegin.'" AND "'.$dateEnd.'"')
            ],
        ]);

        if(count($iterator) > 1) return false;

        foreach($iterator as $id => $row) {
            $minutes = floor($row['avg_take_time'] / 60);
            $seconds = $row['avg_take_time'] % 60;
            return sprintf('%02d м. %02d с.', $minutes, $seconds);
        }
        return false;
    }
}