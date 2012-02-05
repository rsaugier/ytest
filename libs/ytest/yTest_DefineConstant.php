<?php

// [RS] Needs runkit
class yTest_DefineConstant extends yTest_AbstractCodeChange {
    private $className = null;
    private $constName;
    private $value;

    public static function createConstantDefine($constName, $value) {
        return new self($constName, $value, null);
    }

    public static function createClassConstantDefine($className, $constName, $value) {
        return new self($constName, $value, $className);
    }

    private function __construct($constName, $value, $className = null) {
        $this->constName = $constName;
        $this->className = $className;
        $this->value = $value;
    }

    public function __toString() {
        if ($this->className === null) {
            return "define constant $this->constName to " . var_export($value, true);
        } else {
            return "define class constant $this->className::$this->constName to " . var_export($value, true);
        }
    }

    public function apply() {
        yTest_Reflection::defineConstant($this->className, $this->constName, $this->value);
    }

    public function undo() {
        yTest_Reflection::undefineConstant($this->className, $this->constName);
    }
};

?>