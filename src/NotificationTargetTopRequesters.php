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

class NotificationTargetTopRequesters extends \NotificationTarget {

    const TOP_REQUESTERS  = 'top_requesters';

    /**
     * @return array
     */
    function getEvents() {
        return [
            self::TOP_REQUESTERS  => 'Топ инициаторов по заявкам'
        ];
    }

    /**
    * @param       $event
    * @param array $options
    */
    function addDataForTemplate($event, $options = []) {
        global $CFG_GLPI;

        $this->data['##toprequesters.entity##'] = \Dropdown::getDropdownName('glpi_entities', $options['entities_id']);

        foreach ($options['toprequesters'] as $id => $item) {
            $tmp = [];
            $tmp['##toprequesters.cnt##']       = $item['cnt'];
            $tmp['##toprequesters.requester##'] = $item['requester'];
            $tmp['##toprequesters.number##']    = $item['number'];
            $this->data['toprequesters'][] = $tmp;
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
        $month = [
            'январь',
            'февраль',
            'март',
            'апрель',
            'май',
            'июнь',
            'июль',
            'август',
            'сентябрь',
            'октябрь',
            'ноябрь',
            'декабрь'
        ];
        $tags = [
            'toprequesters.requester'   => __('Requester'),
            'toprequesters.cnt'         => __('Tickets'),
            'toprequesters.number'      => '№',
            'toprequesters.action'      => 'Топ инициаторов по заявкам за '.$month[date('n')-1].' '.date('Y')
        ];

        foreach ($tags as $tag => $label) {
        $this->addTagToList(['tag'   => $tag, 'label' => $label,
                                'value' => true]);
        }
        asort($this->tag_descriptions);
    }

    /**
     *
    */
    static function init() {
        global $DB;
        $req = $DB->request([
            'SELECT'    => 'id',
            'FROM'      => 'glpi_notificationtemplates',
            'WHERE'     => [
                'itemtype' => 'GlpiPlugin\Etn\TopRequesters'
            ]
        ]);

        if (!count($req)) {
            $DB->insertOrDie(
                'glpi_notificationtemplates', [
                    'name'           => 'Топ инициаторов по заявкам',
                    'itemtype'       => 'GlpiPlugin\Etn\TopRequesters',
                    'date_mod'       => date('Y-m-d H:i:s'),
                    'date_creation'  => date('Y-m-d H:i:s')
                ]
            );
            $templates_id = $DB->insertId();
            $DB->insertOrDie(
                'glpi_notificationtemplatetranslations', [
                    'notificationtemplates_id'   => $templates_id,
                    'language'                   => '',
                    'subject'                    => '##lang.toprequesters.action##',
                    'content_html'              => 
                        "&lt;p&gt;##lang.toprequesters.action##&lt;table style=\"border-collapse: collapse;\"&gt;&lt;tr&gt;".
                        "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.toprequesters.number##&lt;/th&gt;".
                        "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.toprequesters.requester##&lt;/th&gt;".
                        "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.toprequesters.cnt##&lt;/th&gt;&lt;/tr&gt;".
                        "##FOREACHtoprequesters##&lt;tr&gt;".
                        "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##toprequesters.number##&lt;/td&gt;".
                        "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##toprequesters.requester##&lt;/td&gt;".
                        "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##toprequesters.cnt##&lt;/td&gt;&lt;/tr&gt;".
                        "##ENDFOREACHtoprequesters##&lt;/table&gt;&lt;/p&gt;",
                ]
            );
            $DB->insertOrDie(
                'glpi_notifications', [
                    'name'          => 'Топ инициаторов по заявкам',
                    'itemtype'      => 'GlpiPlugin\Etn\TopRequesters',
                    'entities_id'   => 0,
                    'event'         => 'top_requesters',
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
    }
}