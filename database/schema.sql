CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `auth_code` varchar(64) DEFAULT NULL,
  `auth_code_exp` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `subscriptions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) DEFAULT NULL,
  `hub` varchar(255) DEFAULT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `lease_seconds` int(11) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `date_expires` datetime DEFAULT NULL,
  `pending` tinyint(4) NOT NULL DEFAULT '0',
  `date_subscription_requested` datetime DEFAULT NULL,
  `date_subscription_confirmed` datetime DEFAULT NULL,
  `subscription_response_code` int(11) DEFAULT NULL,
  `subscription_response_body` text,
  `notification_content_type` varchar(255) DEFAULT NULL,
  `notification_content` blob,
  `date_last_notification` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
