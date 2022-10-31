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
  * catcafe.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

require_once( "modules/CTCCoord.php" );
require_once( "modules/CTCPossibleDrawing.php" );
require_once( "modules/CTCSquare.php" );
require_once( "modules/CTCPossibleCoord.php" );

class catcafe extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );        
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "catcafe";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // initStat( "player", "turns_number", 0 );
        self::initStat( "player", "cat_house", 0 );
        self::initStat( "player", "ball_of_yarn", 0 );
        self::initStat( "player", "butterfly_toy", 0 );
        self::initStat( "player", "food_bowl", 0 );
        self::initStat( "player", "cushion", 0 );
        self::initStat( "player", "mouse_toy", 0 );
        self::initStat( "player", "columns", 0 );
        // self::initStat( "player", "cat_footprints", 0 );

        // TODO: setup the initial game situation here

        $player_order = self::getNextPlayerTable();
        $sql = "UPDATE player SET is_first_player = true WHERE player_id = ".$player_order[0];
        self::DbQuery( $sql );

        $squares_positions = $this->getSquaresPositions();
        $sql = "INSERT INTO drawing (player_id, coord_x, coord_y) VALUES ";
        $values = array();

        foreach( $players as $player_id => $player ){
            foreach( $squares_positions as $pos ) {
                $values[] = "('".$player_id."','".$pos->x."','".$pos->y."')";
            }
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );

        $this->rollDices();

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        // Constants
        $result['constants'] = $this->gameConstants;

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, is_first_player, has_passed, 
                    footprint_available, footprint_used,
                    first_chosen_dice_num, first_chosen_dice_val, first_chosen_played_order,
                    second_chosen_dice_num, second_chosen_dice_val, second_chosen_played_order, 
                    location_chosen, 
                    score_cat_1, score_cat_2, score_cat_3, score_cat_4, score_cat_5, score_cat_6,
                    score_col_1, score_col_2, score_col_3, score_col_4, score_col_5
                FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        // $result['squarespositions'] = $this->getSquaresPositions();
        
        $sql = "SELECT player_id, coord_x, coord_y, state FROM drawing ORDER BY player_id, coord_x, coord_y";
        $player_grid = self::getObjectListFromDB($sql);

        foreach ($player_grid as $square) {
            $result['drawings'][$square['player_id']][] = new CTCSquare($square['coord_x'], $square['coord_y'], $square['state']);
        }

        $sql = "SELECT id, dice_value, player_id FROM dice ORDER BY id ASC";
        $result['dices'] = self::getCollectionFromDb( $sql );

        $column_score_state = $this->getColumnScoreState();
        $result['column_score_state'] = $column_score_state;

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $game_progression = 0;
        
        $sql = "SELECT DISTINCT player_id FROM player ORDER BY player_id ASC";
        $players = self::getCollectionFromDb( $sql );

        foreach ( $players as $player_id => $player ) {
            $sql = "SELECT coord_x, count(*) as nb FROM drawing WHERE player_id = $player_id AND state != 0 group by coord_x";
            $column_progresses = self::getCollectionFromDb( $sql );

            $tmp_progresses = array(0, 0, 0, 0, 0);
            for ( $i=0; $i<5; $i++ ) {
                if ( isset($column_progresses[$i]['nb']) ) {
                    $tmp = round(($column_progresses[$i]['nb'] / $this->gameConstants["COL_FLOORS_NUMBER"][$i]) * 100);
                    $tmp_progresses[$i] = $tmp;
                }
            }

            arsort($tmp_progresses);

            $tmp = round((($tmp_progresses[0] * 33) / 100) + (($tmp_progresses[1] * 33) / 100) + (($tmp_progresses[2] * 33) / 100));
            
            if ( $tmp > $game_progression ) {
                $game_progression = $tmp;
            }
        }

        return $game_progression;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */
    // Get the different squares postions

    function getSquaresPositions() 
    {
        $res = array();

        $constants = $this->gameConstants;

        for ($i=0; $i<$constants["NB_COLUMNS"]; $i++) {
            for ($j=0; $j<$constants["NB_LINES"]; $j++) {
                if (($i == 0 && ($j != 3 && $j != 5)) 
                    || ($i == 1)
                    || ($i == 2 && ($j != 2))
                    || ($i == 3)
                    || ($i == 4 && ($j != 1 && $j < 4))) {

                        $res[] = new CTCCoord($i, $j);
                }
            }
        }
        
        return $res;
    }

    // Get the complete board of both players with a double associative array
    function getPlayerBoards()
    {
        $sql = "SELECT DISTINCT player_id FROM drawing ORDER BY player_id ASC";
        $players = self::getCollectionFromDb( $sql );

        $result = array();
        foreach ($players as $player_id => $player) {
            $result[$player_id] =  self::getDoubleKeyCollectionFromDB( "SELECT coord_x x, coord_y y, state value
                                                                    FROM drawing WHERE player_id = $player_id", 
                                                                    true );
        }
        // var_dump($result);

        return $result;
    }

    // Get the complete board of 1 player with a double associative array
    function getBoardByPlayer($player_id)
    {
        $result = array();
        $result = self::getDoubleKeyCollectionFromDB( "SELECT coord_x x, coord_y y, state value
                                                                    FROM drawing WHERE player_id = $player_id", 
                                                                    true );
        // var_dump($result);

        return $result;
    }

    // Get the list of possible moves (x => y => true)
    function getPossibleDrawings( $player_id, $dice_1, $dice_2 )
    {
        $result = array();
        
        $board = self::getBoardByPlayer($player_id);

        // for each 5 columns
        for ($i=0; $i<5; $i++) {
            if (isset($board[$i][$dice_1-1])) {
                if ($board[$i][$dice_1-1] == 0) {
                    $result[$player_id][] = new CTCPossibleDrawing($i, $dice_1-1, $dice_2);
                }
            }

            if (isset($board[$i][$dice_2-1])) {
                if ($board[$i][$dice_2-1] == 0) {
                    $result[$player_id][] = new CTCPossibleDrawing($i, $dice_2-1, $dice_1);
                }
            }
        }

        return $result;
    }

    // Get the list of possible moves with the selected dice
    function getPossibleLocationsWithOneDice( $player_id, $dice )
    {
        $result = array();
        
        $board = self::getBoardByPlayer($player_id);

        $sql = "SELECT footprint_available FROM player WHERE player_id = '$player_id' ";
        $footprint_available = self::getUniqueValueFromDB( $sql );

        $min_line = 0;
        $min_line = max($min_line, $dice - $footprint_available);
        $max_line = 6;
        $max_line = min($max_line, $dice + $footprint_available);

        // for each 5 columns
        for ($i=0; $i<5; $i++) {
            for ($j=$min_line - 1; $j<$max_line; $j++) {
                if (isset($board[$i][$j])) {
                    if ($board[$i][$j] == 0) {
                        $cost = abs($dice - ($j+1));
                        $result[$player_id][] = new CTCPossibleCoord($i, $j, $cost);
                    }
                }
            }
        }

        return $result;
    }

    function getCatHouseScore($player_id, $cat) {

        $result = array();

        $sql = "SELECT coord_x, coord_y, state FROM drawing WHERE player_id = '$player_id' ORDER BY coord_x, coord_y";
        $player_grid = self::getDoubleKeyCollectionFromDB( $sql, true );

        // self::dump( "grille", $player_grid );

        $nb_shape = 0;

        foreach($player_grid as $key => $value) {
            foreach($value as $key2 => $value2) {
                if ($value2 == $cat) {
                    $nb_shape += 1;
                }
            }
        }

        // self::dump( "getCatHouseScore : ", $nb_shape * 2 );
        return ($nb_shape * 2);
    }

    function getCatHouseScoreTotal($player_id) {

        $sql = "SELECT (COALESCE(score_cat_1, 0) + COALESCE(score_cat_2, 0) + COALESCE(score_cat_3, 0) + COALESCE(score_cat_4, 0) + COALESCE(score_cat_5, 0) + COALESCE(score_cat_6, 0)) FROM player WHERE player_id = '$player_id'";
        $cat_house_score = self::getUniqueValueFromDB( $sql );

        // self::dump( "getCatHouseScoreTotal : ", $cat_house_score );
        return ($cat_house_score);
    }

    function getBallOfYarnScore($player_id) {
        
        // self::debug( "+++++++++++++++++ getBallOfYarnScore" );

        $sql = "SELECT player_id id FROM player";
        $players = self::getObjectListFromDB( $sql );

        // self::dump( "players : ", $players );

        $ball_of_yarn = array();
        $max = array(0, 0, 0, 0, 0);

        foreach ($players as $key => $player_info) {

            $sql = "SELECT coord_x, coord_y, state FROM drawing WHERE player_id = '".$player_info["id"]."' ORDER BY coord_x, coord_y";
            $player_grid = self::getDoubleKeyCollectionFromDB( $sql, true );

            // self::dump( "value[\"id\"] : ", $player_info["id"] );

            $ball_of_yarn[$player_info["id"]] = array(0, 0, 0, 0, 0);

            // self::debug( "--------------------------------------------" );
            // self::dump( "player_grid : ", $player_grid );

            foreach($player_grid as $x => $value) {
                $count = 0;
                
                foreach($value as $y => $value2) {
                    if ($value2 == $this->gameConstants["SHAPE_BALL_OF_YARN"]) {

                        // self::debug( "x : ".$x.", y : ".$y." / value2 : ".$value2 );

                        $count++;
                    }
                }
                // self::debug( "--------------------------------------------" );
                // self::dump( "player_grid : ", $player_grid );
                // self::dump( "value : ", $player_info );
                // self::dump( "value[\"id\"] : ", $player_info["id"] );
                // self::debug( "x = /" . $x . "/" );
                // self::dump( "ball_of_yarn : ", $ball_of_yarn );
                
                $ball_of_yarn[$player_info["id"]][$x] += $count;

                if ($count > $max[$x]) {
                    $max[$x] = $count;
                }
            }
        }

        // self::dump( "ball_of_yarn : ", $ball_of_yarn );
        // self::dump( "max : ", $max );

        $res = 0;

        for ($i=0; $i<5; $i++) {
            if ($ball_of_yarn[$player_id][$i] > 0) {
                if ($ball_of_yarn[$player_id][$i] == $max[$i]) {
                    $res += 8;
                } else {
                    $res += 3;
                }
            }
        }

        // self::dump( "res : ", $res );

        return $res;
    }

    function getButterflyToyScore($player_id) {

        $sql = "SELECT coord_x, coord_y, state FROM drawing WHERE player_id = '$player_id' ORDER BY coord_x, coord_y";
        $player_grid = self::getDoubleKeyCollectionFromDB( $sql, true );

        $nb_butterfly_toy = 0;

        foreach($player_grid as $key => $value) {
            foreach($value as $key2 => $value2) {
                if ($value2 == $this->gameConstants["SHAPE_BUTTERFLY_TOY"]) {
                    $nb_butterfly_toy += 1;
                }
            }
        }

        // self::dump( "getButterflyToyScore : ", $nb_butterfly_toy * 3 );

        return ($nb_butterfly_toy * 3);
    }

    function getFoodBowlScore($player_id) {

        $sql = "SELECT coord_x, coord_y, state FROM drawing WHERE player_id = '$player_id' ORDER BY coord_x, coord_y";
        $player_grid = self::getDoubleKeyCollectionFromDB( $sql, true );

        $food_bowl_score = 0;

        // self::debug( "+++++++++++++++++++++++++++++++ getFoodBowlScore" );
        // self::dump( "player_grid", $player_grid );

        foreach($player_grid as $x => $value) {
            foreach($value as $y => $value2) {
                if ($value2 == $this->gameConstants["SHAPE_FOOD_BOWL"]) {
                    $food_bowl_array = array(0, 0, 0, 0, 0, 0);

                    $nb_shape = 0;

                    // column x
                    if (array_key_exists(($y-1), $player_grid[$x])) {
                        $shape = $player_grid[$x][$y-1];
                        if ($shape > 0) {
                            $food_bowl_array[$shape - 1] = 1;
                        }
                    }
                    if (array_key_exists(($y+1), $player_grid[$x])) {
                        $shape = $player_grid[$x][$y+1];
                        if ($shape > 0) {
                            $food_bowl_array[$shape - 1] = 1;
                        }
                    }

                    if (($x % 2) == 0) {
                        // column x-1
                        for ($i=0; $i<2; $i++) {
                            if (array_key_exists($x-1, $player_grid)) {
                                if (array_key_exists(($y+$i), $player_grid[$x-1])) {
                                    $shape = $player_grid[$x-1][$y+$i];
                                    if ($shape > 0) {
                                        $food_bowl_array[$shape - 1] = 1;
                                    }
                                }
                            }
                        }
                        // column x+1
                        for ($i=0; $i<2; $i++) {
                            if (array_key_exists($x+1, $player_grid)) {
                                if (array_key_exists(($y+$i), $player_grid[$x+1])) {
                                    $shape = $player_grid[$x+1][$y+$i];
                                    if ($shape > 0) {
                                        $food_bowl_array[$shape - 1] = 1;
                                    }
                                }
                            }
                        }
                    } else {
                        // column x-1
                        for ($i=-1; $i<1; $i++) {
                            if (array_key_exists($x-1, $player_grid)) {
                                if (array_key_exists(($y+$i), $player_grid[$x-1])) {
                                    $shape = $player_grid[$x-1][$y+$i];
                                    if ($shape > 0) {
                                        $food_bowl_array[$shape - 1] = 1;
                                    }
                                    
                                }
                            }
                        }
                        // column x+1
                        for ($i=-1; $i<1; $i++) {
                            if (array_key_exists($x+1, $player_grid)) {
                                if (array_key_exists(($y+$i), $player_grid[$x+1])) {
                                    $shape = $player_grid[$x+1][$y+$i];
                                    if ($shape > 0) {
                                        $food_bowl_array[$shape - 1] = 1;
                                    }
                                }
                            }
                        }
                    }

                    foreach($food_bowl_array as $value) {
                        $food_bowl_score += $value;
                    }

                    // self::dump( "food_bowl_array", $food_bowl_array );
                }
            }
        }

        return $food_bowl_score;
    }

    function getCushionScore($player_id) {

        $sql = "SELECT coord_x, coord_y, state FROM drawing WHERE player_id = '$player_id' ORDER BY coord_x, coord_y";
        $player_grid = self::getDoubleKeyCollectionFromDB( $sql, true );

        // self::dump( "grille", $player_grid );

        $cushion_score = 0;

        foreach($player_grid as $x => $value) {
            foreach($value as $y => $value2) {
                if ($value2 == $this->gameConstants["SHAPE_CUSHION"]) {
                    $cushion_score += ($y + 1);
                }
            }
        }

        // self::dump( "cushion_score", $cushion_score );
        return ($cushion_score);
    }

    function getMouseToyScore($player_id) {
        
        $sql = "SELECT coord_x, coord_y, state FROM drawing WHERE player_id = '$player_id' ORDER BY coord_x, coord_y";
        $player_grid = self::getDoubleKeyCollectionFromDB( $sql, true );

        // self::dump( "grille", $player_grid );

        $mice_score_calculation_tmp = array();
        for ($i=0; $i<5; $i++) {
            for ($j=0; $j<6; $j++) {
                $mice_score_calculation_tmp[$i][$j] = "to_be_done";
            }
        }

        // self::dump( "grille json : ", json_encode($mice_score_calculation_tmp));

        $connected_mice_score = array();

        for ($x=0; $x<5; $x++) {
            for ($y=0; $y<6; $y++) {
                if (array_key_exists(($y), $player_grid[$x])) {
                    if ($player_grid[$x][$y] == $this->gameConstants["SHAPE_MOUSE_TOY"]) {
                        $connected_mice = 0;
                        $connected_mice = $this->getConnectedMice($player_grid, $x, $y, $mice_score_calculation_tmp);
                        // self::dump( "connected mice score : ", json_encode($connected_mice));
                        if ($connected_mice > 0) {
                            $connected_mice_score[] = $connected_mice;
                        }
                    } else {
                        $mice_score_calculation_tmp[$x][$y] = "done";
                    }
                } else {
                    // self::dump( "key not exists : x = $x, y = $y", "+++++++++++++++++++++++++++");
                }
            }
        }

        // self::dump( "mice tmp : ", json_encode($mice_score_calculation_tmp));

        // self::dump( "mice score : ", json_encode($connected_mice_score));


        $mice_score = 0;

        foreach($connected_mice_score as $nb_mice) {
            $mod_chain_of_4 = floor($nb_mice / 4);
            $rest = $nb_mice % 4;
            
            $mice_score += $this->gameConstants["CONNECTED_MICE_POINTS"][3] * $mod_chain_of_4;
            if ($rest > 0) {
                $mice_score += $this->gameConstants["CONNECTED_MICE_POINTS"][$rest - 1];
            }
        }

        // self::dump( "final mice score : ", json_encode($mice_score));
        return $mice_score;
    }

    function getConnectedMice(&$grid, $x, $y, &$mice_score_calculation_tmp) {

        $score = 0;
        // self::dump( "getConnectedMice : x = $x, y = $y", "***");

        if (!isset($grid[$x][$y])) {
            return 0;
        }

        if ($mice_score_calculation_tmp[$x][$y] == "done") {
            return 0;
        } else {
            $mice_score_calculation_tmp[$x][$y] = "done";
        }

        if ($grid[$x][$y] == $this->gameConstants["SHAPE_MOUSE_TOY"]) {
            $score = 1;

            // column x
            $score += $this->getConnectedMice($grid, $x, $y-1, $mice_score_calculation_tmp);
            $score += $this->getConnectedMice($grid, $x, $y+1, $mice_score_calculation_tmp);

            if (($x % 2) == 0) {
                // column x-1
                for ($i=0; $i<2; $i++) {
                    $score += $this->getConnectedMice($grid, $x-1, $y+$i, $mice_score_calculation_tmp);
                }
                // column x+1
                for ($i=0; $i<2; $i++) {
                    $score += $this->getConnectedMice($grid, $x+1, $y+$i, $mice_score_calculation_tmp);
                }
            } else {
                // column x-1
                for ($i=-1; $i<1; $i++) {
                    $score += $this->getConnectedMice($grid, $x-1, $y+$i, $mice_score_calculation_tmp);
                }
                // column x+1
                for ($i=-1; $i<1; $i++) {
                    $score += $this->getConnectedMice($grid, $x+1, $y+$i, $mice_score_calculation_tmp);
                }
            }
        }

        return $score;
    }

    function getColumnsScore($player_id) {
        $sql = "SELECT (score_col_1 + score_col_2 + score_col_3 + score_col_4 + score_col_5) FROM player WHERE player_id = '".$player_id."'";
        $res = self::getUniqueValueFromDB( $sql );

        return $res;
    }
    
    function getCatFootprintsScore($player_id) {
        $sql = "SELECT footprint_available FROM player WHERE player_id = '".$player_id."'";
        $res = self::getUniqueValueFromDB( $sql );

        return $res;
    }

    function rollDices() 
    {
        $values = array();
        for ($i=0; $i<=self::getPlayersNumber(); $i++) {
            $values[] = "(".($i + 1).", ".bga_rand(1, 6).")";
        }

        $sql = "INSERT INTO dice (id, dice_value) VALUES ";
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
    }

    function getColumnScoreState() 
    {
        // Column score by player
        $sql = "SELECT player_id, score_col_1, score_col_2, score_col_3, score_col_4, score_col_5 FROM player";
        $player_score_col = self::getCollectionFromDB( $sql );

        $column_score_state = array();
        $column_score_state["global"] = array(0, 0, 0, 0, 0); // Columns already filled
        
        foreach ($player_score_col as $player_id => $score) {
            $column_score_state["players"][$player_id] = array(array(0, 0), array(0, 0), array(0, 0), array(0, 0), array(0, 0)); // 0 => empty, 1 => validated, 2 => erased

            for ( $i=0; $i<5; $i++ ) {
                if ( $score["score_col_".($i+1)] > 0 ) {
                    $column_score_state["global"][$i] = 1;

                    if ( $score["score_col_".($i+1)] == $this->gameConstants["COL_SUB_SCORING_COL_MAX"][$i] ) {
                        $column_score_state["players"][$player_id][$i][0] = 1;
                        $column_score_state["players"][$player_id][$i][1] = 2;
                    } else {
                        $column_score_state["players"][$player_id][$i][0] = 2;
                        $column_score_state["players"][$player_id][$i][1] = 1;
                    }
                }
            }
        }

        // self::dump("column_score_state / global", $column_score_state["global"]);

        foreach ( $player_score_col as $player_id => $score_col ) {
            for ( $i=0; $i<5; $i++ ) {
                if ( $column_score_state["global"][$i] == 1 ) {
                    if ( $column_score_state["players"][$player_id][$i][0] == 0 ) {
                        $column_score_state["players"][$player_id][$i][0] = 2;
                    }
                }
            }
        }

        return $column_score_state;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in catcafe.action.php)
    */

    function pickDice( $dice_id, $dice_face ) 
    {
        // Check that this is player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'pickDice' ); 
        
        $player_id = self::getActivePlayerId();

        $sql = "SELECT id FROM dice WHERE id = ".$dice_id." AND player_id is null";
        $dice = self::getObjectFromDb( $sql );

        if ($dice == null) {
            throw new BgaUserException( self::_(clienttranslate("This die is not available any more.")) );
        }

        // Update dice state
        $sql = "UPDATE dice SET player_id = '$player_id' WHERE id = '$dice_id'";
        self::DbQuery($sql);

        // NEW ====================================
        // Update player dice
        $sql = "UPDATE player SET first_chosen_dice_num = 0, first_chosen_dice_val = $dice_face WHERE player_id = '$player_id'";
        self::DbQuery($sql);

        // Notify all players
        self::notifyAllPlayers( "dicePicked", clienttranslate( '${player_name} picked a die ${ctc_log_dice}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'dice_id' => $dice_id,
            'dice_face' => $dice_face,
            'ctc_log_dice' => $dice_face
            )
        );

        // Go to next game state
        $this->gamestate->nextState( "dicePicked" );
    }

    function draw( $player_id, $x, $y, $shape ) 
    {
        // Check that this is player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'draw' ); 
        
        $player_id = self::getCurrentPlayerId();

        // Update board state
        $sql = "UPDATE drawing SET state = '$shape' WHERE player_id = '$player_id' AND coord_x = '$x' AND coord_y = '$y'";
        self::DbQuery($sql);

        // Notify all players
        self::notifyAllPlayers( "drawn", clienttranslate( '${player_name} picked a die' ), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'shape' => $shape,
            'player_id' => $player_id,
            'x' => $x,
            'y' => $y
            )
        );

        // Go to next game state
        $this->gamestate->nextState( "dicePicked" );
    }

    function pass( $player_id )
    {
        // Check that this is player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'pass' ); 

        $player_id = self::getCurrentPlayerId();

        // Update available footprints
        $sql = "UPDATE player SET has_passed = true, footprint_available = LEAST(footprint_available + ".$this->gameConstants["FOOTPRINTS_GAINED_PASSING"].", (".$this->gameConstants["FOOTPRINTS_TOTAL"]." - footprint_used)) WHERE player_id = '$player_id'";
        self::DbQuery($sql);

        $sql = "SELECT footprint_available, footprint_used FROM player WHERE player_id = '$player_id'";
        $res = self::getObjectFromDB( $sql );
        $footprint_available = $res["footprint_available"];
        $footprint_used = $res["footprint_used"];

        // Notify all players
        self::notifyAllPlayers( "passed", clienttranslate( '${player_name} passed' ), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'footprint_available' => $footprint_available,
            'footprint_used' => $footprint_used
            )
        );

        // // Go to next game state
        // $sql = "SELECT COUNT(*) AS nb FROM player WHERE has_passed = true OR (first_chosen_played_order IS NOT NULL AND second_chosen_played_order IS NOT NULL)";
        // $res = self::getObjectFromDB( $sql );
        // if ($res['nb'] < (self::getPlayersNumber())) {
        //     $this->gamestate->nextState( "passed" );
        // } else {
        //     $this->gamestate->nextState( "nextRound" );
        // }
        
        $this->gamestate->setPlayerNonMultiactive( $player_id, "" );
    }

    function chooseDiceForLocation( $player_id, $num_player_dice, $player_dice_face ) {

        // Check that this is player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'chooseDiceForLocation' ); 

        // $player_id = self::getActivePlayerId();

        // NEW ====================================
        // We check that the dice is still playable
        $sql = "SELECT first_chosen_played_order, second_chosen_played_order FROM player WHERE player_id = '$player_id'";
        $res = self::getObjectFromDB( $sql );
        if ($num_player_dice == 0) {
            if (!is_null($res['first_chosen_played_order'])) {
                throw new BgaUserException( self::_(clienttranslate("This die has already been played.")) );
            } else {
                // Update player dice
                $sql = "UPDATE player SET first_chosen_played_order = 1 WHERE player_id = '$player_id'";
                self::DbQuery($sql);
            }
        } else {
            if (!is_null($res['second_chosen_played_order'])) {
                throw new BgaUserException( self::_(clienttranslate("This die has already been played.")) );
            } else {
                // Update player dice
                $sql = "UPDATE player SET second_chosen_played_order = 1 WHERE player_id = '$player_id'";
                self::DbQuery($sql);
            }
        }

        // Notify all players
        self::notifyAllPlayers( "diceForLocationChosen", clienttranslate( '${player_name} has chosen his first die' ), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'first_chosen_dice_num' => $num_player_dice,
            'first_chosen_dice_val' => $player_dice_face,
            )
        );

        // Go to next game state
        //$this->gamestate->nextState( "diceForLocationChosen" );
        $this->gamestate->nextPrivateState($player_id, "diceForLocationChosen");
    }

    function cancelLocationDiceChoice( $player_id ) {
        // Check that this is player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'cancelLocationDiceChoice' ); 

        $player_id = self::getCurrentPlayerId();

        $sql = "UPDATE player SET first_chosen_played_order = null, second_chosen_played_order = null WHERE player_id = '$player_id'";
                self::DbQuery($sql);

        $sql = "SELECT footprint_available, footprint_used FROM player WHERE player_id = '$player_id'";
        $res = self::getObjectFromDB($sql);
        $footprint_available = $res['footprint_available'];
        $footprint_used = $res['footprint_used'];

        // Notify all players
        self::notifyAllPlayers( "backToTurnDrawingPhase1", clienttranslate( '${player_name} has cancelled his action' ), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'x' => -1,
            'y' => -1,
            'footprint_available' => $footprint_available,
            'footprint_used' => $footprint_used
            )
        );

        // Go to next game state
        $this->gamestate->nextPrivateState( $player_id, "locationDiceChoiceCancelled" );
    }

    function cancelLocationChoice( $player_id ) {
        // Check that this is player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'cancelLocationChoice' ); 

        $player_id = self::getCurrentPlayerId();

        $sql = "SELECT location_chosen FROM player WHERE player_id = '$player_id'";
        $location = self::getUniqueValueFromDB($sql);
        $locations = explode(",", $location);

        $sql = "UPDATE drawing SET state = 0 WHERE player_id = '$player_id' AND coord_x = '$locations[0]' AND coord_y = '$locations[1]'";
        self::DbQuery($sql);

        $sql = "UPDATE player SET 
                    first_chosen_played_order = null, 
                    second_chosen_played_order = null, 
                    location_chosen = null, 
                    footprint_available = footprint_available + footprint_required_tmp,
                    footprint_used = footprint_used - footprint_required_tmp,
                    footprint_required_tmp = 0 
                WHERE player_id = '$player_id'";
        self::DbQuery($sql);

        $sql = "SELECT footprint_available, footprint_used FROM player WHERE player_id = '$player_id'";
        $res = self::getObjectFromDB($sql);
        $footprint_available = $res['footprint_available'];
        $footprint_used = $res['footprint_used'];

        // Notify all players
        self::notifyAllPlayers( "backToTurnDrawingPhase1", clienttranslate( '${player_name} has cancelled his action' ), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'x' => $locations[0],
            'y' => $locations[1],
            'footprint_available' => $footprint_available,
            'footprint_used' => $footprint_used
            )
        );

        // Go to next game state
        $this->gamestate->nextPrivateState( $player_id, "locationChoiceCancelled" );
    }

    function cancelShapeChoice( $player_id ) {
        // Check that this is player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'cancelShapeChoice' ); 

        $player_id = self::getCurrentPlayerId();

        $sql = "SELECT location_chosen FROM player WHERE player_id = '$player_id'";
        $location = self::getUniqueValueFromDB($sql);
        $locations = explode(",", $location);

        $sql = "UPDATE drawing SET state = 0 WHERE player_id = '$player_id' AND coord_x = '$locations[0]' AND coord_y = '$locations[1]'";
        self::DbQuery($sql);

        $sql = "UPDATE player SET 
                    first_chosen_played_order = null, 
                    second_chosen_played_order = null, 
                    location_chosen = null, 
                    footprint_available = footprint_available + footprint_required_tmp,
                    footprint_used = footprint_used - footprint_required_tmp,
                    footprint_required_tmp = 0 
                WHERE player_id = '$player_id'";
        self::DbQuery($sql);

        $sql = "SELECT footprint_available, footprint_used FROM player WHERE player_id = '$player_id'";
        $res = self::getObjectFromDB($sql);
        $footprint_available = $res['footprint_available'];
        $footprint_used = $res['footprint_used'];

        // Notify all players
        self::notifyAllPlayers( "backToTurnDrawingPhase1", clienttranslate( '${player_name} has cancelled his action' ), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'x' => $locations[0],
            'y' => $locations[1],
            'footprint_available' => $footprint_available,
            'footprint_used' => $footprint_used
            )
        );
        
        // Go to next game state
        $this->gamestate->nextPrivateState( $player_id, "shapeChoiceCancelled" );
    }

    function chooseDrawingLocation( $player_id, $x, $y ) {
        self::checkAction( 'chooseDrawingLocation' ); 

        // self::trace( 'chooseDrawingLocation' ); 
        // self::dump( 'player_id', $player_id );
        // self::dump( 'x', $x );
        // self::dump( 'y', $y );

        $player_id = self::getCurrentPlayerId();

        // We check that the square is available for drawing
        $sql = "SELECT state FROM drawing WHERE coord_x = '$x' AND coord_y = '$y' AND player_id = '$player_id'";
        $res = self::getObjectFromDB( $sql );
        if ($res['state'] > 0) {
            throw new BgaUserException( self::_(clienttranslate("You can't draw here.")) );
        }

        // We check the player can draw there (using cat footprint ?)
        $sql = "SELECT footprint_available, footprint_used, first_chosen_dice_num, first_chosen_dice_val, first_chosen_played_order, second_chosen_dice_num, second_chosen_dice_val, second_chosen_played_order FROM player WHERE player_id = '$player_id'";
        $player_info = self::getObjectFromDb( $sql );

        // What dice was chosen for the location
        if ($player_info['first_chosen_played_order'] == 1) {
            $dice_value = $player_info['first_chosen_dice_val'];
        } else {
            $dice_value = $player_info['second_chosen_dice_val'];
        }

        $nb_required_footprint = abs(($y + 1) - $dice_value);
        if ($nb_required_footprint > 0) {
            if ($nb_required_footprint > $player_info['footprint_available']) {
                throw new BgaUserException( self::_(clienttranslate("You can't draw here.")) );
            }
        }

        // Update player
        $sql = "UPDATE player SET 
                    location_chosen = CONCAT('$x', ',', '$y'),
                    footprint_available = (footprint_available - $nb_required_footprint), 
                    footprint_used = (footprint_used + $nb_required_footprint),
                    footprint_required_tmp = $nb_required_footprint
                WHERE player_id = '$player_id'";
        self::DbQuery($sql);


        // Notify all players
        self::notifyAllPlayers( "drawingLocationChosen", clienttranslate( '${player_name} has chosen where to draw' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'x' => $x,
            'y' => $y,
            'footprint_used' => $player_info['footprint_used'] + $nb_required_footprint, 
            'footprint_available' => $player_info['footprint_available'] - $nb_required_footprint
            )
        );

        // Go to next game state
        $this->gamestate->nextPrivateState( $player_id, "drawingLocationChosen" );
    }

    function chooseShape( $player_id, $shape ) {
        self::checkAction( 'chooseShape' ); 

        $player_id = self::getCurrentPlayerId();

        // We check that he can select this shape
        $sql = "SELECT footprint_available, footprint_used, first_chosen_dice_num, first_chosen_dice_val, first_chosen_played_order, second_chosen_dice_num, second_chosen_dice_val, second_chosen_played_order, location_chosen FROM player WHERE player_id = '$player_id'";
        $player_info = self::getObjectFromDb( $sql );

        $coord = explode(",", $player_info['location_chosen']);
        $x = $coord[0];
        $y = $coord[1];

        $remaing_dice_value = 0;

        // What dice was chosen for the location
        if ($player_info['first_chosen_played_order'] == 1) {
            $remaing_dice_value = $player_info['second_chosen_dice_val'];

            // Update 2nd played dice (1rst chosen)
            $sql = "UPDATE player SET second_chosen_played_order = 2 WHERE player_id = '$player_id'";
            self::DbQuery($sql);
        } else {
            $remaing_dice_value = $player_info['first_chosen_dice_val'];

            // Update 1rst played dice (2nd chosen)
            $sql = "UPDATE player SET first_chosen_played_order = 2 WHERE player_id = '$player_id'";
            self::DbQuery($sql);
        }

        // Can the player choose this shape ?
        $required_footprint = abs($remaing_dice_value - $shape);
        if ($required_footprint > $player_info['footprint_available']) {
            throw new BgaUserException( self::_(clienttranslate("You can't draw this.")) );
        } else {
            if ($required_footprint > 0) {
                // Update available footprints
                $sql = "UPDATE player SET footprint_available = (footprint_available - $required_footprint), footprint_used = (footprint_used + $required_footprint), footprint_required_tmp = (footprint_required_tmp + $required_footprint) WHERE player_id = '$player_id'";
                self::DbQuery($sql);
            }
        }

        // If Butterfly, immediately gain 3 footprints
        $gained_footprints = 0;
        if ($shape == $this->gameConstants["SHAPE_BUTTERFLY_TOY"]) {
            $sql = "SELECT ".$this->gameConstants["FOOTPRINTS_TOTAL"]." - (footprint_available + footprint_used) FROM player WHERE player_id = '$player_id'";
            $free_footprints = self::getUniqueValueFromDB( $sql );

            $gained_footprints = min($this->gameConstants["FOOTPRINTS_GAINED_BUTTERFLY"], $free_footprints);

            // $sql = "UPDATE player SET footprint_available = LEAST(footprint_available + 2, (18 - footprint_used)) WHERE player_id = '$player_id'";
            $sql = "UPDATE player SET footprint_available = footprint_available + $gained_footprints WHERE player_id = '$player_id'";
            self::DbQuery($sql);
        }

        $sql = "UPDATE drawing SET state = '$shape' WHERE player_id = '$player_id' AND coord_x = '$x' AND coord_y = '$y'";
        self::DbQuery($sql);

        // Notify all players
        self::notifyAllPlayers( "shapeChosen", clienttranslate( '${player_name} has chosen his shape' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'x' => $x,
            'y' => $y,
            'footprint_used' => $player_info['footprint_used'] + $required_footprint, 
            'footprint_available' => $player_info['footprint_available'] - $required_footprint + $gained_footprints,
            'shape' => $shape
            )
        );

        if ($shape == $this->gameConstants["SHAPE_CAT_HOUSE"]) {
            $this->gamestate->nextPrivateState( $player_id, "chooseCat" );
        } else {
            // $this->gamestate->nextState( "shapeChosen" );
            $this->gamestate->setPlayerNonMultiactive( $player_id, "" );
        }
    }

    function chooseCat( $player_id, $cat ) {
        self::checkAction( 'chooseCat' ); 

        $player_id = self::getCurrentPlayerId();

        // We check that he can select this cat
        $sql = "SELECT location_chosen, score_cat_$cat FROM player WHERE player_id = '$player_id'";
        $player_info = self::getObjectFromDB( $sql );

        if ($player_info["score_cat_$cat"] != 0) {
            throw new BgaUserException( self::_(clienttranslate("You can't select this cat.")) );
        } else {
            $coord = explode(",", $player_info["location_chosen"]);
            $x = $coord[0];
            $y = $coord[1];

            $score_cat = self::getCatHouseScore($player_id, $cat);
            $sql = "UPDATE player SET score_cat_$cat = $score_cat WHERE player_id = '$player_id'";
            self::DbQuery($sql);
        }

        $sql = "SELECT score_cat_1, score_cat_2, score_cat_3, score_cat_4, score_cat_5, score_cat_6 FROM player WHERE player_id = '$player_id'";
        $scores_cat_info = self::getObjectFromDB( $sql );

        // Notify all players
        self::notifyAllPlayers( "catChosen", clienttranslate( '${player_name} has chosen his cat' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'x' => $x,
            'y' => $y,
            'cat' => $cat,
            'score_cat' => $score_cat,
            'scores_cat_info' => $scores_cat_info
            )
        );

        // $this->gamestate->nextState( "catChosen" );
        // $this->gamestate->nextState( "" );
        $this->gamestate->setPlayerNonMultiactive( $player_id, "" );
    }

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argPlayerTurnPicking() 
    {
        $sql = "SELECT id, dice_value, player_id FROM dice WHERE 1 = 1";
        $dices = self::getObjectListFromDB( $sql );
        $res['dices'] = $dices;

        return $res;
    }

    function argSetupDrawing() {
        $player_id = self::getCurrentPlayerId();
        
        $playersBasicInfos = $this->loadPlayersBasicInfos();
        $res['playersBasicInfos'] = $playersBasicInfos;

        $sql = "SELECT id, dice_value FROM dice WHERE player_id = '$player_id'";
        $dicePlayer = self::getObjectFromDb( $sql );
        $res['dicePlayer'] = $dicePlayer;

        $sql = "SELECT id, dice_value FROM dice WHERE player_id IS NULL";
        $diceCommon = self::getObjectFromDb( $sql );
        $res['diceCommon'] = $diceCommon;
        
        // var_dump($res);

        return $res;
    }

    // Select a dice for position
    function argPlayerTurnDrawingPhase1() 
    {
        $playerBoards = self::getPlayerBoards();

        $player_id = self::getCurrentPlayerId();

        $sql = "SELECT dice_value FROM dice WHERE player_id = '$player_id'";
        $dice_1 = self::getObjectFromDb( $sql );

        $sql = "SELECT dice_value FROM dice WHERE player_id is null";
        $dice_2 = self::getObjectFromDb( $sql );


        // echo "///////////////////////////////////////////////////////////////////";
        // var_dump($player_id);

        $res = array();
        $res['possibleDrawings'] = array();

        // if spectator, these values are not set
        if (isset($dice_1['dice_value']) && $dice_2['dice_value']) {
            // var_dump($dice_1['dice_value']);
            // var_dump($dice_2['dice_value']);

            $res['possibleDrawings'] = self::getPossibleDrawings( $player_id, $dice_1['dice_value'], $dice_2['dice_value'] );
        }
        $res['player_id'] = $player_id;

        $playersBasicInfos = $this->loadPlayersBasicInfos();
        $res['playersBasicInfos'] = $playersBasicInfos;

        $sql = "SELECT id, dice_value FROM dice WHERE player_id = '$player_id'";
        $dicePlayer = self::getObjectFromDb( $sql );
        $res['dicePlayer'] = $dicePlayer;

        $sql = "SELECT id, dice_value FROM dice WHERE player_id IS NULL";
        $diceCommon = self::getObjectFromDb( $sql );
        $res['diceCommon'] = $diceCommon;
        
        // var_dump($res);

        return $res;
    }

    // Select a position
    function argPlayerTurnDrawingPhase2() 
    {
        $playerBoards = self::getPlayerBoards();

        $player_id = self::getCurrentPlayerId();

        $sql = "SELECT footprint_used, footprint_available, first_chosen_dice_num, first_chosen_dice_val, first_chosen_played_order, second_chosen_dice_num, second_chosen_dice_val, second_chosen_played_order FROM player WHERE player_id = '$player_id'";
        $res = self::getObjectFromDb( $sql );

        $footprint_used = $res['footprint_used'];
        $footprint_available = $res['footprint_available'];
        $first_chosen_dice_num = $res['first_chosen_dice_num'];
        $first_chosen_dice_val = $res['first_chosen_dice_val'];
        $first_chosen_played_order = $res['first_chosen_played_order'];
        $second_chosen_dice_num = $res['second_chosen_dice_num'];
        $second_chosen_dice_val = $res['second_chosen_dice_val'];
        $second_chosen_played_order = $res['second_chosen_played_order'];

        if ($first_chosen_played_order == 1) {
            $selected_dice = $first_chosen_dice_val;
        } else {
            $selected_dice = $second_chosen_dice_val;
        }

        // echo "///////////////////////////////////////////////////////////////////";
        // var_dump($player_id);
        // var_dump($dice_1['dice_value']);
        // var_dump($dice_2['dice_value']);

        $res = array();
        $res['possibleLocations'] = self::getPossibleLocationsWithOneDice( $player_id, $selected_dice );
        $res['player_id'] = $player_id;

        $playersBasicInfos = $this->loadPlayersBasicInfos();
        $res['playersBasicInfos'] = $playersBasicInfos;

        $sql = "SELECT id, dice_value FROM dice WHERE player_id IS NULL";
        $diceCommon = self::getObjectFromDb( $sql );
        $res['diceCommon'] = $diceCommon;

        $res['footprint_used'] = $footprint_used;

        // var_dump($res);

        return $res;
    }

    function argPlayerTurnDrawingPhase3() 
    {
        $player_id = self::getCurrentPlayerId();

        $res = array();
        $sql = "SELECT footprint_used, footprint_available, first_chosen_dice_num, first_chosen_dice_val, first_chosen_played_order, second_chosen_dice_num, second_chosen_dice_val, second_chosen_played_order FROM player WHERE player_id = '$player_id'";
        $res = self::getObjectFromDb( $sql );

        // self::dump('res', $res);

        $footprint_used = $res['footprint_used'];
        $footprint_available = $res['footprint_available'];
        $first_chosen_dice_num = $res['first_chosen_dice_num'];
        $first_chosen_dice_val = $res['first_chosen_dice_val'];
        $first_chosen_played_order = $res['first_chosen_played_order'];
        $second_chosen_dice_num = $res['second_chosen_dice_num'];
        $second_chosen_dice_val = $res['second_chosen_dice_val'];
        $second_chosen_played_order = $res['second_chosen_played_order'];

        $remaining_dice_val = 0;

        if (is_null($first_chosen_played_order)) {
            $remaining_dice_val = $first_chosen_dice_val;
        } else {
            $remaining_dice_val = $second_chosen_dice_val;
        }

        $min_shape = max(1, $remaining_dice_val - $footprint_available);
        $max_shape = min(6, $remaining_dice_val + $footprint_available);

        $res = array();
        $res['player_id'] = $player_id;
        $res['min_shape'] = $min_shape;
        $res['max_shape'] = $max_shape;
        $res['footprint_used'] = $footprint_used;
        $res['dice'] = $remaining_dice_val;
        
        // self::dump('res', $res);

        return $res;
    }

    function argCleanBoardForNextRound() 
    {
        $sql = "SELECT player_id id FROM player";
        $players = self::getObjectListFromDB( $sql );

        $sql = "SELECT id, dice_value, player_id FROM dice";
        $dices = self::getObjectListFromDB( $sql );

        $res = array();

        $res['players'] = $players;
        $res['dices'] = $dices;

        return $res;
    }

    function argSetupNewRound() 
    {
        $sql = "SELECT player_id id FROM player";
        $players = self::getObjectListFromDB( $sql );

        $sql = "SELECT id, dice_value, player_id FROM dice";
        $dices = self::getObjectListFromDB( $sql );

        $res = array();

        $res['players'] = $players;
        $res['dices'] = $dices;

        return $res;
    }

    function argPlayerTurnCatSelection() 
    {
        $player_id = self::getCurrentPlayerId();

        $sql = "SELECT score_cat_1, score_cat_2, score_cat_3, score_cat_4, score_cat_5, score_cat_6 FROM player WHERE player_id = '$player_id'";
        $score_cat = self::getObjectFromDb( $sql );

        $res = array();

        $res['score_cat'] = array();
        for ($i=1; $i<=6; $i++) {
            $res['score_cat'][] = $score_cat['score_cat_'.$i];
        }
        $res['player_id'] = $player_id;

        $cat_scoring = array();
        $sql = "SELECT 
                    d.state AS id, COUNT(*) AS nb, p.score_cat_1, p.score_cat_2, p.score_cat_3, p.score_cat_4, p.score_cat_5, p.score_cat_6 
                FROM 
                    drawing d 
                    JOIN player p ON d.player_id = p.player_id 
                WHERE 
                    d.player_id = $player_id AND d.state > 0 
                GROUP BY 
                    d.state, p.score_cat_1, p.score_cat_2, p.score_cat_3, p.score_cat_4, p.score_cat_5, p.score_cat_6 
                ORDER BY 
                    d.state";
        $cat_scoring = self::getCollectionFromDB( $sql );

        $possible_sub_scoring = $cat_scoring;

        $res['possible_sub_scoring'] = $possible_sub_scoring;

        return $res;
    }
    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    function stRollDices()
    {
        // self::trace( "stRollDices" );

        // Renew dices
        $sql = "DELETE FROM dice WHERE 1 = 1";
        self::DbQuery($sql);

        $values = array();
        for ($i=0; $i<=self::getPlayersNumber(); $i++) {
            $values[] = "(".($i + 1).", ".bga_rand(1, 6).")";
            // $values[] = "(".($i + 1).", 3)";
        }

        $sql = "INSERT INTO dice (id, dice_value) VALUES ";
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );

        $this->gamestate->nextState("");
    }

    function stSetupDices()
    {
        // self::trace( "stSetupDices" );

        $this->gamestate->nextState("");
    }

    function stNextPlayerPicking()
    {
    	// self::trace( "stNextPlayerPicking" );
    	 
    	// Go to next player
    	$active_player = self::activeNextPlayer();
    	self::giveExtraTime( $active_player );    

        $sql = "SELECT count(*) nb FROM dice WHERE player_id is null";
        $res = self::getObjectFromDb( $sql );

        //var_dump($res);

        if ($res["nb"] > 1) {
            $this->gamestate->nextState("stayOnPicking");
        } else {
            $sql = "SELECT dice_value FROM dice WHERE player_id is null";
            $res = self::getObjectFromDB( $sql );
            $dice_value = $res['dice_value'];

            $sql = "UPDATE player SET second_chosen_dice_num = 1, second_chosen_dice_val = '$dice_value'";
            self::DbQuery($sql);

            $this->gamestate->nextState("goToSetupDrawing");
        }
    }

    function stSetupDrawing()
    {
        // self::trace( "stSetupDrawing" );

        $this->gamestate->nextState("");
    }

    function stMultiplayerDrawingPhase()
    {
        $this->gamestate->setAllPlayersMultiactive();
        
        //this is needed when starting private parallel states; players will be transitioned to initialprivate state defined in master state
        $this->gamestate->initializePrivateStateForAllActivePlayers();

        return;
    }

    function stEndPlayerTurn()
    {
        // self::trace( "stEndPlayerTurn" );

        $sql = "SELECT COUNT(*) AS nb FROM player WHERE has_passed = true OR (first_chosen_played_order IS NOT NULL AND second_chosen_played_order IS NOT NULL)";
        $res = self::getObjectFromDB( $sql );
        if ($res['nb'] < (self::getPlayersNumber())) {
            $this->gamestate->nextState( "nextPlayer" );
        } else {
            $this->gamestate->nextState( "goColumnScoring" );
        }
    }

    // Check columns scoring
    function stColumnScoring()
    {
        // self::trace( "stColumnScoring" );

        // Initial state of column score, before this ans of round calculation
        $initial_column_score_state = $this->getColumnScoreState();

        // Column score by player
        $sql = "SELECT player_id, score_col_1, score_col_2, score_col_3, score_col_4, score_col_5 FROM player";
        $player_score_col = self::getCollectionFromDB( $sql );

        $boards = self::getPlayerBoards();

        // self::dump( 'player_score_col', $player_score_col );

        foreach ( $boards as $player_id => $board ) {
            for ( $i=0; $i<5; $i++ ) {
                // self::dump( 'boards ------------- ', $player_score_col[$player_id]["score_col_".($i+1)] );
                if ( $player_score_col[$player_id]["score_col_".($i+1)] == 0) {
                    // is there a cat house in the column ?
                    $cat_house_in_col = false;
                    // nb of filled cells in the column
                    $nb_shape = 0;

                    for ( $j=0; $j<6; $j++ ) {
                        if ( isset($board[$i][$j] )) {
                            if ($board[$i][$j] > 0 ) {
                                $nb_shape++;

                                if ($board[$i][$j] == $this->gameConstants["SHAPE_CAT_HOUSE"] ) {
                                    $cat_house_in_col = true;

                                    // self::trace( "!!!!!!!!!!!!!!!!!!!!! $player_id / $i, $j : ".$boards[$player_id][$i][$j] );
                                }
                            }
                        }
                    }

                    if ( $nb_shape == $this->gameConstants["COL_FLOORS_NUMBER"][$i] ) {

                        // self::trace( "!!!!!!!!!!!!!!!!!!!!! $player_id / $i" );

                        // We can score the highest only if we have a cat house in the column, and the column not not already be filled by anyone
                        if ( $cat_house_in_col && $initial_column_score_state["global"][$i] == 0 ) {
                            // self::trace( "!!!!!!!!!!!!!!!!!!!!! $player_id / $i MAX" );

                            $sql = "UPDATE player SET score_col_".($i+1)." = ".$this->gameConstants["COL_SUB_SCORING_COL_MAX"][$i]." WHERE player_id = '$player_id'";
                            self::DbQuery($sql);

                        } else {
                            // self::trace( "!!!!!!!!!!!!!!!!!!!!! $player_id / $i MIN" );

                            $sql = "UPDATE player SET score_col_".($i+1)." = ".$this->gameConstants["COL_SUB_SCORING_COL_MIN"][$i]." WHERE player_id = '$player_id'";
                            self::DbQuery($sql);

                        }
                    }
                }
            }
        }

        $new_column_score_state = $this->getColumnScoreState();

        $notify_validated_col = array();
        $notify_erased_col = array();

        foreach ( $player_score_col as $player_id => $board ) {
            for ( $i=0; $i<5; $i++ ) {
                if ( $initial_column_score_state["players"][$player_id][$i][0] != $new_column_score_state["players"][$player_id][$i][0] ) {
                    if ( $new_column_score_state["players"][$player_id][$i][0] == 1 ) {
                        $notify_validated_col[] = array($player_id, $i, 0);
                    } else {
                        $notify_erased_col[] = array($player_id, $i, 0);
                    }
                }
                if ( $initial_column_score_state["players"][$player_id][$i][1] != $new_column_score_state["players"][$player_id][$i][1] ) {
                    if ( $new_column_score_state["players"][$player_id][$i][1] == 1 ) {
                        $notify_validated_col[] = array($player_id, $i, 1);
                    } else {
                        $notify_erased_col[] = array($player_id, $i, 1);
                    }
                }
            }
        }

        // Notify all players
        self::notifyAllPlayers( "columnSubScoringValidated", "", array(
            'params' => $notify_validated_col
            )
        );

        self::notifyAllPlayers( "columnSubScoringErased", "", array(
            'params' => $notify_erased_col
            )
        );

        // self::dump( 'initial_column_score_state', $initial_column_score_state["players"] );
        // self::dump( 'new_column_score_state', $new_column_score_state["players"] );

        // self::dump( 'notify_validated_col', $notify_validated_col );
        // self::dump( 'notify_erased_col', $notify_erased_col );

        $this->gamestate->nextState("");
    }

    function stNextPlayerDrawing()
    {
    	// self::trace( "stNextPlayerDrawing" );
    	
        $active_player_id = self::getActivePlayerId();
        $sql = "UPDATE player SET location_chosen = null WHERE player_id = '$active_player_id'";
        self::DbQuery($sql);

    	// Go to next player
    	$active_player = self::activeNextPlayer();
    	self::giveExtraTime( $active_player );    

        //var_dump($res);

        $this->gamestate->nextState("stayOnDrawing");
    }

    function stNextRound()
    {
        // self::trace( "stNextRound" );

        $players = $this->loadPlayersBasicInfos();

        $game_over = false;

        foreach ($players as $player_id => $info) {
            $nb_completed_columns = 0;

            $sql = "SELECT score_col_1, score_col_2, score_col_3, score_col_4, score_col_5 FROM player WHERE player_id = '$player_id'";
            $player_info = self::getObjectFromDB( $sql );

            for ($i=0; $i<5; $i++) {
                $col_name = "score_col_".($i + 1);
                if ($player_info[$col_name] > 0) {
                    $nb_completed_columns++;
                }
            }

            if ($nb_completed_columns >= 3) {
                $game_over = true;
            }
        }

        if ($game_over) {
            $this->gamestate->nextState("goStatsCalculation");
        } else {
            $this->gamestate->nextState("goToCleanBoardForNextRound");
        }
    }

    function stCleanBoardForNextRound()
    {
        // self::trace( "stCleanBoardForNextRound" );

        // Clear players turn datas
        $sql = "UPDATE player SET 
                has_passed = false,
                first_chosen_dice_num = NULL, first_chosen_dice_val = NULL, first_chosen_played_order = NULL, 
                second_chosen_dice_num = NULL, second_chosen_dice_val = NULL, second_chosen_played_order = NULL, 
                location_chosen = NULL, footprint_required_tmp = 0";
        self::DbQuery( $sql );

        $this->gamestate->nextState("");
    }

    function stStatsCalculation()
    {
        $sql = "SELECT DISTINCT player_id FROM drawing ORDER BY player_id ASC";
        $players = self::getCollectionFromDb( $sql );

        // $result = array();
        $final_score = array();
        foreach ($players as $player_id => $player) {
            $finalScore[$player_id] = array();

            $catHouseScore = $this->getCatHouseScoreTotal($player_id);
            $ballOfYarnScore = $this->getBallOfYarnScore($player_id);
            $butterflyToyScore = $this->getButterflyToyScore($player_id);
            $foodBowlScore = $this->getFoodBowlScore($player_id);
            $cushionScore = $this->getCushionScore($player_id);
            $mouseToyScore = $this->getMouseToyScore($player_id);

            $columnsScore = $this->getColumnsScore($player_id);
            // $catFootprintsScore = $this->getCatFootprintsScore($player_id);
            $catFootprintsScore = 0;

            self::setStat( $catHouseScore, "cat_house", $player_id );
            self::setStat( $ballOfYarnScore, "ball_of_yarn", $player_id );
            self::setStat( $butterflyToyScore, "butterfly_toy", $player_id );
            self::setStat( $foodBowlScore, "food_bowl", $player_id );
            self::setStat( $cushionScore, "cushion", $player_id );
            self::setStat( $mouseToyScore, "mouse_toy", $player_id );

            self::setStat( $columnsScore, "columns", $player_id );
            // self::setStat( $catFootprintsScore, "cat_footprints", $player_id );

            $totalScore = $catHouseScore + $ballOfYarnScore + $butterflyToyScore + $foodBowlScore + $cushionScore + $mouseToyScore + $columnsScore + $catFootprintsScore;

            $finalScore[$player_id][] = $catHouseScore;
            $finalScore[$player_id][] = $ballOfYarnScore;
            $finalScore[$player_id][] = $butterflyToyScore;
            $finalScore[$player_id][] = $foodBowlScore;
            $finalScore[$player_id][] = $cushionScore;
            $finalScore[$player_id][] = $mouseToyScore;
            $finalScore[$player_id][] = $columnsScore;
            $finalScore[$player_id][] = $totalScore;

            $this->DbQuery("UPDATE player SET player_score='$totalScore' WHERE player_id='$player_id'");
        }

        // Notify all players
        self::notifyAllPlayers( "displayFinalScore", "", array(
            'finalScore' => $finalScore
            )
        );

        $this->gamestate->nextState("");
    }

    function stSetupNewRound()
    {
        // self::trace( "stSetupNewRound" );

        $sql = "SELECT player_id FROM player WHERE is_first_player = true";
        $current_first_player = self::getUniqueValueFromDB( $sql );

        $sql = "UPDATE player SET is_first_player = false";
        self::DbQuery( $sql );

        $player_order = self::getNextPlayerTable();
        $sql = "UPDATE player SET is_first_player = true WHERE player_id = ".$player_order[$current_first_player];
        self::DbQuery( $sql );

        while (self::getActivePlayerId() != $player_order[$current_first_player]) {
            $this->activeNextPlayer();
        }

        $this->gamestate->nextState("");
    }

    function argSetupDices() 
    {
        $sql = "SELECT id, dice_value, player_id FROM dice WHERE 1 = 1";
        $dices = self::getObjectListFromDB( $sql );
        $res['dices'] = $dices;

        return $res;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                case "playerTurnPicking":
                    $sql = "SELECT id, dice_value FROM dice";
                    $dices = self::getObjectListFromDB( $sql );

                    $nb = count($dices);
                    $dice_number = bga_rand(0, ($nb-1));

                    $this->pickDice( $dices[$dice_number]["id"], $dices[$dice_number]["dice_value"] );

                    break;
                
                case "playerTurnDrawingPhase1":
                    $this->pass( self::getActivePlayerId() );
                    break;

                case "playerTurnDrawingPhase2":
                    $this->cancelLocationDiceChoice( self::getActivePlayerId() );
                    break;

                case "playerTurnDrawingPhase3":
                    $this->cancelLocationChoice( self::getActivePlayerId() );
                    break;

                case "playerTurnCatSelection":
                    $this->cancelShapeChoice( self::getActivePlayerId() );
                    break;

                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( clienttranslate("Zombie mode not supported at this game state: ").$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
