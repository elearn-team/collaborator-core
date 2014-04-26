set FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `els_courses`;
CREATE TABLE `els_courses` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` INT(10) UNSIGNED NOT NULL,
  `title` VARCHAR(255) NULL,
  `description` TEXT NULL DEFAULT NULL,
  `is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `els_courses_ref_sites` (`site_id`),
  CONSTRAINT `els_courses_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;
/*
DROP TABLE IF EXISTS  `els_tasks_courses_settings`;
CREATE TABLE `els_tasks_courses_settings` (
  `task_id` INT(10) UNSIGNED NOT NULL,
  `course_id` INT(10) UNSIGNED NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`task_id`),
  CONSTRAINT `FK_els_tasks_courses_settings` FOREIGN KEY (`task_id`) REFERENCES `els_tasks` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_unicode_ci'
  ENGINE=InnoDB;*/
  
DROP TABLE IF EXISTS `els_courses_elements`;
CREATE TABLE `els_courses_elements` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`course_id` INT(10) UNSIGNED NOT NULL,
	`element_id` INT(10) UNSIGNED NOT NULL,
	`type` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
	`created_at` DATETIME NULL DEFAULT NULL,
	`updated_at` DATETIME NULL DEFAULT NULL,
	`created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `element_id` (`element_id`),
	INDEX `type` (`type`),
	CONSTRAINT `FK_els_courses_elements_ref_courses` FOREIGN KEY (`course_id`) REFERENCES `els_courses` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB;

ALTER TABLE `els_courses_elements`
	ADD COLUMN `order` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `updated_by`;

DROP TABLE IF EXISTS `els_courses_plan`;
CREATE TABLE `els_courses_plan` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`course_id` INT(10) UNSIGNED NOT NULL,
	`element_id` INT(10) UNSIGNED NOT NULL,
	`type` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
	`created_at` DATETIME NULL DEFAULT NULL,
	`updated_at` DATETIME NULL DEFAULT NULL,
	`created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `element_id` (`element_id`),
	INDEX `type` (`type`),
	CONSTRAINT `FK_els_courses_plan_ref_courses` FOREIGN KEY (`course_id`) REFERENCES `els_courses` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB;


DROP TABLE IF EXISTS `els_courses_test_setting`;
CREATE TABLE `els_courses_test_setting` (
	`plan_id` INT(10) UNSIGNED NOT NULL,
	`course_id` INT(10) UNSIGNED NOT NULL,
	`test_id` INT(10) UNSIGNED NOT NULL,
	`all_questions` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	`questions_count` INT(10) UNSIGNED NOT NULL,
	`unlim_attempts` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	`attempts_count` INT(10) UNSIGNED NOT NULL,
	`created_at` DATETIME NULL DEFAULT NULL,
	`updated_at` DATETIME NULL DEFAULT NULL,
	`created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`time` INT(10) NULL DEFAULT NULL,
	PRIMARY KEY (`plan_id`),
	CONSTRAINT `FK_els_courses_test_setting_plan` FOREIGN KEY (`plan_id`) REFERENCES `els_courses_plan` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB;

DROP TABLE IF EXISTS `els_courses_users`;
CREATE TABLE `els_courses_users` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`course_id` INT(10) UNSIGNED NOT NULL,
	`user_id` INT(10) UNSIGNED NOT NULL,
	`is_individual` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	`created_at` DATETIME NULL DEFAULT NULL,
	`updated_at` DATETIME NULL DEFAULT NULL,
	`created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `course_id` (`course_id`),
	INDEX `user_id` (`user_id`),
	CONSTRAINT `FK_els_courses_users_courses` FOREIGN KEY (`course_id`) REFERENCES `els_courses` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `FK_els_courses_users_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB;

DROP TABLE IF EXISTS `els_courses_requests`;
CREATE TABLE `els_courses_requests` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`site_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`user_id` INT(10) UNSIGNED NOT NULL,
	`course_id` INT(10) UNSIGNED NOT NULL,
	`created_at` DATETIME NULL DEFAULT NULL,
	`updated_at` DATETIME NULL DEFAULT NULL,
	`created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `els_courses_requests_ref_users` (`user_id`),
	INDEX `els_courses_requests_ref_courses` (`course_id`),
	INDEX `els_courses_requests_ref_sites` (`site_id`),
	CONSTRAINT `els_courses_requests_ref_courses` FOREIGN KEY (`course_id`) REFERENCES `els_courses` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `els_courses_requests_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `els_courses_requests_ref_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB;


set FOREIGN_KEY_CHECKS=1;

INSERT INTO `cms_permissions` (`id`, `description`) VALUES ('courses.can_manage_courses', 'Пользователь может управлять курсами');

ALTER TABLE `els_courses_plan`
	ADD COLUMN `order` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `updated_by`;

ALTER TABLE `els_courses`
	ADD COLUMN `icon` VARCHAR(255) NULL DEFAULT NULL AFTER `title`;

	ALTER TABLE `els_courses_test_setting`
	CHANGE COLUMN `plan_id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
	DROP FOREIGN KEY `FK_els_courses_test_setting_plan`;
	ALTER TABLE `els_courses_test_setting`
	ADD CONSTRAINT `FK_els_courses_test_setting_ref_course` FOREIGN KEY (`course_id`) REFERENCES `els_courses` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;

	ALTER TABLE `els_courses`
	ADD COLUMN `annotation` TEXT NULL AFTER `description`;

ALTER TABLE `els_courses`
	ADD COLUMN `code` VARCHAR(255) NULL DEFAULT NULL AFTER `icon`,
	ADD COLUMN `score_employment` VARCHAR(255) NULL DEFAULT NULL AFTER `code`,
	ADD COLUMN `course_length` INT(10) NULL DEFAULT NULL AFTER `score_employment`;

ALTER TABLE `els_courses_plan`
	ADD COLUMN `start_element` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `element_id`;

ALTER TABLE `els_courses_test_setting`
	ADD COLUMN `training` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `attempts_count`;


ALTER TABLE `els_courses_plan`
	ADD COLUMN `is_determ_final_mark` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `start_element`;

ALTER TABLE `els_courses`
	ADD COLUMN `finish_type` ENUM('summary','by_test') NULL DEFAULT 'summary' AFTER `code`;

ALTER TABLE `els_courses_test_setting`
	ADD COLUMN `threshold` INT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `all_questions`;

ALTER TABLE `els_courses`
	ADD COLUMN `start_type` ENUM('start_page','plan','elements') NULL DEFAULT 'start_page' AFTER `finish_type`;

-- v 0.2 --
ALTER TABLE `els_courses`
	ADD COLUMN `is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `course_length`;

DROP TABLE IF EXISTS `els_courses_files`;
CREATE TABLE IF NOT EXISTS `els_courses_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `size` int(10) unsigned NOT NULL DEFAULT '0',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FC_els_courses_files_els_courses` (`course_id`),
  CONSTRAINT `FC_els_courses_files_els_courses` FOREIGN KEY (`course_id`) REFERENCES `els_courses` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

set FOREIGN_KEY_CHECKS=1;

-- v 0.1.2 --

--  v 0.1.3 --
ALTER TABLE `els_courses`
	ADD COLUMN `registration_for_course` TINYINT(1) NULL DEFAULT '0' AFTER `annotation`;

--  v 0.1.4 --
ALTER TABLE `els_courses`
	ADD COLUMN `category_id` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `course_length`;

DROP TABLE IF EXISTS `els_courses_categories`;
CREATE TABLE IF NOT EXISTS `els_courses_categories` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`site_id` INT(10) UNSIGNED NULL DEFAULT NULL,
	`url` VARCHAR(255) NULL DEFAULT NULL,
	`title` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
	`image` VARCHAR(255) NULL DEFAULT NULL,
	`lft` INT(10) NOT NULL,
	`rgt` INT(10) NOT NULL,
	`depth` INT(10) UNSIGNED NOT NULL,
	`is_hidden` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	`is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
	`created_at` DATETIME NULL DEFAULT NULL,
	`updated_at` DATETIME NULL DEFAULT NULL,
	`created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `FK_els_courses_categories_cms_sites` (`site_id`),
	CONSTRAINT `FK_els_courses_categories_cms_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
);
--  v 0.1.5 --