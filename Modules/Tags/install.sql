-- v 0.2 --

set FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `els_tags`;
CREATE TABLE `els_tags` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` INT(10) UNSIGNED NOT NULL,
  `body` VARCHAR(255) NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `els_tags_ref_sites` (`site_id`),
  CONSTRAINT `els_tags_ref_sites` FOREIGN KEY (`site_id`) REFERENCES `cms_sites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;

DROP TABLE IF EXISTS `els_tags_ref_elements`;
CREATE TABLE `els_tags_ref_elements` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`tag_id` INT(10) UNSIGNED NOT NULL,
	`element_id` INT(10) UNSIGNED NOT NULL,
	`type` VARCHAR(255) NOT NULL,
	`created_at` DATETIME NULL DEFAULT NULL,
	`updated_at` DATETIME NULL DEFAULT NULL,
	`created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	`updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `tag_id_element_id_type` (`tag_id`, `element_id`, `type`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;

set FOREIGN_KEY_CHECKS=1;

-- v 0.1.2 --
ALTER TABLE `els_tags_ref_elements`
	ADD CONSTRAINT `FK_els_ref_tag` FOREIGN KEY (`tag_id`) REFERENCES `els_tags` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;

--  v 0.1.3 --
--  v 0.1.4 --
--  v 0.1.5 --