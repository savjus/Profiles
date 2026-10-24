-- Run once on an existing vartvalds database to add the social network schema.
ALTER TABLE `users`
  ADD COLUMN `first_name` varchar(80) NOT NULL DEFAULT '',
  ADD COLUMN `last_name` varchar(80) NOT NULL DEFAULT '',
  ADD COLUMN `bio` varchar(1000) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS `social_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `content` text NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `social_posts_created` (`created_at`),
  KEY `social_posts_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `social_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `username` varchar(30) NOT NULL,
  `content` text NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `social_comments_post` (`post_id`),
  KEY `social_comments_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `social_reactions` (
  `post_id` int(10) unsigned NOT NULL,
  `username` varchar(30) NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`post_id`, `username`),
  KEY `social_reactions_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `social_follows` (
  `follower` varchar(30) NOT NULL,
  `followed` varchar(30) NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`follower`, `followed`),
  KEY `social_follows_followed` (`followed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `social_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender` varchar(30) NOT NULL,
  `recipient` varchar(30) NOT NULL,
  `content` text NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `social_messages_sender` (`sender`),
  KEY `social_messages_recipient` (`recipient`),
  KEY `social_messages_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
