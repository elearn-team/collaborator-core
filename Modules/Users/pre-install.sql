DROP TABLE IF EXISTS `cms_permissions`;
CREATE TABLE IF NOT EXISTS `cms_permissions` (
  `id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;