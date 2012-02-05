<?php

/**
 * Helper class to generate human-readable stack traces.
 */
class yTest_Stack {
    private static $enableDetailledMode = false;
    private static $removeAnnoyingFrames = true; // remove annoying frames

    public static function setDetailledMode($enable) {
        self::$enableDetailledMode = $enable;
    }

    public static function keepAnnoyingFrames($enableKeep) {
        self::$removeAnnoyingFrames = $enableKeep;
    }

    public static function getStackLineMaxLength() {
        return YTEST_STACK_LINE_MAX_LENGTH;
    }

    public static function getCurrentStack($maxLineLen = null) {
        if (self::$enableDetailledMode) {
            return self::getDetailledCurrentStack($maxLineLen);
        } else {
            return self::getAbbreviatedCurrentStack($maxLineLen);
        }
    }

    public static function getAbbreviatedCurrentStack($maxLineLen = null) {
        if ($maxLineLen === null) {
            $maxLineLen = self::getStackLineMaxLength();
        }
        $bt = debug_backtrace();

        $preSkip = true;
        $removedPhpUnitFrames = false;
        $lines = array();
        foreach ($bt as $frame) {

            if (self::$removeAnnoyingFrames) {
                if ($preSkip && array_key_exists('class', $frame) && ($frame['class'] == 'yTest_Stack')) {
                    continue;
                } else {
                    $preSkip = false;
                    if (array_key_exists('class', $frame)
                        && $frame['class'] == 'PHPUnit_Framework_TestCase'
                        && $frame['function'] == 'runTest') {
                        $removedPhpUnitFrames = true;
                        break;
                    }
                }

            }

            $callPart = " ";
            if (isset($frame['class'])) {
                $callPart .= $frame['class']  . $frame['type'];
            }
            $callPart .= $frame['function'];

            if (isset($frame['file'])) {
                $filePart = str_replace(yTest_getBaseDir(), '', $frame['file']) . ":" . $frame['line'];
            } else {
                $filePart = "<no file info>";
            }

            $lines[] = array($callPart, $filePart);
        }

        if (self::$removeAnnoyingFrames && $removedPhpUnitFrames) {
            // We remove two extra frames on top of PHPUnit ones:
            //
            //  <TestCaseName>-><TestMethodName> <no file info>
            //  ReflectionMethod->invokeArgs     /test/libs/phpunit/PHPUnit/Framework/TestCase.php:822

            $lines = array_slice($lines, 0, count($lines) - 2);
        }

        return $lines;
    }

    public static function getDetailledCurrentStack($maxLineLen = null) {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();
        $lines = explode("\n", $trace);
        $res = array();
        foreach ($lines as $line) {
            $res[] = array($line);
        }
        return $res;
    }


    /**
     * Format stacks returned by getCurrentStack() and friends.
     * Returns an array of strings.
     */
    public static function formatStack($stack, $maxLineLen = null) {
        if ($maxLineLen === null) {
            $maxLineLen = self::getStackLineMaxLength();
        }

        if (count($stack) == 0) {
            return $stack;
        }

        $numParts = count($stack[0]);
        if ($numParts == 0) {
            return $stack;
        }

        // Find the max length of each part
        $maxLens = array();
        for ($i = 0; $i < $numParts; ++$i) {
            $max = 0;
            foreach ($stack as $parts) {
                $part = $parts[$i];
                $l = strlen($part);
                if (strlen($part) > $max) {
                    $max = strlen($part);
                }
            }
            $maxLens[$i] = $max;
        }

        // Build each line of the result by indenting the parts
        $lines = array();
        foreach ($stack as $parts) {
            $line = "";
            for ($i = 0; $i < $numParts; ++$i) {
                $part = $parts[$i];
                $line .= $part;
                $pad = $maxLens[$i] - strlen($part);
                $pad = max($pad, 0);
                $line .= str_repeat(' ', $pad + 1);
            }
            $lines[] = $line;
        }

        // Truncate the lines if necessary
        $cutLines = array();
        foreach ($lines as $line) {
            if (strlen($line) > $maxLineLen) {
                $cutLines[] = substr($line, 0, $maxLineLen) . " ...<CUT!>...";
            } else {
                $cutLines[] = $line;
            }
        }

        return $cutLines;
    }


}
