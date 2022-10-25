<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * catcafe implementation : © Julien Coignet <breddabasse@hotmail.com>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * catcafe game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/

$this->gameConstants = array(
  "SQUARE_WIDTH" => 61,
  "SQUARE_HEIGHT" => 52,
  "NB_LINES" => 6,
  "NB_COLUMNS" => 5,
  "X_ORIGIN" => 40,
  "Y_ORIGIN" => 313,
  "Y_OFFSET" => 27,

  "SCORING_COLUMN_WIDTH" => 25,
  "SCORING_COLUMN_HEIGHT" => 25,
  "SCORING_COLUMN_X_ORIGIN" => 50,
  "SCORING_COLUMN_Y_ORIGIN" => 68,
  "SCORING_COLUMN_X_OFFSET" => 60.5,

  "SHAPE_SELECTION_WIDTH" => 55,
  "SHAPE_SELECTION_HEIGHT" => 30,
  "SHAPE_SELECTION_X_ORIGIN" => 24,
  "SHAPE_SELECTION_Y_ORIGIN" => 556,
  "SHAPE_SELECTION_X_OFFSET" => 8,

  "CAT_FOOTPRINT_WIDTH" => 28,
  "CAT_FOOTPRINT_HEIGHT" => 28,
  "CAT_FOOTPRINT_X_ORIGIN" => 352,
  "CAT_FOOTPRINT_Y_ORIGIN" => 52,
  "CAT_FOOTPRINT_X_OFFSET" => 14,
  "CAT_FOOTPRINT_Y_OFFSET" => 9,

  "CAT_SELECTION_WIDTH" => 42,
  "CAT_SELECTION_HEIGHT" => 82,
  "CAT_SELECTION_X_ORIGIN" => 28,
  "CAT_SELECTION_Y_ORIGIN" => 418,
  "CAT_SELECTION_X_OFFSET" => 21,

  "SUB_SCORING_WIDTH" => 25,
  "SUB_SCORING_HEIGHT" => 20,
  "SUB_SCORING_X_ORIGIN" => 36,
  "SUB_SCORING_Y_ORIGIN" => 504,
  "SUB_SCORING_X_OFFSET" => 38,

  "FINAL_SCORING_WIDTH" => 24,
  "FINAL_SCORING_HEIGHT" => 21,
  "FINAL_SCORING_X_ORIGIN" => 27,
  "FINAL_SCORING_Y_ORIGIN" => 646,
  "FINAL_SCORING_X_OFFSET" => 21,

  "FINAL_SCORING_TOTAL_X_ORIGIN" => 340,
  "FINAL_SCORING_TOTAL_Y_ORIGIN" => 646,

  "COL_FLOORS_NUMBER" => array(4, 6, 5, 6, 3),
  "COL_SUB_SCORING_COL_MAX" => array(6, 9, 7, 8, 3),
  "COL_SUB_SCORING_COL_MIN" => array(4, 5, 3, 4, 2),

  "SHAPE_CAT_HOUSE" => 1,
  "SHAPE_BALL_OF_YARN" => 2,
  "SHAPE_BUTTERFLY_TOY" => 3,
  "SHAPE_FOOD_BOWL" => 4,
  "SHAPE_CUSHION" => 5,
  "SHAPE_MOUSE_TOY" => 6,

  "CONNECTED_MICE_POINTS" => array(2, 6, 12, 20),

  "FOOTPRINTS_TOTAL" => 18,
  "FOOTPRINTS_GAINED_PASSING" => 3,
  "FOOTPRINTS_GAINED_BUTTERFLY" => 2
);



