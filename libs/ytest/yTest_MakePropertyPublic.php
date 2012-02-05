<?php

// [RS] Needs PHP 5.3
class yTest_MakePropertyPublic extends yTest_AbstractCodeChange {
    private $className;
    private $propertyName;
    private $undoNeeded = false;
    private $ref = null;

    public function __construct($className, $propertyName) {
        $this->className = $className;
        $this->propertyName = $propertyName;
    }

    public function __toString() {
        return "make $this->className::\$$this->propertyName public";
    }

    public function apply() {
        $this->ref = new ReflectionProperty($this->className, $this->propertyName);
        if ($this->ref->isPrivate() || $this->ref->isProtected()) {
            $this->ref->setAccessible(true);
            $this->undoNeeded = true;
        }
    }

    public function undo() {
        if ($this->undoNeeded) {
            $this->ref->setAccessible(false);
        }
    }
};


?>