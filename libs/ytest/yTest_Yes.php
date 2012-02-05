<?php

class yTest_Yes {
    private $yesValue;

    private function __construct($yesValue) {
        $this->yesValue = $yesValue;
    }

    public static function instance($yesValue) {
        static $insts = array();

        foreach ($insts as $data) {
            if ($data['yesValue'] === $yesValue) {
                return $data['inst'];
            }
        }

        $inst = new yTest_Yes($yesValue);
        $insts[] = array('yesValue' => $yesValue, 'inst' => $inst);

        return $inst;
    }


    // [JL] anybody can call me, with any params :D !
    public function any() {
        return $this->yesValue;
    }
};
