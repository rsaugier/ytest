<?php #coding: utf-8
/**
 * @file ytest.php
 *
 * This file is the main ytest library file.
 * It includes all other files of the yTest library.
 */

function yTest_requireYTestClass($class) {
    global $yTest_yTestLibPath;
    require_once($yTest_yTestLibPath.'/'.$class.'.php');
}

function yTest_requirePhpUnitClass($class) {
    global $yTest_phpUnitLibPath;
    $parts = explode('_', $class);
    $path = implode('/', $parts).'.php';
    require_once($yTest_phpUnitLibPath.'/'.$path);
}

/**
 * This function is called by ytest when it deems necessary to stop the whole test process.
 */
function yTest_end() {
    if(function_exists("yTest_endHook"))
    {
	// You can define this callback in your Bootstrap.php to customize the end of the script.
	yTest_endHook();
    }
    else
    {
        exit(1); // die abruptly
    }
}

function yTest_init($yTestConfigPath, $yTestLibPath, $phpUnitLibPath) {
	
    require_once($yTestConfigPath);

    global $yTest_yTestLibPath;
    $yTest_yTestLibPath = $yTestLibPath;
    require($yTest_yTestLibPath.'/yTest_Functions.php');

    global $yTest_phpUnitLibPath;
    $yTest_phpUnitLibPath = $phpUnitLibPath;

    // [RS] If __autoload() has been defined correctly this should work.
    yTest_CallsRouter::initialize();
}
