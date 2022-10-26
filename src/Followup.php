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

class Followup extends \CommonDBTM
{
    /**
     * Add followup 
     *
     * @param $item            class ITILFollowup
     *
     * @return bool
    **/
    static function addFollowup($item) {
        global $CFG_GLPI;

         // is temporary BAD! solution for specific cases
        $e = new \Entity();
        $mails = [];
        if(!empty($CFG_GLPI['admin_email'])) array_push($mails, $CFG_GLPI['admin_email']);
        if(!empty($CFG_GLPI['smtp_sender'])) array_push($mails, $CFG_GLPI['smtp_sender']);
        if(!empty($CFG_GLPI['replyto_email'])) array_push($mails, $CFG_GLPI['replyto_email']);
        if(!empty($CFG_GLPI['noreply_email'])) array_push($mails, $CFG_GLPI['noreply_email']);
        $entities = $e->find();
        foreach($entities as $entity) {
            if(!empty($entity['admin_email'])) array_push($mails, $entity['admin_email']);
            if(!empty($entity['from_email'])) array_push($mails, $entity['from_email']);
            if(!empty($entity['replyto_email'])) array_push($mails, $entity['replyto_email']);
            if(!empty($entity['noreply_email'])) array_push($mails, $entity['noreply_email']);
        }
        $mails = array_unique($mails);
        $hasMail = false;
        foreach($mails as $mail) {
            if(strpos($item->input['content'], $mail) === 0 || strpos($item->input['content'], $mail) > 0) {
                $hasMail = true;
                break;
            }
        }
        if($hasMail
            && ((strpos($item->input['content'], date('d.m.Y')) === 0 || strpos($item->input['content'], date('d.m.Y')) > 0)
                || (strpos($item->input['content'],'From:') === 0 || strpos($item->input['content'], 'From:') > 0)
                || (strpos($item->input['content'], 'От:') === 0 || strpos($item->input['content'], 'От:') > 0))) {
            $item->input['content'] = explode($mail, $item->input['content'])[0];
            $arrContent = explode(date('d.m.Y'), $item->input['content']);
            if (count($arrContent) > 2) {
                unset($arrContent[count($arrContent) - 1]);
                $item->input['content'] = implode(date('d.m.Y'), $arrContent);
            } else {
                $item->input['content'] = $arrContent[0];
            }
            $item->input['content'] = explode('From:', $item->input['content'])[0];
            $item->input['content'] = explode('От:', $item->input['content'])[0];
        }
    }
}