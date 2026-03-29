-- Migration: 002 — Add role and status columns to users table
-- Run AFTER 001_create_users_table.sql

ALTER TABLE `users`
    ADD COLUMN `role`   VARCHAR(20) NOT NULL DEFAULT 'user'   AFTER `password`,
    ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `role`;
