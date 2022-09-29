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
    }

    /**
     * Add new tag of user's photo to ticket's notifications
     *
     * @param $item            class Ticket
     *
     * @return $item
    **/
    static function modifyNotification($item) {

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
        $item->data['##ticket.ticket.rating.1##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.1##'] = [
            'tag'       => 'ticket.rating.1',
            'value'     => 1,
            'label'     => __('Оценка 1', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.ticket.rating.2##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.2##'] = [
            'tag'       => 'ticket.rating.2',
            'value'     => 1,
            'label'     => __('Оценка 2', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.ticket.rating.3##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.3##'] = [
            'tag'       => 'ticket.rating.3',
            'value'     => 1,
            'label'     => __('Оценка 3', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.ticket.rating.4##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.4##'] = [
            'tag'       => 'ticket.rating.4',
            'value'     => 1,
            'label'     => __('Оценка 4', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.ticket.rating.5##'] = '';
        $item->tag_descriptions['tag']['##ticket.rating.5##'] = [
            'tag'       => 'ticket.rating.5',
            'value'     => 1,
            'label'     => __('Оценка 5', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];
        $item->data['##ticket.ticket.priorityup##'] = '';
        $item->tag_descriptions['tag']['##ticket.priorityup##'] = [
            'tag'       => 'ticket.priorityup',
            'value'     => 1,
            'label'     => __('Повысить приоритет', 'etn'),
            'events'    => 0,
            'lang'      => 1
        ];

        if(!(isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['SERVER_NAME']))) return $item;
        
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
            $url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/front/document.send.php?docid='.$proc['documents_id'];
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

        // add to notification's template tags of solved ticket's ratings
        $style = '
                display:inline-block;
                background:#3200F0;
                color:#ffffff;
                font-family:Stolzl, Arial, san-serif;
                font-size:16px;
                font-weight:600;
                line-height:1.6;
                margin:0;
                text-decoration:none;
                text-transform:none;
                padding:10px 15px;
                mso-padding-alt:0px;
                border-radius:3px;
            ';
        $url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/plugins/etn/front/status.php?'.'users_id='.$requester['users_id'].'&tickets_id='.$tickets_id;

        $ts = new \TicketSatisfaction();
        if($ticket = current($ts->find(['tickets_id' => $tickets_id], [], 1))) {
            $ts->getFromDB($ticket['id']);
            $ts->fields['id'] = $ticket['id'];
            $ts->fields['tickets_id'] = $tickets_id;
            $ts->fields['date_begin'] = date('Y-m-d H:i:s');
            $ts->fields['satisfaction'] = isset($ts->fields['satisfaction']) ? $ts->fields['satisfaction'] : 5;
            $ts->updateInDB(array_keys($ts->fields));
        } else {
            $input['date_begin'] = date('Y-m-d H:i:s');
            $input['satisfaction'] = 5;
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
        $item->data['##ticket.rating.1##'] = '<a href="'.$url.'&rating=1" style="'.$style.'">1</a>';
        $item->data['##ticket.rating.2##'] = '<a href="'.$url.'&rating=2" style="'.$style.'">2</a>';
        $item->data['##ticket.rating.3##'] = '<a href="'.$url.'&rating=3" style="'.$style.'">3</a>';
        $item->data['##ticket.rating.4##'] = '<a href="'.$url.'&rating=4" style="'.$style.'">4</a>';
        $item->data['##ticket.rating.5##'] = '<a href="'.$url.'&rating=5" style="'.$style.'">5</a>';
        
        // add to notification's template tag of ticket's priority up
        $item->data['##ticket.priorityup##'] = '<a href="'.$url.'&priority_up=4" style="'.$style.'">'.__('Повысить приоритет', 'etn').'</a>';
        
        
        return $item;
    }

    /**
     * Update user photo in documents when user's preferences are updated
     *
     * @param $item            class User
     *
     * @return bool
    **/
    static function updateUser($item) {
        $doc = new \Document();
        // update photo with LDAP auth
        if(!isset($item->input['picture']) && isset($item->input['authtype']) && $item->input['authtype'] == 3) {
            $item->input['picture'] = $item->syncLdapPhoto();
            print_r($file);
            self::updatePhotoByFilename($item->input['picture'], $item->input['id']);
            return;
        }
        // update photo with internal auth
        if(isset($item->fields['picture']) && !isset($item->input['_picture'])) {
            self::updatePhotoByFilename($item->fields['picture'], $item->fields['id']);
            return;
        }
        //update photo with manual
        if(isset($item->input['_picture']) && isset($item->input['_tag_picture']) && isset($item->input['_prefix_picture'])) {
            $input['_filename'][0] = '_'.$item->input['_picture'][0];
            $input['_tag_filename'] = $item->input['_tag_picture'];
            $input['_prefix_filename'][0] = '_'.$item->input['_prefix_picture'][0];
            copy(GLPI_TMP_DIR.'/'.$item->input['_picture'][0], GLPI_TMP_DIR.'/'.$input['_filename'][0]);
            $doc = new \Document();
            if($curDoc = current($doc->find(['filename' => mb_substr($item->input['_picture'][0], 23)], [], 1))) {
                $docID = $curDoc['id'];
            } else {
                $docID = $doc->add($input);
            }

            // add or update users and docs relations
            $proc = new Process();
            $proc->fields['users_id'] = $item->input['id'];
            $proc->fields['documents_id'] = $docID;
            if($curProc = current($proc->find(['users_id' => $item->input['id']], [], 1))) {
                $proc->fields['id'] = $curProc['id'];
                $proc->updateInDB(array_keys($proc->fields));
            } else {
                $proc->addToDB();
            }
        }
    }

    /**
     * update user photo in docs by filename
     *
     * @param $file             string      filename
     * @param $id               integer     user's ID
     *
    **/
    static function updatePhotoByFilename($file, $id) {
        $tag = trim(\Document::getImageTag(\Rule::getUuid()), '#');
        $filename = mb_substr($file, strpos($file, '_') + 1);
        $prefix = mb_substr($file, strpos($file, '/') + 1, strpos($file, '_') - strpos($file, '/'));
        copy(GLPI_PICTURE_DIR.'/'.$file, GLPI_TMP_DIR.'/'.$prefix.$filename);

        // search and add user photo as document
        $input['_filename']        = [$prefix.$filename];
        $input['_tag_filename']    = [$tag];
        $input['_prefix_filename'] = [$prefix];
        $doc = new \Document();
        if($curDoc = current($doc->find(['filename' => $filename], [], 1))) {
            $docID = $curDoc['id'];
        } else {
            $docID = $doc->add($input);
        }

        // add or update users and docs relations
        $proc = new Process();
        $proc->fields['users_id'] = $id;
        $proc->fields['documents_id'] = $docID;
        if($curProc = current($proc->find(['users_id' => $id], [], 1))) {
            $proc->fields['id'] = $curProc['id'];
            $proc->updateInDB(array_keys($proc->fields));
        } else {
            $proc->addToDB();
        }
    }

    static function roundImg($filename = '') {
        
        $image_s = imagecreatefromjpeg($filename);
        $width = imagesx($image_s);
        $height = imagesy($image_s);
        
        $newwidth = 1000;
        $newheight = 1000;
        
        $image = imagecreatetruecolor($newwidth, $newheight);
        imagealphablending($image, true);

        imagecopyresampled($image, $image_s, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        //create masking
        $mask = imagecreatetruecolor($newwidth, $newheight);
        $transparent = imagecolorallocate($mask, 255, 0, 0);
        imagecolortransparent($mask,$transparent);
        imagefilledellipse($mask, $newwidth/2, $newheight/2, $newwidth-1, $newheight-1, $transparent);
        
        $red = imagecolorallocate($mask, 0, 0, 0);
        imagecopymerge($image, $mask, 0, 0, 0, 0, $newwidth, $newheight, 100);
        imagecolortransparent($image,$red);
        imagefill($image, 0, 0, $red);

        //output, save and free memory
        header('Content-type: image/png');
        imagepng($image,'/var/www/glpi/plugins/etn/output.png');
        imagedestroy($image);
        imagedestroy($mask);
    }
}

?>