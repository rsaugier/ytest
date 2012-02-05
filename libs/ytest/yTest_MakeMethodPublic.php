<?php

class yTest_MakeMethodPublic extends yTest_AbstractCodeChange {
    private $className;
    private $methodName;
    private $undoNeeded = false;
    private $ref = null;

    public function __construct($className, $methodName) {
        $this->className = $className;
        $this->methodName = $methodName;
    }

    public function __toString() {
        return "make $this->className::$this->methodName() public";
    }

    public function apply() {
        $this->ref = new ReflectionMethod($this->className, $this->methodName);
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