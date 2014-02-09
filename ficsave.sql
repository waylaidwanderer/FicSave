CREATE TABLE IF NOT EXISTS `ficsave` (
  `id` varchar(13) COLLATE utf8_unicode_ci NOT NULL,
  `chapter` tinyint(4) NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;