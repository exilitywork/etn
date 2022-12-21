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

class Config extends \CommonDBTM
{

    /**
     * Get typename
     *
     * @param $nb            integer
     *
     * @return string
    **/
    static function getTypeName($nb = 0) {
        return __('ETN Config', 'etn');
    }

    /**
     * Get headername
     *
     * @return string
    **/
    public function getHeaderName(): string {
        return __('Настройки', 'etn');
    }

    static function getMenuContent() {

        $menu          = [];
        $menu['title'] = self::getMenuName();
        $menu['icon']   = 'far fa-envelope';
        $menu['page']   = '/plugins/etn/front/config.php';

        return $menu;
    }

    /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options = []) {

    $ong        = [];
    $this->addStandardTab("GlpiPlugin\Etn\Config", $ong, $options);
    //$this->addStandardTab('Log', $ong, $options);

    return $ong;
 }

    /**
     * Get the tab name used for item
     *
     * @param object $item the item object
     * @param integer $withtemplate 1 if is a template form
     * @return string|array name of the tab
     */
    function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType()==__CLASS__) {
            return [
                __('Config')
            ];
        }
        return '';
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

    $opt = current($item->find([], [], 1));
    $item->getFromDB($opt['id']);
    switch ($tabnum) {
       case 0:
          $item->showForm();
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

        $config = self::getConfig();

        $options['formtitle']       = __('Extended Ticket\'s Notification', 'etn');
        $options['colspan']         = 4;
        $options['withtemplate']    = 0;
        $options['target']          = $CFG_GLPI["root_doc"].'/plugins/etn/front/config.php';
        $this->showFormHeader($options);

        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Профиль, для которого отключено обновление фото из LDAP', 'etn');
        echo '</td>';
        echo '<td class="center">';
        \Profile::dropdownUnder([
            'name'  => 'ldap_profile',
            'value' => isset($config['ldap_profile']) ? $config['ldap_profile'] : \Profile::getDefault()
        ]);
        echo '</td>';
        echo '</tr>';
        /*echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Профиль для Telegram уведомлений', 'etn');
        echo '</td>';
        echo '<td class="center">';
        \Profile::dropdownUnder([
            'name'  => 'notification_profile',
            'value' => isset($config['notification_profile']) ? $config['notification_profile'] : \Profile::getDefault()
        ]);
        echo '</td>';
        echo '</tr>';*/
        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Профиль для доступа к статистике по оценкам', 'etn');
        echo '</td>';
        echo '<td class="center">';
        \Profile::dropdownUnder([
            'name'  => 'rating_profile',
            'value' => isset($config['rating_profile']) ? $config['rating_profile'] : \Profile::getDefault()
        ]);
        echo '</td>';
        echo '</tr>';
        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Минимальная положительная оценка', 'etn');
        echo '</td>';
        echo '<td class="center">';
        \Dropdown::showNumber('min_rating', [
            'value' => isset($config['min_rating']) ? $config['min_rating'] : 4,
            'max'   => 5
        ]);
        echo '</td>';
        echo '</tr>';
        echo '<tr class="tab_bg_1"><th colspan="4">'.__('Настройки Telegram для уведомлений', 'etn') . '</th></tr>';
        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Telegram Bot Token');
        echo '</td>';
        echo '<td class="center">';
        echo \Html::input(
            'bot_token',
            [
                'value' => isset($config['bot_token']) ? $config['bot_token'] : '',
                'id'    => 'bot_token'
            ]
        );
        echo '</td>';
        if($config['bot_token'] && $botName = Telegram::getBotName()) {
            echo '<td>';
            echo __('Telegram Bot URL');
            echo '</td>';
            echo '<td>';
            echo '<a href="https://t.me/'.$botName.'" style="font-weight: bold">'.$botName.'</a>';
            echo '</td>';
        }
        echo '</tr>';
        echo '<tr class="tab_bg_1">';
        echo '<td>';
        echo __('Название группы', 'etn');
        echo '</td>';
        echo '<td class="center">';
        echo \Html::input(
            'group_name',
            [
                'value' => isset($config['group_name']) ? $config['group_name'] : '',
                'id'    => 'group_name'
            ]
        );
        echo '</td>';
        echo '<td>';
        echo __('ID группы', 'etn');
        echo '</td>';
        echo '<td class="center">';
        echo \Html::input(
            'group_chat_id',
            [
                'value' => isset($config['group_chat_id']) ? $config['group_chat_id'] : '',
                'id'    => 'group_chat_id'
            ]
        );
        echo '</td>';
        echo '<td>';
        echo '
            <a id="get-id" class="btn btn-primary me-2" name="get-id" value="1" onclick="getId()">
                <span>Определить ID</span>
            </a>
            <script>
                function getId() {
                    let name = $("#group_name").val();
                    let token = $("#bot_token").val();
                    if(!name) {
                        $("#group_name").css("border-width", "4px").css("border-color", "red");
                    }
                    if(!token) {
                        $("#bot_token").css("border-width", "4px").css("border-color", "red");
                    }
                    if(!name || !token) {
                        alert("'.__('Заполните поля, выделенные красным!', 'etn').'");
                        return;
                    }
                    $.ajax({
                        type: "POST",
                        url: "../../../plugins/etn/ajax/getid.php",
                        data: {
                            name: name, 
                            token: token
                        },
                        datatype: "json"
                    }).done(function(response) {
                        if(response) {
                            $("#group_chat_id").val(response);
                        } else {
                            alert("'.__('Некорректный токен или название группы!', 'etn').'");
                        }
                    });
                }
            </script>';
        echo '</td>';
        echo '</tr>';
        echo '<tr class="tab_bg_1"><th colspan="4">'.__('Настройки сбора статистики по заявкам', 'etn') . '</th></tr>';
        echo '<tr>';
        echo '<td>';
        echo __('Топ инициаторов за месяц', 'etn');
        echo '</td>';
        echo '<td>';
        \Html::showSimpleForm(
            '/front/crontask.form.php',
            ['execute' => 'SendTopRequestersETN'],
            '<i class="fa-fw far fa-envelope"></i><span>'.__('Отправить на почту', 'etn').'</span>'
        );
        echo '</td>';
        echo '</tr>';
        echo '<tr class="tab_bg_1"><th colspan="4">'.__('Настройки времени бездействия', 'etn') . '</th></tr>';
        echo '<tr>';
        echo '<td>';
        echo __('Максимальное время бездействия по умолчанию', 'etn');
        echo '</td>';
        echo '<td>';
        \Dropdown::showTimeStamp('inaction_time', [
            'min'   => 0,
            'max'   => 7 * DAY_TIMESTAMP,
            'step'  => $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP * 6,
            'value' => isset($config['inaction_time']) ? $config['inaction_time'] : '',
        ]);
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>';
        echo __('Час, когда отправляется автоматический отчет на почту', 'etn');
        echo '</td>';
        echo '<td>';
        \Dropdown::showHours('inaction_send_hour', [
            /*'min'   => 0,
            'max'   => 7 * DAY_TIMESTAMP,*/
            'step'  => $CFG_GLPI['time_step'] * 12,
            'value' => isset($config['inaction_send_hour']) ? $config['inaction_send_hour'] : '',
        ]);
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>';
        echo __('Отчет о нарушении времени бездействия по заявкам', 'etn');
        echo '</td>';
        echo '<td>';
        \Html::showSimpleForm(
            '/front/crontask.form.php',
            ['execute' => 'CheckInactionTimeTicketETN'],
            '<i class="fa-fw far fa-envelope"></i><span>'.__('Отправить на почту', 'etn').'</span>'
        );
        echo '</td>';
        echo '</tr>';
        echo '</table>';

        $options['candel'] = false;
        $this->showFormButtons($options);

        return true;
    }

    /**
     * Update config of plugin
     *
     * @param array     $options
     * 
     * @return bool
    **/
    static function updateConfig($options = []) {
        try {
            foreach($options as $option => $value) {
                $cfg = new self;
                $cfg->fields['option'] = $option;
                $cfg->fields['value'] = $value;
                if($config = current($cfg->find(['option' => $option], [], 1))) {
                    $cfg->fields['id'] = $config['id'];
                    $cfg->updateInDB(array_keys($cfg->fields));
                } else {
                    $cfg->addToDB();
                }
            }
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Get config of plugin
     *
     * @param array     $options
     * 
     * @return array|false
    **/
    static function getConfig($options = []) {
        $out = [];
        $cfg = new self;
        try {
            if($options){
                foreach($options as $option) {
                    $config = current($cfg->find(['option' => $option], [], 1));
                    $out[$option] = $config['value'];
                }
            } else {
                foreach($cfg->find() as $config) {
                    $out[$config['option']] = $config['value'];
                }
            }
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
        return $out;
    }

    /**
     * Get option value
     *
     * @param string     $options
     * 
     * @return string|false
    **/
    static function getOption($option) {
        $cfg = new self;
        try {
            if($config = current($cfg->find(['option' => $option], [], 1))){
                return $config['value'];
            }
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
        return false;
    }

    function can($ID, $right, ?array &$input = NULL) {
        if(\Session::haveRight('config', READ)) return true; 
        return false;
    }
}
?>