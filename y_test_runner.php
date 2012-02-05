#!/usr/bin/env php
<?php
/** This is a custom CLI based tests runner which extends phpunit.php a bit.
 *  You might want to use it, or stick with phpunit.php
 */

set_include_path(dirname(__FILE__).'/libs/phpunit'.PATH_SEPARATOR.get_include_path());

require 'PHPUnit/Framework.php';
PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

require 'PHPUnit/TextUI/Command.php';

// Command line options.
class YTestOptions {

    public static $immediateFeedback = false;
    public static $progress = false;
    public static $leaks = false;
    public static $perfs = false;
    public static $repeat = null;
}


class YTestLogger {
    private static $level = 0;

    public static function say($msg) {
        $lines = explode("\n", $msg);
        foreach ($lines as $line) {
            if (strlen($line) > 0) {
                echo str_repeat("  ", self::$level) . $line . "\n";
            }
        }
    }

    public static function indent($n = 2) {
        self::$level += $n;
    }

    public static function dedent($n = 2) {
        self::$level -= $n;
    }
}


class YTestBaseTestListener implements PHPUnit_Framework_TestListener {

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) { }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) { }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) { }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) { }

    public function startTest(PHPUnit_Framework_Test $test) { }

    public function endTest(PHPUnit_Framework_Test $test, $time) { }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) { }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) { }

}


class YTestProgressTestListener implements PHPUnit_Framework_TestListener {

    private $currentSuite = null;
    private $currentTest = null;

    private function printError(Exception $e) {
        YTestLogger::indent();
        YTestLogger::say($e);
        YTestLogger::dedent();
    }

    private function printFailure(PHPUnit_Framework_AssertionFailedError $e) {
        YTestLogger::indent();
        YTestLogger::say($e->getMessage() . "\n");
        foreach ($e->getTrace() as $frame) {
            if (array_key_exists('file', $frame)) {
                $file = $frame['file'];
                if (strpos($file, 'PHPUnit/') || strpos($file, 'CustomTestCase')) {
                    break;
                }
                YTestLogger::say($file . ":" . $frame['line'] . "\n");
            }
        }
        YTestLogger::dedent();
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
        YTestLogger::say("\nError in " . $this->currentTest->toString() . ":\n");
        $this->printError($e);
        YTestLogger::say("\n");
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
        YTestLogger::say("\nFailure in " . $this->currentTest->toString() . ":\n");
        $this->printFailure($e);
        YTestLogger::say("\n");
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        YTestLogger::say("Test '" . $test->toString() . "' is incomplete.\n");
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        YTestLogger::say("Test '" . $test->toString() . "' has been skipped.\n");
    }

    public function startTest(PHPUnit_Framework_Test $test) {
        $this->currentTest = $test;
        if (YTestOptions::$progress) {
            YTestLogger::say("Test '" . $test->toString() . "' started.\n");
        }
    }

    public function endTest(PHPUnit_Framework_Test $test, $time) {
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
        $this->currentSuite = $suite;
        if (YTestOptions::$progress) {
            YTestLogger::say("TestSuite '" . $suite->getName() . "' started.\n");
            YTestLogger::indent();
        }
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
        if (YTestOptions::$progress) {
            YTestLogger::dedent();
            YTestLogger::say("TestSuite '" . $suite->getName() . "' ended.\n");
        }
    }
}


class YTestPerfListener extends YTestBaseTestListener {

    private $startTime = 0;
    private $startMemUsage = 0;
    private $curCol = 0;

    public function startTest(PHPUnit_Framework_Test $test) {
        $this->startTime = $this->getProcessTime();
        $this->startMemUsage = $this->getMemUsage();
    }

    public function endTest(PHPUnit_Framework_Test $test, $time) {

        $duration = $this->getProcessTime() - $this->startTime;
        $this->startLine();
        $str = "";
        $this->align(/*REF*/ $str, 20, $test->getName() . ' perfs: ' );

        $this->align(/*REF*/ $str, 70, '  time = ' . $duration . ' ticks' );

        $memUsageDiff = $this->getMemUsage() - $this->startMemUsage;
        $this->align(/*REF*/ $str, 100, '  memdiff = ' . (int) ( ceil( $memUsageDiff / 1024 ) ) . ' kb' );

        $str .= "\n";
        ZsTestLogger::say($str);
    }

    private function startLine() {
        $this->curCol = 0;
    }

    private function align(&$str, $col, $msg) {
        while ($this->curCol < $col) {
            $str .= ' ';
            $this->curCol ++;
        }
        $str .= $msg;
        $this->curCol += strlen($msg);
    }

    private static function getProcessTime() {
        $times = posix_times();
        $time = $times['utime'];
        //echo 'T:' . $time;
        return $time;
    }

    private static function getMemUsage() {
        return memory_get_usage();
    }
}

class YTestLeaksTestListener extends YTestBaseTestListener {

    private $memUsage;
    private $realMemUsage;

    public function startTest(PHPUnit_Framework_Test $test) {
        $this->memUsage = memory_get_usage();
        $this->realMemUsage = memory_get_usage(true);
    }

    public function endTest(PHPUnit_Framework_Test $test, $time) {
        $memUsage = memory_get_usage();
        $realMemUsage = memory_get_usage(true);
        echo str_repeat(' ', 73) . "mem usage: "  . ceil($memUsage / 1024)         . " kb";
        echo str_repeat(' ', 4) . "diff: "       . ceil(($memUsage - $this->memUsage) / 1024)         . " kb\n";
        echo str_repeat(' ', 73) . "real usage: " . ceil($realMemUsage / 1024)         . " kb";
        echo str_repeat(' ', 4) . "diff: "       . ceil(($realMemUsage - $this->realMemUsage) / 1024) . " kb\n";
    }
}

class YTestRunner extends PHPUnit_TextUI_TestRunner {

    /**
     * override TestResult
     */
    protected function createTestResult() {
        $result = new PHPUnit_Framework_TestResult;
        if (YTestOptions::$progress || YTestOptions::$immediateFeedback) {
            if (YTestOptions::$immediateFeedback) {
                echo "z_tests_runner: immediate feedback on failure/error enabled\n";
            } else if (YTestOptions::$progress) {
                echo "z_tests_runner: progress info enabled\n";
            }
            $result->addListener(new YTestProgressTestListener);
        }
        if (YTestOptions::$perfs) {
            echo "z_tests_runner: performance info enabled\n";
            $result->addListener(new YTestPerfListener);
        }
        if (YTestOptions::$leaks) {
            echo "z_tests_runner: leaks enabled\n";
            $result->addListener(new YTestListener);
        }

        return $result;
    }

}

class YTestTextUICommand extends PHPUnit_TextUI_Command {

    /**
     * Factory to create the test runner.
     */
    protected function createTestRunner($loader) {
        return new YTestRunner($loader);
    }
}

define('PHPUnit_MAIN_METHOD', 'ZsTextUICommand::main');

$args = array();
foreach ($_SERVER['argv'] as $arg) {
    switch ($arg) {
        case "--if":
            YTestOptions::$immediateFeedback = true;
            break;
        case "--progress":
            YTestOptions::$progress = true;
            break;
        case "--leaks":
            YTestOptions::$leaks = true;
            break;
        case "--perfs":
            YTestOptions::$perfs = true;
            break;
        case (substr($arg, 0, 9) == "--repeat="):
            YTestOptions::$repeat = (int) substr($arg, 9);
            break;
        default:
            $args[] = $arg;
            break;
    }
}

if (YTestOptions::$repeat === null) {
    $command = new YTestTextUICommand;
    $command->run($args);
} else {
    echo "\n" . YTestOptions::$repeat . " repetitions scheduled.\n";
    for ($rep = 1; $rep <= YTestOptions::$repeat; $rep++) {
        echo "\n-- Repetition $rep --\n";
        $command = new YTestTextUICommand;
        $command->run($args, false /*no exit*/);
    }
}
