<?php

class yTest_SetStaticProperty extends yTest_AbstractCodeChange {
    private $className;
    private $propertyName;
    private $value;
    private $backup;
    private $isPublic;

    public function __construct($className, $propertyName, $value, $isPublic = true) {
        $this->className = $className;
        $this->propertyName = $propertyName;
        $this->value = $value;
        $this->isPublic = $isPublic;
    }

    public function __toString() {
        return "set static property $this->className::$this->propertyName to " . var_export($this->value, true);
    }

    private function getValue() {
        if ($this->isPublic) {
            $refl = new ReflectionClass($this->className);
            $val = $refl->getStaticPropertyValue($this->propertyName);
        } else {
            $val = call_user_func(
                array($this->className, yTest_AddPublicAccessors::getGetterName($this->className, $this->propertyName)));
        }
        yTest_debugCC('getValue ' . $this->className . '::' . $this->propertyName . ' = ' . var_export($val, true));
        return $val;
    }

    private function setValue(&$val) {
        yTest_debugCC('setValue ' . $this->className . '::' . $this->propertyName . ' = ' . var_export($val, true));
        if ($this->isPublic) {
            $refl = new ReflectionClass($this->className);
            $refl->setStaticPropertyValue($this->propertyName, $val);
        } else {
            call_user_func(
                array($this->className, yTest_AddPublicAccessors::getSetterName($this->className, $this->propertyName)),
                $val);
        }
    }

    public function apply() {
        $this->backup = $this->getValue();
        $this->setValue($this->value);
    }

    public function undo() {
        $this->setValue($this->backup);
    }
};
