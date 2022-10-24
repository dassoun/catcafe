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
 * states.inc.php
 *
 * catcafe game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 10 )
    ),
    
    // Note: ID=10 => your first state
    10 => array(
        "name" => "rollDices",
        "description" => '',
        "type" => "game",
        "action" => "stRollDices",
        "transitions" => array( "" => 20 )
    ),

    20 => array(
        "name" => "setupDices",
        "description" => '',
        "type" => "game",
        "args" => "argSetupDices",
        "action" => "stSetupDices",
        "transitions" => array( "" => 30 )
    ),

    30 => array(
        "name" => "playerTurnPicking",
        "description" => clienttranslate('${actplayer} must pick a dice.'),
        "descriptionmyturn" => clienttranslate('${you} must pick a dice.'),
        "type" => "activeplayer",
        "args" => "argPlayerTurnPicking",
        "possibleactions" => array( "pickDice" ),
        "transitions" => array( "dicePicked" => 40 )
    ),

    40 => array(
        "name" => "nextPlayerPicking",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayerPicking",
        "transitions" => array( "stayOnPicking" => 30, "goToSetupDrawing" => 50 )
    ),

    50 => array(
        "name" => "setupDrawing",
        "description" => '',
        "type" => "game",
        "action" => "stSetupDrawing",
        "transitions" => array( "" => 60 )
    ),

    60 => array(
        "name" => "multiplayerDrawingPhase",
        "description" => clienttranslate('Waiting for other players to end their turn.'),
        "descriptionmyturn" => clienttranslate('${you} must do your turn'), // Won't be displayed anyway since each private state has its own description
        "type" => "multipleactiveplayer",
        "initialprivate" => 70, // This makes this state a master multiactive state and enables private states, this is also a first private state
        "action" => "stMultiplayerDrawingPhase",
        // "args" => "argMultiplayerDrawingPhase",
        //"possibleactions" => ["changeMind"], //this action is possible if player is not in any private state which usually happens when they are inactive
        "transitions" => array( "" => 110 ) // this is normal next transition which will happen after all players finish their turns 
    ),

    70 => array(
        // Choose a 1rst dice for location, or pass
        "name" => "playerTurnDrawingPhase1",
        "description" => clienttranslate('${actplayer} must choose a dice for location, or pass.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a dice for location, or pass.'),
        "type" => "private",
        "args" => "argPlayerTurnDrawingPhase1",
        "possibleactions" => array( "pass", "chooseDiceForLocation" ),
        "transitions" => array( "passed" => 70, "diceForLocationChosen" => 80, "nextRound" => 110 )
    ),

    // 70 => array(
    //     "name" => "nextPlayerDrawing",
    //     "description" => '',
    //     "type" => "game",
    //     "action" => "stNextPlayerDrawing",
    //     "transitions" => array( "stayOnDrawing" => 60, "goToNextRound" => 100 )
    // ),

    80 => array(
        // Choose a location for drawing according to the chosen dice
        "name" => "playerTurnDrawingPhase2",
        "description" => clienttranslate('${actplayer} must choose where to draw.'),
        "descriptionmyturn" => clienttranslate('${you} must choose where to draw.'),
        "type" => "private",
        "args" => "argPlayerTurnDrawingPhase2",
        "possibleactions" => array( "chooseDrawingLocation", "cancelLocationDiceChoice" ),
        "transitions" => array( "drawingLocationChosen" => 90, "locationDiceChoiceCancelled" => 70 )
    ),

    90 => array(
        // Choose a shape to draw
        "name" => "playerTurnDrawingPhase3",
        "description" => clienttranslate('${actplayer} must choose a shape.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a shape.'),
        "type" => "private",
        "args" => "argPlayerTurnDrawingPhase3",
        "possibleactions" => array( "chooseShape", "cancelLocationChoice" ),
        "transitions" => array( "shapeChosen" => 110, "chooseCat" => 100, "locationChoiceCancelled" => 70 )
    ),

    100 => array(
        // Choose a cat
        "name" => "playerTurnCatSelection",
        "description" => clienttranslate('${actplayer} must choose a cat.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a cat.'),
        "type" => "private",
        "args" => "argPlayerTurnCatSelection",
        "possibleactions" => array( "chooseCat", "cancelShapeChoice" ),
        "transitions" => array( "catChosen" => 110, "shapeChoiceCancelled" => 70 )
    ),

    // Next player for same round or column scoring ?
    // 110 => array(
    //     "name" => "endPlayerTurn",
    //     "description" => '',
    //     "type" => "game",
    //     "action" => "stEndPlayerTurn",
    //     "transitions" => array( "nextPlayer" => 70, "goColumnScoring" => 120 )
    // ),

    110 => array(
        "name" => "columnScoring",
        "description" => '',
        "type" => "game",
        "action" => "stColumnScoring",
        "transitions" => array( "nextRound" => 120 )
    ),

    // New round or end game => stats calculation ?
    120 => array(
        "name" => "nextRound",
        "description" => '',
        "type" => "game",
        "action" => "stNextRound",
        "transitions" => array( "goToCleanBoardForNextRound" => 130, "goStatsCalculation" => 150 )
    ),

    130 => array(
        "name" => "cleanBoardForNextRound",
        "description" => '',
        "type" => "game",
        "args" => "argCleanBoardForNextRound",
        "action" => "stCleanBoardForNextRound",
        "transitions" => array( "goToSetupNewRound" => 140 )
    ),

    140 => array(
        "name" => "setupNewRound",
        "description" => '',
        "type" => "game",
        "args" => "argSetupNewRound",
        "action" => "stsetupNewRound",
        "transitions" => array( "" => 10 )
    ),

    150 => array(
        "name" => "statsCalculation",
        "description" => '',
        "type" => "game",
        "action" => "stStatsCalculation",
        "transitions" => array( "" => 99 )
    ),

/*
    Examples:
    
    2 => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,   
        "transitions" => array( "endGame" => 99, "nextPlayer" => 10 )
    ),
    
    10 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play a card or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard", "pass" ),
        "transitions" => array( "playCard" => 2, "pass" => 2 )
    ), 

*/    
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



