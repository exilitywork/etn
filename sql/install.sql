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
  