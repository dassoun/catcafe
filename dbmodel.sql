
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- catcafe implementation : © Julien Coignet <breddabasse@hotmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `player` ADD `is_first_player` boolean NOT NULL DEFAULT false;
ALTER TABLE `player` ADD `has_passed` boolean NOT NULL DEFAULT false;
ALTER TABLE `player` ADD `footprint_available` tinyint(2) UNSIGNED NOT NULL DEFAULT '1';
ALTER TABLE `player` ADD `footprint_used` tinyint(2) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `footprint_required_tmp` tinyint(2) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `first_chosen_dice_num` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `first_chosen_dice_val` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `first_chosen_played_order` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `second_chosen_dice_num` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `second_chosen_dice_val` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `second_chosen_played_order` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `location_chosen` varchar(5) DEFAULT NULL;
ALTER TABLE `player` ADD `score_cat_1` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `score_cat_2` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `score_cat_3` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `score_cat_4` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `score_cat_5` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `score_cat_6` tinyint(2) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `score_col_1` tinyint(2) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `score_col_2` tinyint(2) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `score_col_3` tinyint(2) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `score_col_4` tinyint(2) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `score_col_5` tinyint(2) UNSIGNED NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `drawing` (
   `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
   `player_id` int(10) UNSIGNED NOT NULL,
   `coord_x` tinyint(2) UNSIGNED NOT NULL,
   `coord_y` tinyint(2) UNSIGNED NOT NULL,
   `state` tinyint(2) NOT NULL DEFAULT 0,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `dice` (
   `id` tinyint(1) UNSIGNED NOT NULL,
   `dice_value` tinyint(1) UNSIGNED NOT NULL,  
   `player_id` int(10) UNSIGNED DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;