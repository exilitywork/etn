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

class User extends \CommonDBTM
{

    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0) {
        return __('User', 'etn');
    }

    static function showUsernameField($params) {
        $item = $params['item'];
        $options = $params['options'];
        
        if($item->getType() == 'Ticket' && $_REQUEST['_glpi_tab'] == 'Ticket$main' && $item->fields['status'] >= 5) {
            $ratingUser = 1;
            $satisfaction = new \TicketSatisfaction();
            if($satisfaction = current($satisfaction->find(['tickets_id' => $item->fields['id']], [], 1))) {
                if(!empty($satisfaction['satisfaction'])) {
                    $color = 'green';
                    $minRating = Config::getOption('min_rating');
                    if($satisfaction['satisfaction'] < ($minRating ? $minRating : 4)) $color = 'red';
                    echo '
                        <div class="form-field row col-12 mb-2">
                            <label class="col-form-label col-xxl-4 text-xxl-end" for="">Оценка</label>
                            <div class="col-xxl-8  field-container">
                                <span class="form-control-plaintext"><span><i class="fas fa-'.$satisfaction['satisfaction'].'" style="color: '.$color.'"></i> - '.Rating::getUserNameByTicketId($item->fields['id']).'</span></span>
                            </div>
                            <label class="col-form-label col-xxl-4 text-xxl-end" for="">Комментарий</label>
                            <div class="col-xxl-8  field-container">
                                <span class="form-control-plaintext">'.$satisfaction['comment'].'</span>
                            </div>
                        </div>
                    ';
                }
            }
        }
        if(isset($_REQUEST['_glpi_tab']) && ($_REQUEST['_glpi_tab'] == 'User$1' || $_REQUEST['_glpi_tab'] == 'User$main')) {
            $username = null;
            $id = null;
            $isPref = false;
            $botName = Telegram::getBotName();
            if ($item->getType() == 'Preference' && $options['itemtype'] == 'User') {
                $id = \Session::getLoginUserID();
                $isPref = true;
            }
            if ($item->getType() == 'User' && $item->fields['id']) $id = $item->fields['id'];
            if($id) {
                $user = new self();
                if($u = current($user->find(['users_id' => $id], [], 1))) {
                    $username = $u['username'];
                }

                $out = '<table class="tab_cadre_fixe" style="width: auto;">';
                $out .= '<tr class="tab_bg_1" style="border: 2px rgb(135, 170, 138) solid; border-radius: 4px; display: block;">';
                $out .= '<td>'.__('Имя пользователя Telegram', 'etn').'</td>';
                $out .= '<td><input id="username" type="text" name="username" value="'.$username.'"></td>';
                if($isPref) $out .= '<td><a id="save-username" class="btn btn-primary me-2" name="ave-username" value="1" onclick="saveUsername()">Сохранить</a></td>
                    <script>
                        function saveUsername() {
                            let username = $("#username").val();
                            if(!username) {
                                $("#username").css("border-width", "4px").css("border-color", "red");
                                alert("'.__('Заполните поля, выделенные красным!', 'etn').'");
                                return;
                            }
                            $("#save-username").html("<i class=\"fas fa-spinner fa-spin\"></i>");
                            $("#username").on("input",function() {
                                $("#save-username").html("Сохранить").css("background-color", "#fec95c").css("color", "#1e293b");
                            });

                            $.ajax({
                                type: "POST",
                                url: "../../../plugins/etn/ajax/saveusername.php",
                                data: {
                                    username: username
                                },
                                datatype: "json"
                            }).done(function(response) {
                                if(response) {
                                    $("#save-username").html("Сохранено!")
                                        .css("background-color", "rgb(226, 242, 227)")
                                        .css("color", "rgb(21, 82, 16)")
                                        .css("border-color", "rgba(98, 105, 118, 0.24)");
                                    if(response > 0) $("#reg-status").html("Зарегистрирован<br>в Telegram-боте").css("color", "rgb(21, 82, 16)");
                                    if(response < 0) 
                                        $("#reg-status")
                                            .html("Для завершения регистрации отправьте любое <br> сообщение Telegram-боту <a href=\"https://t.me/'.$botName.'\">'.$botName.'</a>")
                                            .css("color", "rgb(214, 57, 57)");
                                } else {
                                    $("#save-username").html("Ошибка!")
                                        .css("background-color", "rgba(214, 57, 57, 0.05)")
                                        .css("color", "rgb(214, 57, 57)");
                                }
                            });
                        }
                    </script>';
                if(Chat::getChat($username)) {
                    $out .= '<td id="reg-status" style="color: rgb(21, 82, 16)">Зарегистрирован<br>в Telegram-боте</td>';
                } else {
                    $out .= '<td id="reg-status" style="color: rgb(214, 57, 57)">Для завершения регистрации отправьте любое <br> сообщение Telegram-боту 
                                <a href="https://t.me/'.$botName.'">'.$botName.'</a></td>';
                }
                $out .= '</tr>';
                $out .= '</table>';

                echo $out;
            }
        }
    }

    /**
     * Update user photo in documents when user's preferences are updated
     *
     * @param $item            class User
     *
     * @return bool
    **/
    static function updateUser($item) {
        Ldap::switchConfig($item);

        if(!empty($item->input['username'])) {
            if ($item->fields['id']) {
                $user = new self();
                $u = current($user->find(['users_id' => $item->fields['id']], [], 1));
                if($u) {
                    if($u['username'] != $item->input['username']) {
                        $user->fields['id'] = $u['id'];
                        $user->fields['username'] = $item->input['username'];
                        $user->updateInDB(array_keys($user->fields));
                    }
                } else {
                    self::addUser($item);
                }
            }
        }

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

    /**
     * Add user 
     *
     * @param $item            class User
     *
     * @return bool
    **/
    static function addUser($item) {
        if(!empty($item->input['username'])) {
            if ($item->fields['id']) {
                $user = new self();
                $user->fields['username'] = $item->input['username'];
                $user->fields['users_id'] = $item->input['id'];
                if($u = current($user->find(['users_id' => $item->fields['id']], [], 1))) {
                    $user->fields['id'] = $u['id'];
                    $user->updateInDB(array_keys($user->fields));
                } else {
                    $user->addToDB();
                }
            }
        }
    }

    /**
     * Get username from DB
     *
     * @param $id            integer
     *
     * @return string|bool
    **/
    static function getUsername($id) {
        $user = new self();
        if($u = current($user->find(['users_id' => $id], [], 1))) {
            return $u['username'];
        }
        return false;
    }
}
?>