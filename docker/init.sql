-- Docker init: runs once when the MySQL container is first created.
-- Mirrors database/migrations/ exactly.
-- For all other environments, use: php database/migrate.php

CREATE TABLE IF NOT EXISTS `users` (
    `id`                bigint unsigned  NOT NULL AUTO_INCREMENT,
    `name`              varchar(255)     NOT NULL,
    `email`             varchar(255)     NOT NULL,
    `password`          varchar(255)     NOT NULL,
    `role`              varchar(20)      NOT NULL DEFAULT 'user',
    `status`            varchar(20)      NOT NULL DEFAULT 'active',
    `email_verified_at` timestamp        NULL DEFAULT NULL,
    `remember_token`    varchar(100)     DEFAULT NULL,
    `created_at`        timestamp        NULL DEFAULT NULL,
    `updated_at`        timestamp        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id`         bigint unsigned  NOT NULL AUTO_INCREMENT,
    `user_id`    bigint unsigned  NOT NULL,
    `token`      varchar(64)      NOT NULL,
    `created_at` timestamp        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email_verifications_token_unique` (`token`),
    KEY `email_verifications_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         bigint unsigned  NOT NULL AUTO_INCREMENT,
    `email`      varchar(255)     NOT NULL,
    `token`      varchar(64)      NOT NULL,
    `created_at` timestamp        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `password_resets_token_unique` (`token`),
    KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
