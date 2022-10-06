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
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Etn\Rating;
use GlpiPlugin\Etn\Priority;
use GlpiPlugin\Etn\Config;

global $CFG_GLPI, $DB;

include("../../../inc/includes.php");

Session::checkLoginUser();

if(Config::getOption('rating_profile') != $_SESSION['glpiactiveprofile']['id']) Html::displayRightError();

if(Session::getLoginUserID()) {
    if (Session::getCurrentInterface() == "helpdesk") {
        Html::displayRightError();
    } else {
        Html::header(Rating::getTypeName(1), $_SERVER['PHP_SELF'], 'helpdesk', 'GlpiPlugin\Etn\Rating');
    }
}

if (empty($_GET["date1"]) && empty($_GET["date2"])) {
    $datetime = new DateTime();
    $datetime->modify('-30 day');
    $_GET["date1"] = $datetime->format('Y-m-d');
    $_GET["date2"] = date("Y-m-d");
}

echo "<div class='center'><form method='get' name='form' action='".$CFG_GLPI['url_base']."/plugins/etn/front/rating.php'>";
echo "<table class='tab_cadre_fixe' style='width: auto'>";
echo '<tr><th colspan="3" class="center">'.__('Статистика по оценкам решенных заявок', 'etn').'</th></tr>';
echo "<tr class='tab_bg_2'>";
echo "<td class='right'>" . __('Start date') . "</td><td>";
\Html::showDateField("date1", ['value' => $_GET["date1"]]);
echo "</td>";

echo "<td rowspan='2' class='center'>";
echo "<input type='submit' class='btn btn-primary' name='submit' value=\"" . __s('Display report') . "\"></td>" .
     "</tr>";

echo "<tr class='tab_bg_2'><td class='right'>" . __('End date') . "</td><td>";
\Html::showDateField("date2", ['value' => $_GET["date2"]]);
echo "</td></tr>";
echo "</table>";
echo "</form>";
echo "</div>";

/*$req = $DB->request([
    'SELECT'    => [
        'glpi_tickets_users.users_id AS user', 
        'COUNT' => 'glpi_tickets.id AS count', 
        'SUM' => 'glpi_ticketsatisfactions.satisfaction AS rating'
    ],
    'FROM' => ['glpi_tickets'],
    'LEFT JOIN'   => [
        'glpi_tickets_users' => [
            'FKEY'   => [
                'glpi_tickets'          => 'id',
                'glpi_tickets_users'=>'tickets_id'
            ]
        ],
        'glpi_ticketsatisfactions' => [
            'FKEY'   => [
                'glpi_tickets'          => 'id',
                'glpi_ticketsatisfactions'=>'tickets_id'
            ]
        ]
    ],
    'WHERE' => [
        'glpi_tickets.is_deleted' => 0,
        'glpi_ticketsatisfactions.satisfaction' => ['>' , 0],
        'glpi_tickets_users.type' => 2,
        'glpi_tickets.closedate' => ['>' , $_GET['date1'].' 00:00:00'],
        'glpi_tickets.closedate' => ['<' , $_GET['date2'].' 23:59:59']
    ],
    'GROUPBY' => 'glpi_tickets_users.users_id',
    'LIMIT' => 100
]);*/
$req = $DB->request("
    SELECT `glpi_tickets_users`.`users_id` AS `user`, COUNT(`glpi_tickets`.`id`) AS `count`, SUM(`glpi_ticketsatisfactions`.`satisfaction`) AS `rating` 
    FROM `glpi_tickets` 
    LEFT JOIN `glpi_tickets_users` ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`) 
    LEFT JOIN `glpi_ticketsatisfactions` ON (`glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id`) 
    WHERE 
        `glpi_tickets`.`is_deleted` = '0' 
        AND `glpi_ticketsatisfactions`.`satisfaction` IS NOT NULL 
        AND `glpi_tickets_users`.`type` = '2' 
        AND `glpi_tickets`.`closedate` > '".$_GET['date1'].' 00:00:00'."'
        AND `glpi_tickets`.`closedate` < '".$_GET['date2'].' 23:59:59'."' 
    GROUP BY `glpi_tickets_users`.`users_id` 
    LIMIT 100
");

echo '
<table id="rating" class="display" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>'.__('Technician').'</th>
            <th>'.__('Number of solved tickets').'</th>
            <th>'.__('Средняя оценка', 'etn').'</th>
        </tr>
    </thead>
 
    <tfoot>
    <tr>
        <th>'.__('Technician').'</th>
        <th>'.__('Number of solved tickets').'</th>
        <th>'.__('Средняя оценка', 'etn').'</th>
    </tr>
    </tfoot>
 
    <tbody>';
foreach ($req as $id => $row) {
    $user = new \User();
    if($user->getFromDB($row['user'])) {
        echo '<tr>';
        echo '<td>'.$user->fields['realname'].' '.$user->fields['firstname'].'</td>';
        echo '<td>'.$row['count'].'</td>';
        echo '<td>'.round($row['rating'] / $row['count'], 2).'</td>';
        echo '</tr>';
    }
}
echo '</tbody>
</table>';
echo "
<script>
    $(document).ready(function() {
        /*var eventFired = function ( type ) {
            var n = $('#demo_info')[0];
            n.innerHTML += '<div>'+type+' event - '+new Date().getTime()+'</div>';
            n.scrollTop = n.scrollHeight;       
        }*/
    
        $('#rating')
            /*.on( 'order.dt',  function () { eventFired( 'Order' ); } )
            .on( 'search.dt', function () { eventFired( 'Search' ); } )
            .on( 'page.dt',   function () { eventFired( 'Page' ); } )*/
            .dataTable( {
                paging: false,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            } );
    } );
</script>
";

if(Session::getLoginUserID()) {
    if (Session::getCurrentInterface() == "helpdesk") {
        Html::helpFooter();
    } else {
        Html::footer();
    }
}