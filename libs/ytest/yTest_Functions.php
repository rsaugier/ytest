<?php

function yTest_requireOnce($pathRelativeToWorkCopy) {
    global $yTest_testsBaseDir;
    $path = $yTest_testsBaseDir.'/../'.$pathRelativeToWorkCopy;
    require_once $path;
}

function yTest_notice() {
    echo "yTest NOTICE:\n\n";
    foreach (func_get_args() as $msg) {
        echo "$msg\n";
    }
    echo "\n";
}

function yTest_dbg() {
    static $handle = null;
    if ($handle === null) {
        $handle = fopen("/dev/stdout", "w");
    }
    foreach (func_get_args() as $msg) {
        fwrite($handle, $msg."\n");
    }
}

function yTest_dbgStack() {
    yTest_dbg(implode("\n", yTest_Stack::formatStack(yTest_Stack::getCurrentStack())));
}

function yTest_dbgx() {
    static $handle = null;
    static $i = 0;
    if ($handle === null) {
        $handle = fopen("/dev/stdout", "w");
    }
    foreach (func_get_args() as $thing) {
        fwrite($handle, '['.$i."]\n".var_export($thing, true)."\n");
        $i++;
    }
}

function yTest_debugCC() {
    if (YTEST_DEBUG_CODE_CHANGES) {
        foreach (func_get_args() as $msg) {
            echo "CC: $msg\n";
        }
    }
}

function yTest_error() {
    echo "yTest FATAL ERROR:\n";
    foreach (func_get_args() as $msg) {
        echo "$msg\n";
    }
    $level = ob_get_level();
    for ($i = 0; $i<$level; $i++) {
        ob_end_flush();
    }
    yTest_end();
}

function yTest_assert($a) {
    $e = new Exception('yTest_assert() assertion failed');
    if (!$a) {
        yTest_error("yTest internal assertion failed! :\n" . $e);
    }
}

function yTest_isEqual($a, $b) {
    if (is_float($a) || is_float($b)) {
        return abs($a - $b) < 0.000001;
    } else {
        return ($a === $b);
    }
}

function yTest_getConfigDir() {
    $dir = dirname(__FILE__);
    return realpath($dir.'/../config');
}

function yTest_getBaseDir() {
    global $yTest_testsBaseDir;
    return realpath($yTest_testsBaseDir.'/..');
}

function yTest_getSrcDir() {
    global $yTest_testsBaseDir;
    return realpath($yTest_testsBaseDir.'/../src');
}

function yTest_getTestsDir() {
    global $yTest_testsBaseDir;
    $dir = $yTest_testsBaseDir;
    if (substr($dir, -1) != '/') {
        $dir .= '/';
    }
    return $dir;
}

function yTest_getFixturesDir() {
    global $yTest_testsBaseDir;
    $dir = $yTest_testsBaseDir;
    if (substr($dir, -1) != '/') {
        $dir .= '/';
    }
    return $dir.'/data/fixtures/';
}

function yTest_loadFixtures($fixtureFolder) {
    $dir = yTest_getFixturesDir().$fixtureFolder;
    $workingDir = opendir($dir);
    while ($entry = @readdir($workingDir)) {
        if (!is_dir($dir.'/'.$entry) &&
            substr($entry, -4) === '.php' &&
            substr($entry, -8) !== '.tpl.php') {
            require_once($dir.'/'.$entry);
        }
    }
    closedir($workingDir);
}

function yTest_checkedPregReplace($pattern, $replace, $subject, $cntExpected) {
    $subject = preg_replace($pattern, $replace, $subject, -1, $cnt);
    if ( $subject === null || $cnt != $cntExpected ) {
        yTest_error('Problem on a preg_replace call in bootstrap:', var_export(array(
            'pattern' => $pattern,
            'cnt expected' => $cntExpected,
            'real cnt' => $cnt,
            'subject is null' => is_null($subject),
            ), true));
    }
    return $subject;
}

function yTest_getPhpArgs() {
    $args = yTest_getArgs();
    if ($args === null) {
        return null;
    } else {
        $code = 'return '.$args;
        if (substr(trim($code), -1, 1) !== ';') {
            $code .= ';';
        }
        return eval($code);
    }
}

function yTest_getArgs() {
    global $yTest_args;
    if (isset($yTest_args)) {
        return $yTest_args;
    } else {
        return null;
    }
}

function yTest_getJsonArgs() {
    $args = yTest_getArgs();
    if ($args === null) {
        return null;
    } else {
        return json_decode($args);
    }
}
