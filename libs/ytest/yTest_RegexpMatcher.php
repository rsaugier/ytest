<?php

/**
 * Can be used as an expected array value in yTest_CustomTestCase::assertArrayHasValues()
 */
class yTest_RegexpMatcher {

    private $regexps = null;

    /**
     * @param string One PCRE regexp string (including delimiters).
     */
    public function __construct() {
        $regexps = func_get_args();
        if (is_array($regexps)) {
            foreach ($regexps as $regexp) {
                if (!is_string($regexp)) {
                    throw yTest_Exception::invalid('regexp', $regexp);
                }
            }
            $this->regexps = $regexps;
        } else {
            if (!is_string($regexps)) {
                throw yTest_Exception::invalid('regexp', $regexps);
            }
            $this->regexps = array($regexps);
        }
    }

    public function match($testCase, $actual, $msg = '') {
        foreach ($this->regexps as $regexp) {
            $testCase->assertMatch($regexp, $actual, $msg);
        }
    }

    public function notMatch($testCase, $actual, $msg = '') {
        foreach ($this->regexps as $regexp) {
            $testCase->assertMatch($regexp, $actual, $msg);
        }
    }

};