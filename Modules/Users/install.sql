-- --------------------------------------------------------
-- Сервер:                       127.0.0.1
-- Server version:               5.5.34-0ubuntu0.13.10.1 - (Ubuntu)
-- Server OS:                    debian-linux-gnu
-- HeidiSQL Версія:              8.0.0.4396
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;



-- Dumping structure for table elearn_et.cms_languages
DROP TABLE IF EXISTS `cms_languages`;
CREATE TABLE IF NOT EXISTS `cms_languages` (
  `id` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.cms_languages: ~3 rows (approximately)
DELETE FROM `cms_languages`;
/*!40000 ALTER TABLE `cms_languages` DISABLE KEYS */;
INSERT INTO `cms_languages` (`id`, `title`) VALUES
	('en', 'English'),
	('ru', 'Русский (Russian)'),
	('uk', 'Українська (Ukrainian)');
/*!40000 ALTER TABLE `cms_languages` ENABLE KEYS */;


-- Dumping structure for table elearn_et.cms_languages_ref_sites
DROP TABLE IF EXISTS `cms_languages_ref_sites`;
CREATE TABLE IF NOT EXISTS `cms_languages_ref_sites` (
  `language_id` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `site_id` int(10) unsigned NOT NULL,
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`language_id`,`site_id`),
  KEY `FK__cms_sites` (`site_id`),
  KEY `language_id_site_id_is_active` (`language_id`,`site_id`,`is_active`),
  CONSTRAINT `FK__cms_languages` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK__cms_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.cms_languages_ref_sites: ~0 rows (approximately)
DELETE FROM `cms_languages_ref_sites`;
/*!40000 ALTER TABLE `cms_languages_ref_sites` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_languages_ref_sites` ENABLE KEYS */;


-- Dumping structure for table elearn_et.cms_options
DROP TABLE IF EXISTS `cms_options`;
CREATE TABLE IF NOT EXISTS `cms_options` (
  `site_id` int(10) unsigned DEFAULT NULL,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_id_name` (`site_id`,`name`),
  CONSTRAINT `FK_cms_options_cms_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Options of site';

-- Dumping data for table elearn_et.cms_options: ~0 rows (approximately)
DELETE FROM `cms_options`;
/*!40000 ALTER TABLE `cms_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_options` ENABLE KEYS */;




-- Dumping structure for table elearn_et.cms_roles
DROP TABLE IF EXISTS `cms_roles`;
CREATE TABLE IF NOT EXISTS `cms_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned DEFAULT NULL COMMENT 'Якщо в цьому полі NULL, то цю роль не можна видаляти',
  `title` varchar(255) NOT NULL,
  `description` text NULL,
  `is_guest` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_hidden` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `system_acl` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`title`),
  KEY `FK_cms_roles_cms_sites` (`site_id`),
  CONSTRAINT `FK_cms_roles_cms_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Ролі користувачів';

-- Dumping data for table elearn_et.cms_roles: ~0 rows (approximately)
DELETE FROM `cms_roles`;
/*!40000 ALTER TABLE `cms_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_roles` ENABLE KEYS */;


-- Dumping structure for table elearn_et.cms_roles_permissions
DROP TABLE IF EXISTS `cms_roles_permissions`;
CREATE TABLE IF NOT EXISTS `cms_roles_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `FK__cms_permissions` (`permission_id`),
  CONSTRAINT `FK__cms_permissions` FOREIGN KEY (`permission_id`) REFERENCES `cms_permissions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK__cms_users` FOREIGN KEY (`role_id`) REFERENCES `cms_roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.cms_roles_permissions: ~0 rows (approximately)
DELETE FROM `cms_roles_permissions`;
/*!40000 ALTER TABLE `cms_roles_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_roles_permissions` ENABLE KEYS */;


-- Dumping structure for table elearn_et.cms_roles_ref_users
DROP TABLE IF EXISTS `cms_roles_ref_users`;
CREATE TABLE IF NOT EXISTS `cms_roles_ref_users` (
  `user_id` int(10) unsigned NOT NULL,
  `site_id` int(10) unsigned NOT NULL DEFAULT '1',
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`,`site_id`),
  KEY `FK_cms_roles_ref_users_cms_roles` (`role_id`),
  KEY `FK_cms_roles_ref_users_cms_sites` (`site_id`),
  CONSTRAINT `FK_cms_roles_ref_users_cms_roles` FOREIGN KEY (`role_id`) REFERENCES `cms_roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_cms_roles_ref_users_cms_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_cms_roles_ref_users_cms_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table elearn_et.cms_roles_ref_users: ~0 rows (approximately)
DELETE FROM `cms_roles_ref_users`;
/*!40000 ALTER TABLE `cms_roles_ref_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_roles_ref_users` ENABLE KEYS */;


-- Dumping structure for table elearn_et.cms_sites
DROP TABLE IF EXISTS `cms_sites`;
CREATE TABLE IF NOT EXISTS `cms_sites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '/',
  `secret_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `theme_id` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language_id` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `languages` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `is_subdomain` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_multilingual` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_allow_indexing` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `site_id` int(10) unsigned DEFAULT NULL,
  `is_redirect` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain` (`domain`),
  KEY `FK_cms_sites_cms_sites` (`site_id`),
  KEY `FK_cms_sites_cms_languages` (`language_id`),
  KEY `FK_cms_sites_cms_themes` (`theme_id`),
  CONSTRAINT `FK_cms_sites_cms_languages` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `FK_cms_sites_cms_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `FK_cms_sites_cms_themes` FOREIGN KEY (`theme_id`) REFERENCES `cms_themes` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.cms_sites: ~2 rows (approximately)
DELETE FROM `cms_sites`;
/*!40000 ALTER TABLE `cms_sites` DISABLE KEYS */;
INSERT INTO `cms_sites` (`id`, `domain`, `path`, `secret_key`, `theme_id`, `language_id`, `languages`, `is_subdomain`, `is_active`, `is_multilingual`, `is_allow_indexing`, `created_at`, `updated_at`, `site_id`, `is_redirect`) VALUES
	(1, '', '/', NULL, NULL, 'en', 'en', 0, 1, 0, 0, '2013-11-11 15:30:50', '2013-11-11 15:30:50', NULL, 0);
/*!40000 ALTER TABLE `cms_sites` ENABLE KEYS */;


-- Dumping structure for table elearn_et.cms_sites_locale
DROP TABLE IF EXISTS `cms_sites_locale`;
CREATE TABLE IF NOT EXISTS `cms_sites_locale` (
  `id` int(10) unsigned NOT NULL,
  `lang_id` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`lang_id`),
  KEY `FK_cms_sites_locale_cms_languages` (`lang_id`),
  CONSTRAINT `FK_cms_sites_locale_cms_languages` FOREIGN KEY (`lang_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_cms_sites_locale_cms_sites` FOREIGN KEY (`id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.cms_sites_locale: ~0 rows (approximately)
DELETE FROM `cms_sites_locale`;
/*!40000 ALTER TABLE `cms_sites_locale` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_sites_locale` ENABLE KEYS */;


-- Dumping structure for table elearn_et.cms_sites_ref_users
DROP TABLE IF EXISTS `cms_sites_ref_users`;
CREATE TABLE IF NOT EXISTS `cms_sites_ref_users` (
  `site_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `last_activity` datetime DEFAULT NULL,
  `session_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`site_id`,`user_id`),
  KEY `FK_cms_sites_ref_users_cms_users` (`user_id`),
  CONSTRAINT `FK_cms_sites_ref_users_cms_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_cms_sites_ref_users_cms_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table elearn_et.cms_sites_ref_users: ~0 rows (approximately)
DELETE FROM `cms_sites_ref_users`;
/*!40000 ALTER TABLE `cms_sites_ref_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_sites_ref_users` ENABLE KEYS */;


-- Dumping structure for table elearn_et.cms_themes
DROP TABLE IF EXISTS `cms_themes`;
CREATE TABLE IF NOT EXISTS `cms_themes` (
  `id` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'default',
  `settings` text COLLATE utf8_unicode_ci,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dumping data for table elearn_et.cms_themes: ~0 rows (approximately)
DELETE FROM `cms_themes`;
/*!40000 ALTER TABLE `cms_themes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_themes` ENABLE KEYS */;


-- Dumping structure for table elearn_et.cms_users
DROP TABLE IF EXISTS `cms_users`;
CREATE TABLE IF NOT EXISTS `cms_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstname` varchar(255) DEFAULT NULL COMMENT 'Ім''я',
  `secondname` varchar(255) DEFAULT NULL COMMENT 'Прізвище',
  `patronymic` varchar(255) DEFAULT NULL COMMENT 'По-батькові',
  `gender` enum('unknown','male','female') NOT NULL DEFAULT 'unknown' COMMENT 'Стать',
  `birth_date` date DEFAULT NULL COMMENT 'Дата народження',
  `email` varchar(60) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `last_activity` datetime DEFAULT NULL,
  `session_id` varchar(50) DEFAULT NULL,
  `is_god` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `need_edit` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table elearn_et.cms_users: ~1 rows (approximately)
DELETE FROM `cms_users`;
/*!40000 ALTER TABLE `cms_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_users` ENABLE KEYS */;


-- Dumping structure for table elearn_et.cms_users_settings
DROP TABLE IF EXISTS `cms_users_settings`;
CREATE TABLE IF NOT EXISTS `cms_users_settings` (
  `user_id` int(10) unsigned NOT NULL,
  `setting` varchar(255) NOT NULL DEFAULT '',
  `value` text,
  PRIMARY KEY (`user_id`,`setting`),
  CONSTRAINT `FK_cms_users_settings_cms_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table elearn_et.cms_users_settings: ~0 rows (approximately)
DELETE FROM `cms_users_settings`;
/*!40000 ALTER TABLE `cms_users_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_users_settings` ENABLE KEYS */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;


INSERT INTO `cms_users` (`id`, `login`, `password`, `firstname`, `secondname`, `patronymic`, `gender`, `birth_date`, `email`, `is_active`, `is_god`)
  VALUES (1, 'admin', '4dff4ea340f0a823f15d3f4f01ab62eae0e5da579ccb851f8db9dfe84c58b2b37b89903a740e1ee172da793a6e79d560e5f7f9bd058a12a280433ed6fa46510a', 'Admin', '', '', 'male', '1970-01-01', 'aslubsky@gmail.com', 1, 1);


INSERT INTO `cms_permissions` (`id`, `description`) VALUES ('auth.can_edit_users', 'Пользователь может редактировать других пользователей');
INSERT INTO `cms_permissions` (`id`, `description`) VALUES ('auth.can_delete_user', 'Пользователь может удалять других пользователей, кроме себя');
INSERT INTO `cms_permissions` (`id`, `description`) VALUES ('auth.can_edit_roles', 'Пользователь может изменить роли других пользователей, кроме себя');
INSERT INTO `cms_permissions` (`id`, `description`) VALUES ('auth.can_manage_roles', 'Пользователь может управлять ролями');

INSERT INTO `cms_options` (`site_id`, `name`, `value`) VALUES (1, 'Auth.SplitRoles', '0');

INSERT INTO `cms_roles` (`site_id`, `title`, `description`, `is_guest`, `is_hidden`, `system_acl`) VALUES (1, 'Администратор', '', 0, 0, NULL);
INSERT INTO `cms_roles` (`site_id`, `title`, `description`, `is_guest`, `is_hidden`, `system_acl`) VALUES (1, 'Тьютор', '', 0, 0, NULL);
INSERT INTO `cms_roles` (`site_id`, `title`, `description`, `is_guest`, `is_hidden`, `system_acl`) VALUES (1, 'Пользователь', '', 0, 0, NULL);
INSERT INTO `cms_roles` (`site_id`, `title`, `description`, `is_guest`, `is_hidden`, `system_acl`) VALUES (1, 'Гость', '', 1, 0, NULL);

ALTER TABLE `cms_roles`
CHANGE COLUMN `description` `description` TEXT NULL AFTER `title`;

-- v 0.2 --
-- v 0.1.2 --
--  v 0.1.3 --
--  v 0.1.4 --
--  v 0.1.5 --