<?php

/**
 * -------------------------------------------------------------------------
 * userphotoconv plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of userphotoconv.
 *
 * userphotoconv is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * any later version.
 *
 * userphotoconv is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with userphotoconv. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2022-2022 by Oleg Кapeshko
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/exilitywork/userphotoconv
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Userphotoconv;

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
        return __('Processing', 'userphotoconv');
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
        $assigned = $item->data['##ticket.assigntousers##'];
        $item->data['##ticket.assigntousers##'] = explode(',', $assigned)[0];
        $id = (int)$item->data['##ticket.id##'];
        $tu = new \Ticket_User();
        $user = current($tu->find(['tickets_id' => $id, 'type' => 2], ['id'], 1));
        $p = new self;
        $proc = current($p->find(['users_id' => $user['users_id']], [], 1));
        $item->data['##ticket.assigntouserphoto.small##'] = '<img height="48" src="'.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/front/document.send.php?docid='.$proc['documents_id'].'" />';
        $item->tag_descriptions['tag']['##ticket.assigntouserphoto.small##'] = [
            'tag' => 'ticket.assigntouserphoto.small',
            'value' => 1,
            'label' => 'Фото специалиста',
            'events' => 0,
            'lang' => 1
        ];
        $item->data['##ticket.assigntouserphoto.medium##'] = '<img height="96" src="'.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/front/document.send.php?docid='.$proc['documents_id'].'" />';
        $item->tag_descriptions['tag']['##ticket.assigntouserphoto.medium##'] = [
            'tag' => 'ticket.assigntouserphoto.medium',
            'value' => 1,
            'label' => 'Фото специалиста',
            'events' => 0,
            'lang' => 1
        ];
        $item->data['##ticket.assigntouserphoto.large##'] = '<img height="144" src="'.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/front/document.send.php?docid='.$proc['documents_id'].'" />';
        $item->tag_descriptions['tag']['##ticket.assigntouserphoto.large##'] = [
            'tag' => 'ticket.assigntouserphoto.large',
            'value' => 1,
            'label' => 'Фото специалиста',
            'events' => 0,
            'lang' => 1
        ];
        
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
        if(!isset($item->input['picture']) && isset($item->input['authtype']) && $item->input['authtype'] == 3) {
            $item->input['picture'] = $item->syncLdapPhoto();
            print_r($file);
            self::updatePhotoByFilename($item->input['picture'], $item->input['id']);
            return;
        }
        if(isset($item->fields['picture']) && !isset($item->input['_picture'])) {
            self::updatePhotoByFilename($item->input['picture'], $item->input['id']);
            return;
        }
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
        imagepng($image,'/var/www/glpi/plugins/userphotoconv/output.png');
        imagedestroy($image);
        imagedestroy($mask);
    }
}

?>