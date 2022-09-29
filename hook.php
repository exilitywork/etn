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

use GlpiPlugin\Etn\Process;
use GlpiPlugin\Etn\Config;

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_etn_install() {
   global $DB;

   $create_table_query = "
      CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_processes`
      (
         `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
         `users_id` INT UNSIGNED NOT NULL,
         `documents_id` INT UNSIGNED NOT NULL,
         PRIMARY KEY (`id`),
         KEY (`users_id`),
         KEY (`documents_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
   ";
   $DB->query($create_table_query) or die($DB->error());

   $create_table_query = "
      CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_ratings`
      (
         `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
         `tickets_id` INT UNSIGNED NOT NULL,
         `status` BOOL DEFAULT 0,
         `date_create` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
         `date_mod` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
         PRIMARY KEY (`id`),
         KEY (`tickets_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
   ";
   $DB->query($create_table_query) or die($DB->error());

   $create_table_query = "
      CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_configs`
      (
         `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
         `ldap_id` INT UNSIGNED NOT NULL,
         `ldap_photo_field` VARCHAR(255),
         PRIMARY KEY (`id`),
         KEY (`ldap_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
   ";
   $DB->query($create_table_query) or die($DB->error());

   $user = new \User();
   Config::updateConfig();

   $user = new \User();
   $users = $user->find();
   foreach($users as $user) {
      if(!$user['picture']) continue;
      $file = $user['picture'];
      $tag = trim(Document::getImageTag(Rule::getUuid()), '#');
      $filename = mb_substr($file, strpos($file, '_') + 1);
      $prefix = mb_substr($file, strpos($file, '/') + 1, strpos($file, '_') - strpos($file, '/'));
      if(file_exists(GLPI_PICTURE_DIR.'/'.$file)) {
         copy(GLPI_PICTURE_DIR.'/'.$file, GLPI_TMP_DIR.'/'.$prefix.$filename);
      } else {
         continue;
      }

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
      $proc->fields['users_id'] = $user['id'];
      $proc->fields['documents_id'] = $docID;
      if($curProc = current($proc->find(['users_id' => $user['id']], [], 1))) {
         $proc->fields['id'] = $curProc['id'];
         $proc->updateInDB(array_keys($proc->fields));
      } else {
         $proc->addToDB();
      }
   }

   return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_etn_uninstall() {
   global $DB;

   if ($DB->tableExists('glpi_plugin_etn_processes')) {
      //$DB->query('DROP TABLE `glpi_plugin_etn_processes`');
   }

   if ($DB->tableExists('glpi_plugin_etn_ratings')) {
      //$DB->query('DROP TABLE `glpi_plugin_etn_ratings`');
   }

   if ($DB->tableExists('glpi_plugin_etn_configs')) {
      //$DB->query('DROP TABLE `glpi_plugin_etn_configs`');
   }

   return true;
}