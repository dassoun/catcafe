<?php

Class CTCPossibleCoord {

    public $x;
    public $y;
    public $cost;

    public function __construct($x, $y, $cost) {
        $this->x = $x;
        $this->y = $y;
        $this->cost = $cost;
    }
}