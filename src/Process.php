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
 * @copyright Copyright (C) 2022-2023 by Oleg Кapeshko
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/exilitywork/etn
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Etn;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class Process extends \CommonDBTM
{

    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0)
    {
        return __('Processing', 'etn');
    }

    /**
     * post show item
     *
     * @param $item            class
     *
    **/
    static function postShowItem($item) {
        return $item;
    }

    /**
     * Add new tag of user's photo to ticket's notifications
     *
     * @param $item            class Ticket
     *
     * @return $item
    **/
    static function modifyNotification($item) {
        global $CFG_GLPI;

        $item->data['##ticket.assigntouserphoto.small##'] = '';
        $item->tag_descriptions['tag']['##ticket.assigntouserphoto.small##'] = [
            'tag'       => 'ticket.assigntouserphoto.small',
            'value'     => 1,
            'label'     => __('Фото специалиста', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.assigntouserphoto.medium##'] = '';
        $item->tag_descriptions['tag']['##ticket.assigntouserphoto.medium##'] = [
            'tag'       => 'ticket.assigntouserphoto.medium',
            'value'     => 1,
            'label'     => __('Фото специалиста', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.assigntouserphoto.large##'] = '';
        $item->tag_descriptions['tag']['##ticket.assigntouserphoto.large##'] = [
            'tag'       => 'ticket.assigntouserphoto.large',
            'value'     => 1,
            'label'     => __('Фото специалиста', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.assigntousertitle##'] = '';
        $item->tag_descriptions['tag']['##ticket.assigntousertitle##'] = [
            'tag'       => 'ticket.assigntousertitle',
            'value'     => 1,
            'label'     => __('Должность специалиста', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.rating.0##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.0##'] = [
            'tag'       => 'ticket.rating.0',
            'value'     => 1,
            'label'     => __('Оценка 0', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.rating.1##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.1##'] = [
            'tag'       => 'ticket.rating.1',
            'value'     => 1,
            'label'     => __('Оценка 1', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.rating.2##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.2##'] = [
            'tag'       => 'ticket.rating.2',
            'value'     => 1,
            'label'     => __('Оценка 2', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.rating.3##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.3##'] = [
            'tag'       => 'ticket.rating.3',
            'value'     => 1,
            'label'     => __('Оценка 3', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.rating.4##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.4##'] = [
            'tag'       => 'ticket.rating.4',
            'value'     => 1,
            'label'     => __('Оценка 4', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.rating.5##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.5##'] = [
            'tag'       => 'ticket.rating.5',
            'value'     => 1,
            'label'     => __('Оценка 5', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.priorityup##'] = '';
        $item->tag_descriptions['tag']['##ticket.priorityup##'] = [
            'tag'       => 'ticket.priorityup',
            'value'     => 1,
            'label'     => __('Повысить приоритет', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        
        $assigned = $item->data['##ticket.assigntousers##'];
        $item->data['##ticket.assigntousers##'] = explode(',', $assigned)[0];
        $tickets_id = (int)$item->data['##ticket.id##'];
        $tu = new \Ticket_User();
        $assign = current($tu->find(['tickets_id' => $tickets_id, 'type' => 2], ['id'], 1));
        $requester = current($tu->find(['tickets_id' => $tickets_id, 'type' => 1], ['id'], 1));
        if($assign) {
            // add to notification's template tags of assigned user's photo
            $p = new self;
            $proc = current($p->find(['users_id' => $assign['users_id']], [], 1));
            $url = $CFG_GLPI['url_base'].'/front/document.send.php?docid='.$proc['documents_id'];
            $item->data['##ticket.assigntouserphoto.small##'] = '<img height="48" width="48" src="'.$url.'" />';
            $item->data['##ticket.assigntouserphoto.medium##'] = '<img height="96" width="96" src="'.$url.'" />';
            $item->data['##ticket.assigntouserphoto.large##'] = '<img height="144" width="144" src="'.$url.'" />';
            
            // add to notification's template tag of assigned user's title
            $u = new \User();
            $user = current($u->find(['id' => $assign['users_id']], [], 1));
            $t = new \UserTitle();
            $title = current($t->find(['id' => $user['usertitles_id']], [], 1));
            $item->data['##ticket.assigntousertitle##'] = $title['name'];
        }

        $url = $CFG_GLPI['url_base'].'/plugins/etn/front/status.php?'.'users_id='.$requester['users_id'].'&tickets_id='.$tickets_id;

        $ts = new \TicketSatisfaction();
        if($ticket = current($ts->find(['tickets_id' => $tickets_id], [], 1))) {
            $ts->getFromDB($ticket['id']);
            $ts->fields['id'] = $ticket['id'];
            $ts->fields['tickets_id'] = $tickets_id;
            $ts->fields['date_begin'] = date('Y-m-d H:i:s');
            $ts->fields['date_answered'] = isset($ts->fields['satisfaction']) ? date('Y-m-d H:i:s') : null;
            $ts->fields['satisfaction'] = isset($ts->fields['satisfaction']) ? $ts->fields['satisfaction'] : null;
            $ts->updateInDB(array_keys($ts->fields));
        } else {
            $input['date_begin'] = date('Y-m-d H:i:s');
            $input['tickets_id'] = $tickets_id;
            $ts->add($input);
        }
        $r = new Rating();
        if($rating = current($r->find(['tickets_id' => $tickets_id], [], 1))) {
            $r->getFromDB($rating['id']);
            if($rating['status']) $r->fields['status'] = 0;
            $r->fields['date_create'] = date('Y-m-d H:i:s');
            $r->updateInDB(array_keys($r->fields));
        } else {
            $input['tickets_id'] = $tickets_id;
            $input['date_create'] = date('Y-m-d H:i:s');
            $r->add($input);
        }

        $item->data['##ticket.rating.0##'] = $url.'&rating=0';
        $item->data['##ticket.rating.1##'] = $url.'&rating=1';
        $item->data['##ticket.rating.2##'] = $url.'&rating=2';
        $item->data['##ticket.rating.3##'] = $url.'&rating=3';
        $item->data['##ticket.rating.4##'] = $url.'&rating=4';
        $item->data['##ticket.rating.5##'] = $url.'&rating=5';
        
        // add to notification's template tag of ticket's priority up
        $item->data['##ticket.priorityup##'] = $url.'&priority_up=4';
        
        return $item;
    }

}

?>