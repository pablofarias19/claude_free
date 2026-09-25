-- Migration 001: Add category column to chat_bot_responses
-- Run this ONLY if content_manager.php auto-migration failed
-- Safe to run multiple times (IF NOT EXISTS supported in MySQL 8+)

ALTER TABLE `chat_bot_responses`
  ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) DEFAULT 'General' AFTER `is_active`;

-- chat_canned already has category column in schema.
-- Update existing rows with no category to 'General'
UPDATE `chat_bot_responses` SET `category` = 'General' WHERE `category` IS NULL OR `category` = '';
