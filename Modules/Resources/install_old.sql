-- --------------------------------------------------------
-- Сервер:                       144.76.91.146
-- Server version:               5.5.31-0+wheezy1 - (Debian)
-- Server OS:                    debian-linux-gnu
-- HeidiSQL Версія:              8.0.0.4396
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

USE `elearn_et`;


-- Dumping structure for table elearn_et.els_courses
DROP TABLE IF EXISTS `els_courses`;
CREATE TABLE IF NOT EXISTS `els_courses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `rating_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `max_score` int(10) unsigned DEFAULT NULL,
  `threshold` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `els_courses_ref_sites` (`site_id`),
  CONSTRAINT `els_courses_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.els_courses: ~0 rows (approximately)
DELETE FROM `els_courses`;
/*!40000 ALTER TABLE `els_courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_courses` ENABLE KEYS */;


-- Dumping structure for table elearn_et.els_courses_classifiers
DROP TABLE IF EXISTS `els_courses_classifiers`;
CREATE TABLE IF NOT EXISTS `els_courses_classifiers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL DEFAULT '0',
  `course_id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `els_courses_classifiers_ref_courses` (`course_id`),
  KEY `els_courses_classifiers_ref_sites` (`site_id`),
  KEY `value` (`value`),
  KEY `name` (`name`),
  CONSTRAINT `els_courses_classifiers_ref_courses` FOREIGN KEY (`course_id`) REFERENCES `els_courses` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `els_courses_classifiers_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.els_courses_classifiers: ~0 rows (approximately)
DELETE FROM `els_courses_classifiers`;
/*!40000 ALTER TABLE `els_courses_classifiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_courses_classifiers` ENABLE KEYS */;


-- Dumping structure for table elearn_et.els_courses_requests
DROP TABLE IF EXISTS `els_courses_requests`;
CREATE TABLE IF NOT EXISTS `els_courses_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `els_courses_requests_ref_users` (`user_id`),
  KEY `els_courses_requests_ref_courses` (`course_id`),
  KEY `els_courses_requests_ref_sites` (`site_id`),
  CONSTRAINT `els_courses_requests_ref_courses` FOREIGN KEY (`course_id`) REFERENCES `els_courses` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `els_courses_requests_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `els_courses_requests_ref_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.els_courses_requests: ~0 rows (approximately)
DELETE FROM `els_courses_requests`;
/*!40000 ALTER TABLE `els_courses_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_courses_requests` ENABLE KEYS */;


-- Dumping structure for table elearn_et.els_courses_resources
DROP TABLE IF EXISTS `els_courses_resources`;
CREATE TABLE IF NOT EXISTS `els_courses_resources` (
  `course_id` int(10) unsigned NOT NULL,
  `resource_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`course_id`,`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.els_courses_resources: ~0 rows (approximately)
DELETE FROM `els_courses_resources`;
/*!40000 ALTER TABLE `els_courses_resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_courses_resources` ENABLE KEYS */;


-- Dumping structure for table elearn_et.els_deans
DROP TABLE IF EXISTS `els_deans`;
CREATE TABLE IF NOT EXISTS `els_deans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `els_deans_ref_users` (`user_id`),
  KEY `els_deans_ref_sites` (`site_id`),
  CONSTRAINT `els_deans_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `els_deans_ref_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.els_deans: ~0 rows (approximately)
DELETE FROM `els_deans`;
/*!40000 ALTER TABLE `els_deans` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_deans` ENABLE KEYS */;


-- Dumping structure for table elearn_et.els_html_pages
DROP TABLE IF EXISTS `els_html_pages`;
CREATE TABLE IF NOT EXISTS `els_html_pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `body` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `els_html_pages_ref_sites` (`site_id`),
  CONSTRAINT `els_html_pages_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.els_html_pages: ~0 rows (approximately)
DELETE FROM `els_html_pages`;
/*!40000 ALTER TABLE `els_html_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_html_pages` ENABLE KEYS */;


-- Dumping structure for table elearn_et.els_resources
DROP TABLE IF EXISTS `els_resources`;
CREATE TABLE IF NOT EXISTS `els_resources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `source` tinyint(2) unsigned NOT NULL,
  `access_level` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `dest_type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_id_resource_type` (`item_id`,`source`),
  KEY `els_resources_ref_sites` (`site_id`),
  CONSTRAINT `els_resources_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.els_resources: ~0 rows (approximately)
DELETE FROM `els_resources`;
/*!40000 ALTER TABLE `els_resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_resources` ENABLE KEYS */;


-- Dumping structure for table elearn_et.els_resources_classifiers
DROP TABLE IF EXISTS `els_resources_classifiers`;
CREATE TABLE IF NOT EXISTS `els_resources_classifiers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL DEFAULT '0',
  `resource_id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `els_resources_classifiers_ref_resources` (`resource_id`),
  KEY `els_resources_classifiers_ref_sites` (`site_id`),
  KEY `value` (`value`),
  KEY `name` (`name`),
  CONSTRAINT `els_resources_classifiers_ref_resources` FOREIGN KEY (`resource_id`) REFERENCES `els_resources` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `els_resources_classifiers_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.els_resources_classifiers: ~0 rows (approximately)
DELETE FROM `els_resources_classifiers`;
/*!40000 ALTER TABLE `els_resources_classifiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_resources_classifiers` ENABLE KEYS */;


-- Dumping structure for table elearn_et.els_tests
DROP TABLE IF EXISTS `els_tests`;
CREATE TABLE IF NOT EXISTS `els_tests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL,
  `title` text,
  `type` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `els_tests_ref_sites` (`site_id`),
  CONSTRAINT `els_tests_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table elearn_et.els_tests: ~0 rows (approximately)
DELETE FROM `els_tests`;
/*!40000 ALTER TABLE `els_tests` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_tests` ENABLE KEYS */;


-- Dumping structure for table elearn_et.els_tests_answers
DROP TABLE IF EXISTS `els_tests_answers`;
CREATE TABLE IF NOT EXISTS `els_tests_answers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL,
  `test_id` int(10) unsigned NOT NULL,
  `body` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `els_tests_answers_ref_tests` (`test_id`),
  KEY `els_tests_answers_ref_sites` (`site_id`),
  CONSTRAINT `els_tests_answers_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `els_tests_answers_ref_tests` FOREIGN KEY (`test_id`) REFERENCES `els_tests` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table elearn_et.els_tests_answers: ~0 rows (approximately)
DELETE FROM `els_tests_answers`;
/*!40000 ALTER TABLE `els_tests_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_tests_answers` ENABLE KEYS */;


-- Dumping structure for table elearn_et.els_tutors
DROP TABLE IF EXISTS `els_tutors`;
CREATE TABLE IF NOT EXISTS `els_tutors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `site_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_course_id` (`user_id`,`course_id`),
  KEY `els_tutors_ref_sites` (`site_id`),
  CONSTRAINT `els_tutors_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `els_tutors_ref_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.els_tutors: ~0 rows (approximately)
DELETE FROM `els_tutors`;
/*!40000 ALTER TABLE `els_tutors` DISABLE KEYS */;
/*!40000 ALTER TABLE `els_tutors` ENABLE KEYS */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
