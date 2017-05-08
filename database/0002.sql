CREATE TABLE `hubs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `token` varchar(20) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `publisher` varchar(20) DEFAULT NULL,
  `secret` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
