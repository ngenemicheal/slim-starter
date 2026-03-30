CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id`         bigint unsigned  NOT NULL AUTO_INCREMENT,
    `user_id`    bigint unsigned  NOT NULL,
    `token`      varchar(64)      NOT NULL,
    `created_at` timestamp        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email_verifications_token_unique` (`token`),
    KEY `email_verifications_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
