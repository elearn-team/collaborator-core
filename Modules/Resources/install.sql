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


DROP TABLE IF EXISTS `els_resource_categories`;
CREATE TABLE IF NOT EXISTS `els_resource_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `lft` int(10) NOT NULL,
  `rgt` int(10) NOT NULL,
  `depth` int(10) unsigned NOT NULL,
  `is_hidden` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_published` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_els_resource_categories_cms_sites` (`site_id`),
  CONSTRAINT `FK_els_resource_categories_cms_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `els_resource_pages`;
CREATE TABLE IF NOT EXISTS `els_resource_pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `body` mediumtext COLLATE utf8_unicode_ci,
  `url` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT 'default.html',
  `is_published` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `type` ENUM('page','file','url') NOT NULL DEFAULT 'page',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_els_resource_pages_cms_users` (`user_id`),
  KEY `FK_els_resource_pages_cms_sites` (`site_id`),
  KEY `FK_els_resource_pages_els_resource_categories` (`category_id`),
  CONSTRAINT `FK_els_resource_pages_cms_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_els_resource_pages_cms_users` FOREIGN KEY (`user_id`) REFERENCES `cms_users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `FK_els_resource_pages_els_resource_categories` FOREIGN KEY (`category_id`) REFERENCES `els_resource_categories` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `els_resource_files`;
CREATE TABLE IF NOT EXISTS `els_resource_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned DEFAULT NULL,
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
  KEY `FK_els_resource_files_els_resource_pages` (`page_id`),
  CONSTRAINT `FK_els_resource_files_els_resource_pages` FOREIGN KEY (`page_id`) REFERENCES `els_resource_pages` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `els_resource_videos`;
CREATE TABLE `els_resource_videos` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `url` VARCHAR(255) NULL DEFAULT NULL,
  `sort_order` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `FK_els_resource_videos_els_resource_pages` (`page_id`),
  CONSTRAINT `FK_els_resource_videos_els_resource_pages` FOREIGN KEY (`page_id`) REFERENCES `els_resource_pages` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;

DROP TABLE IF EXISTS `els_resource_tags`;
CREATE TABLE IF NOT EXISTS `els_resource_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(50) DEFAULT NULL,
  `url` varchar(50) DEFAULT NULL,
  `is_published` tinyint(4) DEFAULT '1',
  `quantity` int(11) DEFAULT '0',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_els_resource_tags_cms_sites` (`site_id`),
  CONSTRAINT `FK_els_resource_tags_cms_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `els_resource_pages_ref_tags`;
CREATE TABLE IF NOT EXISTS `els_resource_pages_ref_tags` (
  `tag_id` int(10) unsigned NOT NULL,
  `page_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`tag_id`,`page_id`),
  KEY `FK__els_resource_pages` (`page_id`),
  CONSTRAINT `FK__els_resource_pages` FOREIGN KEY (`page_id`) REFERENCES `els_resource_pages` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK__els_resource_tags` FOREIGN KEY (`tag_id`) REFERENCES `els_resource_tags` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

INSERT INTO `cms_permissions` (`id`, `description`) VALUES ('pages.can_manage_pages', 'Пользователь может управлять инфо ресурсами');

ALTER TABLE `els_resource_files`
	ADD COLUMN `extension` VARCHAR(5) NULL DEFAULT NULL AFTER `url`;

ALTER TABLE `els_resource_files`
	ADD COLUMN `width` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `size`,
	ADD COLUMN `height` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `width`;

ALTER TABLE `els_resource_pages`
  ADD COLUMN `description` VARCHAR(255) NULL DEFAULT NULL AFTER `code`;

ALTER TABLE `els_resource_pages`
  CHANGE COLUMN `type` `type` ENUM('page','file','url','html') NOT NULL DEFAULT 'page' AFTER `is_published`;

ALTER TABLE `els_resource_files`
  ADD COLUMN `index_page` VARCHAR(255) NULL DEFAULT NULL AFTER `extension`;


-- v 0.2 --
ALTER TABLE `els_resource_pages`
	ADD COLUMN `open_in_window` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `is_published`;

-- v 0.1.2 --
-- v 0.1.3 --
--  v 0.1.4 --
--  v 0.1.5 --