/* v0.10.0 */
-- ALTER TABLE `unit` ADD `calendar_url` VARCHAR(255) NULL DEFAULT NULL AFTER `base_longitude`;

/* 0.13.0 */
-- ALTER TABLE `alarm_device_authorized` ADD `calendar_show` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `ad_expires_at`;