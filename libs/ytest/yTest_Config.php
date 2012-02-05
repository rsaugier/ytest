<?php

class yTest_Config {
    // Returns the workcopy/test dir, without trailing /
    public static function getTestsBaseDir() {
        global $yTest_testsBaseDir;
        return $yTest_testsBaseDir;
    }

    // Returns the workcopy dir, without trailing /
    public static function getAppDir() {
        static $appDir = null;
        if ($appDir === null) {
            $appDir = realpath( self::getTestsBaseDir() . "/../" );
        }
        return $appDir;
    }

    // Returns the datasets dir, without trailing /
    public static function getDatasetsDir() {
        return self::getTestsBaseDir() . "/datasets";
    }
};

?>