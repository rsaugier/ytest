<?php

// Wrapper for DOM with methods much helpful for tests.
// Also includes some static helpers.
class yTest_Dom {

    // Private
    ////////////////////////////////////////////////////////////

    private $testCase;
    private $dom;
    private $domxpath;

    private function __construct($testCase, $html) {
        $this->testCase = $testCase;
        $this->doc = PHPUnit_Util_XML::load($html, true);
        $this->domxpath = new DOMXPath($this->doc);
    }

    // Public static methods
    ////////////////////////////////////////////////////////////

    // Factory: use this to create an instance.
    public static function fromHtml($testCase, $html) {
        if (!($testCase instanceof PHPUnit_Framework_TestCase)) {
            throw yTest_Exception::invalid('PHPUnit_Framework_TestCase', $testCase);
        }
        if (!is_string($html)) {
            throw yTest_Exception::invalid('string', $html);
        }
        return new yTest_Dom($testCase, $html);
    }

    public static function xpath($testCase, $html, $xpath, $howManyNodes = null) {
        if (!is_string($xpath)) {
            throw yTest_Exception::invalid('string', $xpath);
        }
        return self::fromHtml($testCase, $html)->htmlFromXpath($xpath, $howManyNodes);
    }

    public static function htmlFromNodeList($domNodeList, $howManyNodes = null) {
        $doc = new DOMDocument();
        $len = $domNodeList->length;
        if (is_numeric($howManyNodes)) {
            $len = min($len, $howManyNodes);
        }
        for ($i = 0; $i < $howManyNodes; ++$i) {
            $doc->appendChild( $doc->importNode($domNodeList->item($i), true));
        }
        return $doc->saveHTML();
    }

    public static function xmlFromNode($domNode) {
        $doc = new DOMDocument();
        $doc->appendChild($doc->importNode($domNode, true));
        return $doc->saveXML();
    }

    // Public instance methods
    ////////////////////////////////////////////////////////////

    public function nodeListFromXpath($xpath) {
        if (!is_string($xpath)) {
            throw yTest_Exception::invalid('string', $xpath);
        }
        $nodeList = $this->domxpath->evaluate($xpath);
        $this->testCase->assertType('DOMNodeList', $nodeList);
        return $nodeList;
    }

    public function htmlFromXpath($xpath, $howManyNodes = null) {
        $nodeList = $this->nodeListFromXpath($xpath);
        return self::htmlFromNodeList($nodeList, $howManyNodes);
    }
};

