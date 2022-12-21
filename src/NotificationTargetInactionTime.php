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

class NotificationTargetInactionTime extends \NotificationTarget {

    const INACTION_TIME  = 'inaction_time';

    /**
     * @return array
     */
    function getEvents() {
        return [
            self::INACTION_TIME  => 'Отчет о нарушении времени бездействия по заявкам'
        ];
    }

    /**
    * @param       $event
    * @param array $options
    */
    function addDataForTemplate($event, $options = []) {
        global $CFG_GLPI;

        $this->data['##inactiontime.entity##'] = \Dropdown::getDropdownName('glpi_entities', $options['entities_id']);

        foreach ($options['inactiontime'] as $id => $item) {
            $tmp = [];
            $tmp['##inactiontime.name##']       = $item['name'];
            $tmp['##inactiontime.requesters##'] = $item['requesters'];
            $tmp['##inactiontime.specs##']      = $item['specs'];
            $url                                = urldecode($CFG_GLPI['url_base'].'/front/ticket.form.php?id='.$item['id']);
            $tmp['##inactiontime.id##']        = '<a href="'.$url.'" title="'.$item['name'].'">'.$item['id'].'</a>';
            
            $this->data['inactiontime'][] = $tmp;
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
            'inactiontime.id'           => __('ID (URL)', 'etn'),
            'inactiontime.name'         => __('Title'),
            'inactiontime.requesters'   => __('Инициаторы', 'etn'),
            'inactiontime.specs'        => __('Специалисты', 'etn'),
            'inactiontime.action'       => 'Отчет о нарушении времени бездействия по заявкам за '.date('d-m-Y H:i')
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
                    'itemtype' => 'GlpiPlugin\Etn\InactionTime'
                ]
            ]);

            if (!count($req)) {
                $DB->insertOrDie(
                    'glpi_notificationtemplates', [
                        'name'           => 'Отчет о нарушении времени бездействия по заявкам',
                        'itemtype'       => 'GlpiPlugin\Etn\InactionTime',
                        'date_mod'       => date('Y-m-d H:i:s'),
                        'date_creation'  => date('Y-m-d H:i:s')
                    ]
                );
                $templates_id = $DB->insertId();
                $DB->insertOrDie(
                    'glpi_notificationtemplatetranslations', [
                        'notificationtemplates_id'  => $templates_id,
                        'language'                  => '',
                        'subject'                   => '##lang.inactiontime.action##',
                        'content_html'              => 
                            "&lt;p&gt;##lang.inactiontime.action##&lt;table style=\"border-collapse: collapse;\"&gt;&lt;tr&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.inactiontime.id##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.inactiontime.name##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.inactiontime.requesters##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.inactiontime.specs##&lt;/th&gt;&lt;/tr&gt;".
                            "##FOREACHinactiontime##&lt;tr&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##inactiontime.id##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##inactiontime.name##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##inactiontime.requesters##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##inactiontime.specs##&lt;/td&gt;&lt;/tr&gt;".
                            "##ENDFOREACHinactiontime##&lt;/table&gt;&lt;/p&gt;",
                    ]
                );
                $DB->insertOrDie(
                    'glpi_notifications', [
                        'name'          => 'Отчет о нарушении времени бездействия по заявкам',
                        'itemtype'      => 'GlpiPlugin\Etn\InactionTime',
                        'entities_id'   => 0,
                        'event'         => 'inaction_time',
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