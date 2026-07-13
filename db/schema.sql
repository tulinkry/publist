-- Adminer 4.8.1 MySQL 10.5.29-MariaDB-ubu2004-log dump
-- Captured from production 2026-07-10. This is the first schema file ever
-- committed for this project; previously it existed only implicitly via
-- Doctrine's orm:schema-tool:create against the entity annotations.

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

DROP TABLE IF EXISTS `beers`;
CREATE TABLE `beers` (
  `beer_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `degree` int(11) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`beer_id`),
  UNIQUE KEY `beer_unique` (`name`,`degree`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `pubs`;
CREATE TABLE `pubs` (
  `pub_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `mark` double DEFAULT NULL,
  `markVoted` int(11) NOT NULL,
  `beerMark` double DEFAULT NULL,
  `beerMarkVoted` int(11) NOT NULL,
  `beerPriceVoted` int(11) NOT NULL,
  `wineMark` double DEFAULT NULL,
  `wineMarkVoted` int(11) NOT NULL,
  `winePrice` double DEFAULT NULL,
  `winePriceVoted` int(11) NOT NULL,
  `foodMark` double DEFAULT NULL,
  `foodMarkVoted` int(11) NOT NULL,
  `foodPrice` double DEFAULT NULL,
  `foodPriceVoted` int(11) NOT NULL,
  `toaletsMark` double DEFAULT NULL,
  `interierMark` double DEFAULT NULL,
  `exterierMark` double DEFAULT NULL,
  `serviceMark` double DEFAULT NULL,
  `overallMark` double DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  `inserted` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `updated` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `type` varchar(255) DEFAULT NULL,
  `long_name` longblob NOT NULL,
  `opening_hours` longblob DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `whole_name` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`pub_id`),
  KEY `IDX_8686FC98A76ED395` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `pub_descriptions`;
CREATE TABLE `pub_descriptions` (
  `description_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `version` int(11) NOT NULL,
  `text` longblob DEFAULT NULL,
  `pub_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`description_id`),
  UNIQUE KEY `description_unique` (`description_id`,`version`),
  KEY `IDX_F7F45D72A76ED395` (`user_id`),
  KEY `IDX_F7F45D7283FDE077` (`pub_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `ratings`;
CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL AUTO_INCREMENT,
  `pub_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `wine_criteria` double DEFAULT NULL,
  `wine_price` double DEFAULT NULL,
  `food_criteria` double DEFAULT NULL,
  `food_price_criteria` double DEFAULT NULL,
  `toalets_criteria` double DEFAULT NULL,
  `service_criteria` double DEFAULT NULL,
  `overall_criteria` double DEFAULT NULL,
  `interier_criteria` double DEFAULT NULL,
  `exterier_criteria` double DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `garden` tinyint(1) DEFAULT NULL,
  `calculated` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`rating_id`),
  KEY `IDX_CEB607C983FDE077` (`pub_id`),
  KEY `IDX_CEB607C9A76ED395` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `rating_beer`;
CREATE TABLE `rating_beer` (
  `beer_id` int(11) NOT NULL,
  `rating_id` int(11) NOT NULL,
  `beer_criteria` double DEFAULT NULL,
  `beer_price` double DEFAULT NULL,
  PRIMARY KEY (`beer_id`,`rating_id`),
  KEY `IDX_9C8EF528D0989053` (`beer_id`),
  KEY `IDX_9C8EF528A32EFC6` (`rating_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `right` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `click` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `skin` int(11) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `registration` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `state` int(11) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `UNIQ_1483A5E9F85E0677` (`username`),
  UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
