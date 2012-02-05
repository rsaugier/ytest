<?php

class yTest_BackupStaticProperty extends yTest_AbstractCodeChange {
    private $className;
    private $propertyName;
    private $backup;
    private $isPublic;

    public function __construct($className, $propertyName, $isPublic = true) {
        $this->className = $className;
        $this->propertyName = $propertyName;
        $this->isPublic = $isPublic;
    }

    public function __toString() {
        return "backup static property $this->className::$this->propertyName";
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
    }

    public function undo() {
        $this->setValue($this->backup);
    }
};
