<?php #coding: utf-8
/**
 * @file Bootstrap.php
 *
 * This is an example of bootstrap file that can be used for
 * a PHPUnit test suite using yTest.
 * (see the --bootstrap option of phpunit.php or y_test_runner.php).
 *
 * As ytest relies on autoloading to work, we must define
 * an __autoload function here. You will have to make it suit
 * your own needs too.
 */

function __autoload($class) {
    /**
     * Start PHP output capture. See comment below.
     */
    if ( ob_start() === false ) {
        yTest_error('autoload: can\'t start output buffering.');
    }

    /**
     * If the class name starts with yTest_, then it is a yTest file.
     */
    if (substr($class, 0, 6) == 'yTest_') {
        yTest_requireYTestClass($class);
    } 
    /**
     * If the class name starts with PHPUnit_, then it is a PHPUnit file.
     */
    elseif (substr($class, 0, 8) == 'PHPUnit_') {
        yTest_requirePhpUnitClass($class);
    } 
    /**
     * Otherwise this must be one of the application's file.
     * It should be loaded here with require() or require_once()
     */
    else {
        // TO BE FILLED WITH YOUR OWN __autoload() CODE (if necessary)
        yTest_error('__autoload(): don\'t know how to load '.$class);
    }

    /** We make sure, using output buffering, that there is no unwanted
     *  output in the loaded classes, which is generally wanted when loading
     *  library PHP files. Of course your need may differ.
     */
    $output = ob_get_clean();
    if ( $output === false ) {
        yTest_error('autoload: output buffering was not activated ?!?');
    }
    if ( strlen($output) > 0 ) {
        yTest_error('autoload: output detected while loading class '.$class.': '."\n".$output."\n");
    }
}


$baseDir = dirname(__FILE__);

// First, let's load ytest 's main file.
require_once $baseDir."/libs/ytest/ytest.php";

// Initialized ytest
yTest_init($baseDir."/config/ytest_config.php", $baseDir."/libs/ytest", $baseDir."/libs/phpunit");

// Seed mt_rand() manually with an arbitrary value (To have consistent behavior for each test suite execution)
mt_srand(1735923459836);

// This is a good place to set some testsuite-wide rewires. Here's an example with setcookie():
//$rewire = new yTest_RewireFunction('setcookie', yTest_Yes::instance(true), 'any');
//$rewire->apply();
