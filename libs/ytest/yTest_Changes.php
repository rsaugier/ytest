<?php

abstract class yTest_Changes {
    protected $ignoreAll = false;
    protected $ignoredElements = array();
    protected $changes = array();

    abstract public static function getData($user);

    public function isIgnoreAllEnabled() {
        if ( $this->ignoreAll ) {
            if ( count($this->changes) > 0 ) {
                throw new Exception('Mixed change descriptions and "ignore all"');
            }
            return true;
        }
        return false;
    }

    public function ignoreAll() {
        $this->ignoreAll = true;
        return $this;
    }

    public function ignore($elementId) {
        $this->ignoredElements[] = $elementId;
        return $this;
    }

    public function getTop() {
        return $this;
    }

    public function addChange($change) {
        $this->changes[] = $change;
    }

    public function merge($changeObject) {
        foreach ($changeObject->changes as $change) {
            $this->addChange($change);
        }
    }
}
