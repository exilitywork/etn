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

class NotificationTargetItemtype extends \NotificationTarget {

    const NEW_ITEM  = 'new_item';

    /**
     * Overwrite the function in NotificationTarget because there's only one target to be notified
     *
     * @see NotificationTarget::addNotificationTargets()
     */
    public function addNotificationTargets($entity)
    {
        $this->addTarget(\Notification::GLOBAL_ADMINISTRATOR, __('Выбранные группы (из настроек плагина ETN)'));
    }

    /**
     * @return array
     */
    function getEvents() {
        return [
            self::NEW_ITEM  => 'Уведомление о добавлении устройства'
        ];
    }

    /**
    * @param       $event
    * @param array $options
    */
    function addDataForTemplate($event, $options = []) {
        global $CFG_GLPI;

        $this->data['##itemtype.entity##']          = \Dropdown::getDropdownName('glpi_entities', $options['entities_id']);
        $this->data['##itemtype.itemtype##']        = $options['itemtype'];
        $this->data['##itemtype.type##']            = $options['type'];
        $this->data['##itemtype.name##']            = $options['name'];
        $this->data['##itemtype.manufacturer##']    = $options['manufacturer'];
        $this->data['##itemtype.model##']           = $options['model'];
        $this->data['##itemtype.serial##']          = $options['serial'];
        $this->data['##itemtype.user##']            = $options['user'];
        $this->data['##itemtype.url##']             = $options['url'];
        $this->getTags();
        foreach ($this->tag_descriptions[\NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }

    /**
     *
    */
    function getTags() {
        $tags = [
            'itemtype.itemtype'     => __('Category'),
            'itemtype.type'         => __('Type'),
            'itemtype.name'         => __('Name'),
            'itemtype.manufacturer' => __('Manufacturer'),
            'itemtype.model'        => __('Model'),
            'itemtype.serial'       => __('serial'),
            'itemtype.user'         => __('Technician in charge of the hardware'),
            'itemtype.url'          => __('Ссылка на обрудование'),
            'itemtype.action'       => 'Добавлено новое оборудование: '.$this->data['##itemtype.itemtype##']
        ];

        foreach ($tags as $tag => $label) {
        $this->addTagToList(['tag'   => $tag, 'label' => $label,
                                'value' => true]);
        }
        asort($this->tag_descriptions);
    }

    /**
     * Notification initialization
    */
    static function init() {
        global $DB;
        try {
            $notificationId = 0;
            $notificationTemplateId = 0;

            $notificationTemplate = new \NotificationTemplate();
            if(!$notificationTemplate->getFromDBByCrit(['itemtype' => 'GlpiPlugin\Etn\Itemtype'])) {
                $notificationTemplateId = $notificationTemplate->add([
                    'name'     => 'Уведомление о добавлении устройства',
                    'itemtype' => 'GlpiPlugin\Etn\Itemtype',
                    'comment'  => "Created by the plugin ETN",
                    'date_mod'       => date('Y-m-d H:i:s'),
                    'date_creation'  => date('Y-m-d H:i:s')
                ]);
            }

            if($notificationTemplateId) {
                $notificationTemplateTranslation = new \NotificationTemplateTranslation();
                $notificationTemplateTranslationId = $notificationTemplateTranslation->add([
                    'notificationtemplates_id'  => $notificationTemplateId,
                    'language'                  => '',
                    'subject'                   => '##lang.itemtype.action##',
                    'content_html'              => 
                        '&#60;p&#62;&#60;/p&#62;
                        &#60;div&#62;&#60;strong&#62;##lang.itemtype.action##&#60;/strong&#62;&#60;/div&#62;
                        &#60;div&#62;&#60;/div&#62;
                        &#60;div&#62;&#60;span style="text-decoration: underline; color: #888888;"&#62;&#60;strong&#62;##lang.itemtype.itemtype##&#60;/strong&#62;&#60;/span&#62; : ##itemtype.itemtype##&#60;/div&#62;
                        &#60;div&#62;&#60;span style="text-decoration: underline; color: #888888;"&#62;&#60;strong&#62;##lang.itemtype.type##&#60;/strong&#62;&#60;/span&#62; : ##itemtype.type##&#60;/div&#62;
                        &#60;div&#62;&#60;span style="text-decoration: underline; color: #888888;"&#62;&#60;strong&#62;##lang.itemtype.name##&#60;/strong&#62;&#60;/span&#62; : ##itemtype.name##&#60;/div&#62;
                        &#60;div&#62;&#60;span style="text-decoration: underline; color: #888888;"&#62;&#60;strong&#62;##lang.itemtype.manufacturer##&#60;/strong&#62;&#60;/span&#62; : ##itemtype.manufacturer##&#60;/div&#62;
                        &#60;div&#62;&#60;span style="text-decoration: underline; color: #888888;"&#62;&#60;strong&#62;##lang.itemtype.model##&#60;/strong&#62;&#60;/span&#62; : ##itemtype.model##&#60;/div&#62;
                        &#60;div&#62;&#60;span style="text-decoration: underline; color: #888888;"&#62;&#60;strong&#62;##lang.itemtype.serial##&#60;/strong&#62;&#60;/span&#62; : ##itemtype.serial##&#60;/div&#62;
                        &#60;div&#62;&#60;span style="text-decoration: underline; color: #888888;"&#62;&#60;strong&#62;##lang.itemtype.user##&#60;/strong&#62;&#60;/span&#62; : ##itemtype.user##&#60;/div&#62;
                        &#60;div&#62;&#60;span style="text-decoration: underline; color: #888888;"&#62;&#60;strong&#62;##lang.itemtype.url##&#60;/strong&#62;&#60;/span&#62; : ##itemtype.url##&#60;/div&#62;',
                ]);
            }

            $notification = new \Notification();
            if(!$notification->getFromDBByCrit(['name' => 'Уведомление о добавлении устройства'])){
                $notificationId = $notification->add([
                    'name'                     => 'Уведомление о добавлении устройства',
                    'entities_id'              => 0,
                    'is_recursive'             => 1,
                    'is_active'                => 1,
                    'itemtype'                 => 'GlpiPlugin\Etn\Itemtype',
                    'event'                    => 'new_item',
                    'comment'                  => "Created by the plugin ETN"
                ]);
            }

            if($notificationId && $notificationTemplateId){
                $notifNotifTemplate = new \Notification_NotificationTemplate();
                $fields = [
                    'notifications_id'          => $notificationId,
                    'mode'                      => 'mailing',
                    'notificationtemplates_id'  => $notificationTemplateId
                ];
                $notifications_id   = $notifNotifTemplate->add($fields);
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}