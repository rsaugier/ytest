<?php

// [RS] Needs runkit.
class yTest_AddPublicAccessors extends yTest_AbstractCodeChange {
    private $className;
    private $propertyName;
    private $getterName;
    private $setterName;

    public static function getGetterName($className, $propertyName) {
        if (yTest_Reflection::isStaticProperty($className, $propertyName)) {
            $name = self::COMMON_PREFIX . "getStatic_" . $propertyName;
        } else {
            $name = self::COMMON_PREFIX . "get_" . $propertyName;
        }

        return strtolower($name);
    }

    public static function getSetterName($className, $propertyName) {
        if (yTest_Reflection::isStaticProperty($className, $propertyName)) {
            $name = self::COMMON_PREFIX . "setStatic_" . $propertyName;
        } else {
            $name = self::COMMON_PREFIX . "set_" . $propertyName;
        }

        return strtolower($name);
    }

    public function __construct($className, $propertyName) {
        $this->className = $className;
        $this->propertyName = $propertyName;
        $this->getterName = self::getGetterName($className, $propertyName);
        $this->setterName = self::getSetterName($className, $propertyName);
    }

    public function __toString() {
        return 'add public accessors ' . $this->getterName . '() and ' . $this->setterName . '($value)';
    }

    public function apply() {
        $runkitFlags = RUNKIT_ACC_PUBLIC;
        if (yTest_Reflection::isStaticProperty($this->className, $this->propertyName)) {
            $setter = 'self::$' . $this->propertyName . ' = $value;';
            $getter = 'return self::$' . $this->propertyName . ';';
            $runkitFlags |= RUNKIT_ACC_STATIC;
        } else {
            $setter = '$this->' . $this->propertyName . ' = $value;';
            $getter = 'return $this->' . $this->propertyName . ';';
        }
        runkit_method_add(
            $this->className,
            $this->setterName,
            '$value',
            $setter,
            $runkitFlags
        );
        runkit_method_add(
            $this->className,
            $this->getterName,
            '',
            $getter,
            $runkitFlags
        );
    }

    public function undo() {
        runkit_method_remove($this->className, $this->getterName);
        runkit_method_remove($this->className, $this->setterName);
    }
};


?>