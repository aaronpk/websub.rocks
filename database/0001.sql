CREATE TABLE `publishers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `input_url` varchar(255) DEFAULT NULL,
  `content_type` varchar(255) DEFAULT NULL,
  `hub_url` varchar(255) DEFAULT NULL,
  `self_url` varchar(255) DEFAULT NULL,
  `hub_source` varchar(255) DEFAULT NULL,
  `self_source` varchar(255) DEFAULT NULL,
  `http_links` text,
  `body_links` text,
  `hostmeta_links` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
