ALTER TABLE `subscriber_hub`
  ADD COLUMN `user_id` int(11) unsigned DEFAULT NULL,
  ADD KEY `user_id` (`user_id`);
