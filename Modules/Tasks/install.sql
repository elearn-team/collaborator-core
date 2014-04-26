set FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `els_tasks`;
CREATE TABLE `els_tasks` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` INT(10) UNSIGNED NOT NULL,
  `type` VARCHAR(255) NULL,
  `title` VARCHAR(255) NULL,
  `lft` INT(10) NOT NULL,
  `rgt` INT(10) NOT NULL,
  `depth` INT(10) UNSIGNED NOT NULL,
  `is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `els_tasks_ref_sites` (`site_id`),
  CONSTRAINT `els_tasks_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;

DROP TABLE IF EXISTS  `els_tasks_ref_users`;
CREATE TABLE `els_tasks_ref_users` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` INT(10) UNSIGNED NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `status` ENUM('started','inprogress','finished') NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
  `result_id` INT(10) UNSIGNED NULL,
  `mark` FLOAT UNSIGNED NULL,
  `attempts_count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `FK_els_tasks_ref_users_users` (`user_id`),
  INDEX `FK_els_tasks_ref_users_tasks` (`task_id`),
  CONSTRAINT `FK_els_tasks_ref_users_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `FK_els_tasks_ref_users_tasks` FOREIGN KEY (`task_id`) REFERENCES `els_tasks` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_unicode_ci'
  ENGINE=InnoDB;


DROP TABLE IF EXISTS  `els_tasks_test_settings`;
CREATE TABLE `els_tasks_test_settings` (
  `task_id` INT(10) UNSIGNED NOT NULL,
  `test_id` INT(10) UNSIGNED NOT NULL,
  `all_questions` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `questions_count` INT(10) UNSIGNED NOT NULL,
  `unlim_attempts` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `attempts_count` INT(10) UNSIGNED NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`task_id`),
  CONSTRAINT `FK_els_tasks_test_settings_tasks` FOREIGN KEY (`task_id`) REFERENCES `els_tasks` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_unicode_ci'
  ENGINE=InnoDB;

set FOREIGN_KEY_CHECKS=1;

INSERT INTO `cms_permissions` (`id`, `description`) VALUES ('tasks.can_manage_tasks', 'Пользователь может управлять заданиями');

ALTER TABLE `els_tasks_test_settings`
	ADD COLUMN `time` INT(10) NULL DEFAULT NULL AFTER `updated_by`;

ALTER TABLE `els_tasks`
	ADD COLUMN `description` TEXT NULL DEFAULT NULL AFTER `type`;


ALTER TABLE `els_tasks`
  ADD COLUMN `course_id` INT(10) UNSIGNED NULL AFTER `site_id`;

ALTER TABLE `els_tasks`
  ADD COLUMN `element_id` INT(10) UNSIGNED NULL AFTER `course_id`;

ALTER TABLE `els_tasks`
  ADD COLUMN `threshold` INT(3) UNSIGNED NULL AFTER `title`;

ALTER TABLE `els_tasks_test_settings`
  DROP COLUMN `test_id`;

  ALTER TABLE `els_tasks_test_settings`
	ADD COLUMN `training` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `attempts_count`;

ALTER TABLE `els_tasks`
  CHANGE COLUMN `type` `type` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci' AFTER `element_id`;

ALTER TABLE `els_tasks`
  CHANGE COLUMN `course_id` `parent_id` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `site_id`;

-- v 0.2 --
-- v 0.1.2 --
ALTER TABLE `els_tasks_ref_users`
  DROP COLUMN `result_id`;

ALTER TABLE `els_tasks_ref_users`
  ADD COLUMN `attempts_limit` INT(10) UNSIGNED NULL DEFAULT '0' AFTER `mark`;

ALTER TABLE `els_tasks`
ADD CONSTRAINT `FK_parents` FOREIGN KEY (`parent_id`) REFERENCES `els_tasks` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;

-- v 0.1.3 --

ALTER TABLE `els_tasks_ref_users`
CHANGE COLUMN `status` `status` ENUM('started','inprogress','finished','verification','fail') NULL DEFAULT NULL COLLATE 'utf8_unicode_ci' AFTER `user_id`;
--  v 0.1.4 --
--  v 0.1.5 --