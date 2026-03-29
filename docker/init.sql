-- Docker init: runs once when the MySQL container is first created.
-- Includes the full schema with role and status columns.
-- On shared hosting, run migrations 001 then 002 via phpMyAdmin, then run the seeder.

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
