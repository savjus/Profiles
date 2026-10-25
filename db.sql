-- phpMyAdmin SQL Dump
-- version 3.3.7deb7
-- http://www.phpmyadmin.net
--
-- Darbinė stotis: localhost
-- Atlikimo laikas:  2012 m. Kovo 01 d.  00:21
-- Serverio versija: 5.1.49
-- PHP versija: 5.3.3-7+squeeze8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Sukurta duomenų struktūra lentelei `active_guests`
--

CREATE TABLE IF NOT EXISTS `active_guests` (
  `ip` varchar(45) NOT NULL,
  `timestamp` int(11) unsigned NOT NULL,
  PRIMARY KEY (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Sukurta duomenų kopija lentelei `active_guests`
--


-- --------------------------------------------------------

--
-- Sukurta duomenų struktūra lentelei `active_users`
--

CREATE TABLE IF NOT EXISTS `active_users` (
  `username` varchar(30) NOT NULL,
  `timestamp` int(11) unsigned NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Sukurta duomenų kopija lentelei `active_users`
--


-- --------------------------------------------------------

--
-- Sukurta duomenų struktūra lentelei `banned_users`
--

CREATE TABLE IF NOT EXISTS `banned_users` (
  `username` varchar(30) NOT NULL,
  `timestamp` int(11) unsigned NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Sukurta duomenų kopija lentelei `banned_users`
--


-- --------------------------------------------------------

--
-- Sukurta duomenų struktūra lentelei `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `username` varchar(30) NOT NULL,
  `password` varchar(32) DEFAULT NULL,
  `userid` varchar(32) DEFAULT NULL,
  `userlevel` tinyint(1) unsigned NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `timestamp` int(11) unsigned NOT NULL,
  `first_name` varchar(80) NOT NULL DEFAULT '',
  `last_name` varchar(80) NOT NULL DEFAULT '',
  `bio` varchar(1000) NOT NULL DEFAULT '',
  PRIMARY KEY (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Sukurta duomenų kopija lentelei `users`
--

INSERT IGNORE INTO `users` (`username`, `password`, `userid`, `userlevel`, `email`, `timestamp`) VALUES
('Valdytojas', 'fe01ce2a7fbac8fafaed7c982a04e229', '7ed2b87b255a0348b61226bd7c2ed5b4', 5, 'demo@ktu.lt', 1330553708),
('Administratorius', 'fe01ce2a7fbac8fafaed7c982a04e229', 'a2fe399900de341c39c632244eaf8483', 9, 'demo@ktu.lt', 1330553956),
('Vartotojas', 'fe01ce2a7fbac8fafaed7c982a04e229', '9a47f4552955b91bcd8850d73b00e703', 1, 'demo@ktu.lt', 1330553730);

-- Social network data. Existing installations can run social_migration.sql once.
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
