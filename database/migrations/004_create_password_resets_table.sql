CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         bigint unsigned  NOT NULL AUTO_INCREMENT,
    `email`      varchar(255)     NOT NULL,
    `token`      varchar(64)      NOT NULL,
    `created_at` timestamp        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `password_resets_token_unique` (`token`),
    KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
