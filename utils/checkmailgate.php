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

    if(PHP_SAPI !== 'cli') die("Sorry. You can't access directly to this file");
    define("GLPI_ROOT", __DIR__."/../../..");

    include (GLPI_ROOT."/inc/autoload.function.php");
    include (GLPI_ROOT."/inc/db.function.php");
    include (GLPI_ROOT."/src/DBmysql.php");
    include (GLPI_ROOT."/src/DbUtils.php");
    include (GLPI_ROOT."/src/CommonGLPIInterface.php");
    include (GLPI_ROOT."/src/CommonGLPI.php");
    include (GLPI_ROOT."/src/CommonDBTM.php");
    include (GLPI_ROOT."/config/config_db.php");
    include (GLPI_ROOT."/src/CronTask.php");
    
    global $DB;
    $DB = new DB();
    $c = new Crontask;
    $cron = current($c->find(['name' => 'mailgate'], [], 1));
    $curTime = new DateTime();
    $time = new DateTime($cron['lastrun']);
    $time->modify('+5 minutes');
    if($curTime > $time) {
        echo(0);
    } else {
        echo(1);
    }

?>