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

class NotificationTargetExpiredSla extends \NotificationTarget {

    const EXPIRED_SLA  = 'expired_sla';

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
            self::EXPIRED_SLA  => 'Отчет о нарушении SLA по заявкам определенной категории'
        ];
    }

    /**
    * @param       $event
    * @param array $options
    */
    function addDataForTemplate($event, $options = []) {
        global $CFG_GLPI;

        $this->data['##expiredsla.entity##'] = \Dropdown::getDropdownName('glpi_entities', $options['entities_id']);

        foreach ($options['expiredsla'] as $id => $item) {
            $tmp = [];
            $tmp['##expiredsla.name##']         = $item['name'];
            $tmp['##expiredsla.date##']         = $item['date'];
            $tmp['##expiredsla.requesters##']   = $item['requesters'];
            $tmp['##expiredsla.specs##']        = $item['specs'];
            $url                                = urldecode($CFG_GLPI['url_base'].'/front/ticket.form.php?id='.$item['id']);
            $tmp['##expiredsla.id##']           = '<a href="'.$url.'" title="'.$item['name'].'">'.$item['id'].'</a>';
            
            $this->data['expiredsla'][] = $tmp;
        }
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
            'expiredsla.id'           => __('ID (URL)', 'etn'),
            'expiredsla.name'         => __('Title'),
            'expiredsla.date'         => __('Opening date'),
            'expiredsla.requesters'   => __('Инициаторы', 'etn'),
            'expiredsla.specs'        => __('Специалисты', 'etn'),
            'expiredsla.action'       => 'Отчет о нарушении SLA по заявкам категории "'.$this->options['categoryname'].'" за '.date('d-m-Y H:i')
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
            $req = $DB->request([
                'SELECT'    => 'id',
                'FROM'      => 'glpi_notificationtemplates',
                'WHERE'     => [
                    'itemtype' => 'GlpiPlugin\Etn\ExpiredSla'
                ]
            ]);

            if (!count($req)) {
                $DB->insertOrDie(
                    'glpi_notificationtemplates', [
                        'name'           => 'Отчет о нарушении SLA по заявкам определенной категории',
                        'itemtype'       => 'GlpiPlugin\Etn\ExpiredSla',
                        'date_mod'       => date('Y-m-d H:i:s'),
                        'date_creation'  => date('Y-m-d H:i:s')
                    ]
                );
                $templates_id = $DB->insertId();
                $DB->insertOrDie(
                    'glpi_notificationtemplatetranslations', [
                        'notificationtemplates_id'  => $templates_id,
                        'language'                  => '',
                        'subject'                   => '##lang.expiredsla.action##',
                        'content_html'              => 
                            "&lt;p&gt;##lang.expiredsla.action##&lt;table style=\"border-collapse: collapse;\"&gt;&lt;tr&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.expiredsla.id##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.expiredsla.name##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.expiredsla.date##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.expiredsla.requesters##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.expiredsla.specs##&lt;/th&gt;&lt;/tr&gt;".
                            "##FOREACHexpiredsla##&lt;tr&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##expiredsla.id##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##expiredsla.name##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##expiredsla.date##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##expiredsla.requesters##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##expiredsla.specs##&lt;/td&gt;&lt;/tr&gt;".
                            "##ENDFOREACHexpiredsla##&lt;/table&gt;&lt;/p&gt;",
                    ]
                );
                $DB->insertOrDie(
                    'glpi_notifications', [
                        'name'          => 'Отчет о нарушении SLA по заявкам определенной категории',
                        'itemtype'      => 'GlpiPlugin\Etn\ExpiredSla',
                        'entities_id'   => 0,
                        'event'         => 'expired_sla',
                        'is_recursive'  => 1,
                        'is_active'     => 1
                    ]
                );
                $notification_id = $DB->insertId();
                $DB->insertOrDie(
                    'glpi_notifications_notificationtemplates', [
                        'notifications_id'          => $notification_id,
                        'mode'                      => 'mailing',
                        'notificationtemplates_id'  => $templates_id
                    ]
                );
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}