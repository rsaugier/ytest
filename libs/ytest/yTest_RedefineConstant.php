<?php

// [RS] Needs runkit
class yTest_RedefineConstant extends yTest_AbstractCodeChange {
    private $className = null;
    private $constName;
    private $value;
    private $backup;

    public static function createConstantRedefine($constName, $value) {
        return new self($constName, $value, null);
    }

    public static function createClassConstantRedefine($className, $constName, $value) {
        return new self($constName, $value, $className);
    }

    private function __construct($constName, $value, $className = null) {
        $this->constName = $constName;
        $this->className = $className;
        $this->value = $value;
    }

    public function __toString() {
        if ($this->className === null) {
            return "redefine constant $this->constName to " . var_export($this->value, true);
        } else {
            return "redefine class constant $this->className::$this->constName to " . var_export($this->value, true);
        }
    }

    private function getConstantValue() {
        if ($this->className === null) {
            $consts = get_defined_constants();
            if (!array_key_exists($this->constName, $consts)) {
                throw yTest_Exception::noSuch("constant", $this->constName);
            }
            return $consts[$this->constName];
        } else {
            $refl = new ReflectionClass($this->className);
            return $refl->getConstant($this->constName);
        }
    }

    public function apply() {
        $this->backup = $this->getConstantValue();
        yTest_Reflection::redefineConstant($this->className, $this->constName, $this->value);
    }

    public function undo() {
        yTest_Reflection::redefineConstant($this->className, $this->constName, $this->backup);
    }
};

?>
