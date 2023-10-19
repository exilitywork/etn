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

class NotificationTargetTakeIntoAccountTime extends \NotificationTarget {

    const TAKE_TIME  = 'take_time';

    /**
     * Overwrite the function in NotificationTarget because there's only one target to be notified
     *
     * @see NotificationTarget::addNotificationTargets()
     */
    public function addNotificationTargets($entity)
    {
        $this->addTarget(\Notification::GLOBAL_ADMINISTRATOR, __('Выбранные пользователи (из настроек плагина ETN)'));
    }

    /**
     * @return array
     */
    function getEvents() {
        return [
            self::TAKE_TIME  => 'Отчет о среднем времени взятия заявок в работу'
        ];
    }

    /**
    * @param       $event
    * @param array $options
    */
    function addDataForTemplate($event, $options = []) {
        global $CFG_GLPI;

        $this->data['##taketime.entity##'] = \Dropdown::getDropdownName('glpi_entities', $options['entities_id']);
        $this->data['##taketime.avgtime##'] = $options['avgtime'];
        $this->data['##taketime.datebegin##'] = $options['datebegin'];
        $this->data['##taketime.dateend##'] = $options['dateend'];

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
            'taketime.action'       => 'Отчет о среднем времени взятия заявок в работу за период с '.$this->options['datebegin'].' по '.$this->options['dateend'],
            'taketime.avgtime'      => __('Среднее время', 'etn'),
            'taketime.datebegin'    => __('Начальная дата'),
            'taketime.dateend'      => __('Конечная дата'),
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
            if(!$notificationTemplate->getFromDBByCrit(['itemtype' => 'GlpiPlugin\Etn\TakeIntoAccountTime'])) {
                $notificationTemplateId = $notificationTemplate->add([
                    'name'     => 'ETN - Отчет о среднем времени взятия заявок в работу',
                    'itemtype' => 'GlpiPlugin\Etn\TakeIntoAccountTime',
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
                    'subject'                   => '##lang.taketime.action##',
                    'content_html'              => 
                        '&#60;p&#62;&#60;/p&#62;
                        &#60;div&#62;&#60;strong&#62;##lang.taketime.action##&#60;/strong&#62;&#60;/div&#62;
                        &#60;div&#62;&#60;/div&#62;
                        &#60;div&#62;&#60;span style="text-decoration: underline; color: #888888;"&#62;&#60;strong&#62;##lang.taketime.avgtime##&#60;/strong&#62;&#60;/span&#62; : ##taketime.avgtime##&#60;/div&#62;',
                ]);
            }

            $notification = new \Notification();
            if(!$notification->getFromDBByCrit(['name' => 'ETN - Отчет о среднем времени взятия заявок в работу'])){
                $notificationId = $notification->add([
                    'name'                     => 'ETN - Отчет о среднем времени взятия заявок в работу',
                    'entities_id'              => 0,
                    'is_recursive'             => 1,
                    'is_active'                => 1,
                    'itemtype'                 => 'GlpiPlugin\Etn\TakeIntoAccountTime',
                    'event'                    => 'take_time',
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