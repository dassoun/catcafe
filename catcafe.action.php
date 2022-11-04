<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * catcafe implementation : © Julien Coignet <breddabasse@hotmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * catcafe.action.php
 *
 * catcafe main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/catcafe/catcafe/myAction.html", ...)
 *
 */

class action_catcafe extends APP_GameAction
{ 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "catcafe_catcafe";
            self::trace( "Complete reinitialization of board game" );
        }
  	} 
  	
  	// TODO: defines your action entry points there

    public function pickDice()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $dice_id = self::getArg( "dice_id", AT_posint, true );
        $dice_face = self::getArg( "dice_face", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->pickDice( $dice_id, $dice_face );

        self::ajaxResponse( );
    }

    public function draw()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        // $player_id = self::getArg( "player_id", AT_posint, true );
        $x = self::getArg( "x", AT_posint, true );
        $y = self::getArg( "y", AT_posint, true );
        $shape = self::getArg( "shape", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->draw( $x, $y, $shape );

        self::ajaxResponse( );
    }

    public function pass()
    {
        self::setAjaxMode();     

        $this->game->pass();

        self::ajaxResponse();
    }

    public function cancelLocationDiceChoice() 
    {
        self::setAjaxMode();     

        $this->game->cancelLocationDiceChoice();

        self::ajaxResponse();
    }

    public function chooseDiceForLocation()
    {
        self::setAjaxMode();

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $num_player_dice = self::getArg( "num_player_dice", AT_posint, true );
        $dice_face = self::getArg( "dice_face", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->chooseDiceForLocation( $num_player_dice, $dice_face );

        self::ajaxResponse( );
    }

    public function cancelLocationChoice()
    {
        self::setAjaxMode();     

        $this->game->cancelLocationChoice();

        self::ajaxResponse();
    }

    public function chooseDrawingLocation()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $x = self::getArg( "x", AT_posint, true );
        $y = self::getArg( "y", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->chooseDrawingLocation( $x, $y );

        self::ajaxResponse( );
    }

    public function cancelShapeChoice()
    {
        self::setAjaxMode();     

        $this->game->cancelShapeChoice();

        self::ajaxResponse( );
    }

    public function chooseShape()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $shape = self::getArg( "shape", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->chooseShape( $shape );

        self::ajaxResponse( );
    }

    public function chooseCat()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $cat = self::getArg( "cat", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->chooseCat( $cat );

        self::ajaxResponse( );
    }
    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

}
