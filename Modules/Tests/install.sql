set FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `els_tests`;
CREATE TABLE `els_tests` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` INT(10) UNSIGNED NOT NULL,
  `title` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `els_tests_ref_sites` (`site_id`),
  CONSTRAINT `els_tests_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;

DROP TABLE IF EXISTS `els_tests_questions`;
CREATE TABLE `els_tests_questions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` INT(10) UNSIGNED NOT NULL,
  `test_id` INT(10) UNSIGNED NOT NULL,
  `type` ENUM('single','multi') NOT NULL,
  `weight` INT(10) UNSIGNED NOT NULL DEFAULT '1',
  `body` TEXT NULL,
  `is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `els_tests_questions_ref_tests` (`test_id`),
  INDEX `els_tests_questions_ref_sites` (`site_id`),
  INDEX `type` (`type`),
  CONSTRAINT `els_tests_questions_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `els_tests_questions_ref_tests` FOREIGN KEY (`test_id`) REFERENCES `els_tests` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;

DROP TABLE IF EXISTS `els_tests_answers`;
CREATE TABLE `els_tests_answers` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` INT(10) UNSIGNED NOT NULL,
  `question_id` INT(10) UNSIGNED NOT NULL,
  `is_right` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `body` TEXT NULL,
  `is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `els_tests_answers_ref_tests` (`question_id`),
  INDEX `els_tests_answers_ref_sites` (`site_id`),
  CONSTRAINT `els_tests_answers_ref_questions` FOREIGN KEY (`question_id`) REFERENCES `els_tests_questions` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `els_tests_answers_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;


DROP TABLE IF EXISTS `els_tests_results`;
CREATE TABLE `els_tests_results` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` INT(10) UNSIGNED NOT NULL,
  `test_id` INT(10) UNSIGNED NOT NULL,
  `status` ENUM('started','inprogress','finished') NOT NULL DEFAULT 'started',
  `user_id` INT(10) UNSIGNED NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `els_tests_results_ref_tests` (`test_id`),
  INDEX `els_tests_results_ref_sites` (`site_id`),
  INDEX `user_id` (`user_id`),
  CONSTRAINT `els_tests_results_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `els_tests_results_ref_tests` FOREIGN KEY (`test_id`) REFERENCES `els_tests` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `els_tests_results_ref_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;


DROP TABLE IF EXISTS `els_tests_results_ref_answers`;
CREATE TABLE `els_tests_results_ref_answers` (
  `result_id` INT(10) UNSIGNED NOT NULL,
  `question_id` INT(10) UNSIGNED NOT NULL,
  `answer_id` INT(10) UNSIGNED NOT NULL,
  `is_right` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`result_id`, `question_id`, `answer_id`),
  INDEX `is_right` (`is_right`),
  CONSTRAINT `els_tests_results_ref_answers_rfk` FOREIGN KEY (`result_id`) REFERENCES `els_tests_results` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;



DROP TABLE IF EXISTS `els_tests_files`;
CREATE TABLE `els_tests_files` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `site_id` INT(10) UNSIGNED NOT NULL,
  `name` VARCHAR(255) NULL DEFAULT NULL,
  `file` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `FK_eels_tests_files_els_tests_questions` (`question_id`),
  INDEX `FK_eels_tests_files_els_tests_ref_sites` (`site_id`),
  CONSTRAINT `FK_eels_tests_files_els_tests_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `FK_eels_tests_files_els_tests_questions` FOREIGN KEY (`question_id`) REFERENCES `els_tests_questions` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;



set FOREIGN_KEY_CHECKS=1;

INSERT INTO `cms_permissions` (`id`, `description`) VALUES ('tests.can_manage_tests', 'Пользователь может управлять тестами');

-- v 0.2 --

ALTER TABLE `els_tests`
	CHANGE COLUMN `title` `title` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci' AFTER `site_id`;

-- v 0.1.2 --

ALTER TABLE `els_tests_results`
	ADD COLUMN `settings` TEXT NULL DEFAULT NULL AFTER `updated_by`;

ALTER TABLE `els_tests_results`
	ADD COLUMN `mark` FLOAT UNSIGNED NULL AFTER `settings`;

ALTER TABLE `els_tests_results`
	ADD COLUMN `task_id` INT(10) UNSIGNED NULL AFTER `test_id`;


INSERT INTO `cms_permissions` (`id`, `description`) VALUES ('tests.can_manage_attempts', 'Пользователь может управлять попытками тестирования');

-- v 0.1.3 --
ALTER TABLE `els_tests_results_ref_answers`
ADD COLUMN `text_answer` TEXT NOT NULL AFTER `is_right`;

ALTER TABLE `els_tests_results_ref_answers`
ADD COLUMN `mark` INT(10) NULL DEFAULT NULL AFTER `text_answer`;


CREATE TABLE `els_tests_answer_result_files` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `answer_result_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `site_id` INT(10) UNSIGNED NOT NULL,
  `name` VARCHAR(255) NULL DEFAULT NULL,
  `file` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;

CREATE TABLE `els_tests_results_ref_answers2` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `result_id` INT(10) UNSIGNED NOT NULL,
  `question_id` INT(10) UNSIGNED NOT NULL,
  `answer_id` INT(10) UNSIGNED NOT NULL,
  `is_right` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `text_answer` TEXT NOT NULL,
  `mark` INT(10) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `els_tests_results_ref_answers_rfk` (`result_id`),
  CONSTRAINT `els_tests_results_ref_answers_rfk2` FOREIGN KEY (`result_id`) REFERENCES `els_tests_results` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;
  
insert into els_tests_results_ref_answers2 
(`result_id` ,`question_id`,`answer_id`,`is_right` ,`text_answer` ,`mark`) (SELECT `result_id` ,`question_id`,`answer_id`,`is_right` ,`text_answer` ,`mark` FROM els_tests_results_ref_answers);
  
DROP TABLE `els_tests_results_ref_answers`;

RENAME TABLE `els_tests_results_ref_answers2` TO `els_tests_results_ref_answers`;

ALTER TABLE `els_tests_questions`
ALTER `type` DROP DEFAULT;
ALTER TABLE `els_tests_questions`
CHANGE COLUMN `type` `type` ENUM('single','multi','free') NOT NULL AFTER `test_id`;

ALTER TABLE `els_tests_answer_result_files`
ADD COLUMN `extension` VARCHAR(5) NULL DEFAULT NULL AFTER `file`;

ALTER TABLE `els_tests_questions`
ADD COLUMN `allow_add_files` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `is_deleted`;

ALTER TABLE `els_tests_results`
CHANGE COLUMN `status` `status` ENUM('started','inprogress','finished','verification','fail') NOT NULL DEFAULT 'started' AFTER `task_id`;


ALTER TABLE `els_tests_results_ref_answers`
ADD COLUMN `created_at` DATETIME NULL DEFAULT NULL AFTER `mark`,
ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL AFTER `created_at`,
ADD COLUMN `created_by` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `updated_at`,
ADD COLUMN `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `created_by`;

--  v 0.1.4 --
--  v 0.1.5 --