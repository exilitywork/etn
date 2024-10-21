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
 * @copyright Copyright (C) 2022-2024 by Oleg Кapeshko
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/exilitywork/etn
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Etn;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class NotificationTargetProblemInactionTime extends \NotificationTarget {

    const PROBLEM_INACTION_TIME  = 'problem_inaction_time';

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
            self::PROBLEM_INACTION_TIME  => 'Отчет о нарушении времени бездействия по проблемам'
        ];
    }

    /**
    * @param       $event
    * @param array $options
    */
    function addDataForTemplate($event, $options = []) {
        global $CFG_GLPI;

        $this->data['##probleminactiontime.entity##'] = \Dropdown::getDropdownName('glpi_entities', $options['entities_id']);

        foreach ($options['probleminactiontime'] as $id => $item) {
            $tmp = [];
            $tmp['##probleminactiontime.name##']       = $item['name'];
            $tmp['##probleminactiontime.date##']       = $item['date'];
            $tmp['##probleminactiontime.requesters##'] = $item['requesters'];
            $tmp['##probleminactiontime.specs##']      = $item['specs'];
            $url                                = urldecode($CFG_GLPI['url_base'].'/front/problem.form.php?id='.$item['id']);
            $tmp['##probleminactiontime.id##']        = '<a href="'.$url.'" title="'.$item['name'].'">'.$item['id'].'</a>';
            
            $this->data['probleminactiontime'][] = $tmp;
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
            'probleminactiontime.id'           => __('ID (URL)', 'etn'),
            'probleminactiontime.name'         => __('Title'),
            'probleminactiontime.date'         => __('Opening date'),
            'probleminactiontime.requesters'   => __('Инициаторы', 'etn'),
            'probleminactiontime.specs'        => __('Специалисты', 'etn'),
            'probleminactiontime.action'       => 'Отчет о нарушении времени бездействия по проблемам за '.date('d-m-Y H:i')
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
                    'itemtype' => 'GlpiPlugin\Etn\ProblemInactionTime'
                ]
            ]);

            if (!count($req)) {
                $DB->insertOrDie(
                    'glpi_notificationtemplates', [
                        'name'           => 'ETN - Отчет о нарушении времени бездействия по проблемам',
                        'itemtype'       => 'GlpiPlugin\Etn\ProblemInactionTime',
                        'date_mod'       => date('Y-m-d H:i:s'),
                        'date_creation'  => date('Y-m-d H:i:s')
                    ]
                );
                $templates_id = $DB->insertId();
                $DB->insertOrDie(
                    'glpi_notificationtemplatetranslations', [
                        'notificationtemplates_id'  => $templates_id,
                        'language'                  => '',
                        'subject'                   => '##lang.probleminactiontime.action##',
                        'content_html'              => 
                            "&lt;p&gt;##lang.probleminactiontime.action##&lt;table style=\"border-collapse: collapse;\"&gt;&lt;tr&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.probleminactiontime.id##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.probleminactiontime.name##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.probleminactiontime.date##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.probleminactiontime.requesters##&lt;/th&gt;".
                            "&lt;th style=\"border: 1px solid black; padding: 3px;\"&gt;##lang.probleminactiontime.specs##&lt;/th&gt;&lt;/tr&gt;".
                            "##FOREACHprobleminactiontime##&lt;tr&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##probleminactiontime.id##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##probleminactiontime.name##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##probleminactiontime.date##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##probleminactiontime.requesters##&lt;/td&gt;".
                            "&lt;td style=\"border: 1px solid black; padding: 3px;\"&gt;##probleminactiontime.specs##&lt;/td&gt;&lt;/tr&gt;".
                            "##ENDFOREACHprobleminactiontime##&lt;/table&gt;&lt;/p&gt;",
                        'content_text'              => '##lang.probleminactiontime.action##'
                    ]
                );
                $DB->insertOrDie(
                    'glpi_notifications', [
                        'name'          => 'ETN - Отчет о нарушении времени бездействия по проблемам',
                        'itemtype'      => 'GlpiPlugin\Etn\ProblemInactionTime',
                        'entities_id'   => 0,
                        'event'         => 'problem_inaction_time',
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