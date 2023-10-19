CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_processes`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `users_id` INT UNSIGNED NOT NULL,
    `documents_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY (`users_id`),
    KEY (`documents_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_ratings`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tickets_id` INT UNSIGNED NOT NULL,
    `users_id` INT UNSIGNED,
    `status` BOOL DEFAULT 0,
    `date_create` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `date_mod` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY (`tickets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_ldaps`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ldap_id` INT UNSIGNED NOT NULL,
    `ldap_photo_field` VARCHAR(255),
    PRIMARY KEY (`id`),
    KEY (`ldap_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_configs`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `option` VARCHAR(255) NOT NULL,
    `value` VARCHAR(255),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_chats`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `chat_id` INT UNSIGNED NOT NULL,
    `username` VARCHAR(255),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_users`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `users_id` INT UNSIGNED NOT NULL,
    `username` VARCHAR(255),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_slainfos`
(
    `id` INT UNSIGNED NOT NULL,
    `date` VARCHAR(10) NOT NULL,
    `groups_id` VARCHAR(250) NOT NULL,
    `sla_all` INT UNSIGNED DEFAULT 0,
    `sla_false` INT UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_openticketsinfos`
(
    `id` INT UNSIGNED NOT NULL,
    `date_begin` VARCHAR(10) NOT NULL,
    `date_end` VARCHAR(10) NOT NULL,
    `groups_id` VARCHAR(250) NOT NULL,
    `status` INT UNSIGNED NOT NULL,
    `satisfaction` INT UNSIGNED,
    `time_to_solve` INT UNSIGNED,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_toprequesters`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_inactiontimes`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `categories_id` INT UNSIGNED NOT NULL,
    `inaction_time` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_inactiontimes_groups_users`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `groups_id` INT UNSIGNED NOT NULL,
    `users_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_expiredslas`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `users_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_itemtypes`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `itemtypes_id` VARCHAR(250) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_itemtyperecipients`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `users_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_takeintoaccounttimes`
(
    `id` INT UNSIGNED NOT NULL,
    `takeintoacсount_time` INT UNSIGNED NOT NULL,
    `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_etn_takeintoaccounttimerecipients`
(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `users_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;