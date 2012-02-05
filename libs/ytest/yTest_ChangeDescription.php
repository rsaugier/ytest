<?php

abstract class yTest_ChangeDescription {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function getTop() {
        return $this->container;
    }

    public function __toString() {
        return get_class($this->container).'>'.get_class($this).':';
    }

    public function isExtended() {
        return false;
    }
}
