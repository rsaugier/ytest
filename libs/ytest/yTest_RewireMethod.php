<?php

$yTest__orig_instance = null;

function yTest_originalInstance() {
    global $yTest__orig_instance;
    return $yTest__orig_instance;
}

class yTest_RewireMethod extends yTest_AbstractCodeChange {
    const REWIRE_MODE_FOR_ALL_INSTANCES = 1;
    const REWIRE_MODE_FOR_SPECIFIC_INSTANCE = 2;

    private $rewireMode;

    private $instance;
    private $className;
    private $methodName;

    private $delegateObject;
    private $delegateClassName;
    private $delegateMethodName;

    public static function createForAllInstances($className, $methodName, $delegateObject, $delegateMethodName = null) {
        return new self(self::REWIRE_MODE_FOR_ALL_INSTANCES, null, $className, $methodName, $delegateObject, $delegateMethodName);
    }

    public static function createForSpecificInstance($instance, $methodName, $delegateObject, $delegateMethodName = null) {
        return new self(self::REWIRE_MODE_FOR_SPECIFIC_INSTANCE, $instance, get_class($instance), $methodName, $delegateObject, $delegateMethodName);
    }

    private function __construct($rewireMode, $instance, $className, $methodName, $delegateObject, $delegateMethodName = null) {
        $this->rewireMode = $rewireMode;

        if ($delegateMethodName === null) {
            $delegateMethodName = $methodName;
        }

        $this->instance = $instance;
        $this->methodName = $methodName;
        $this->className = $className;

        if (! is_object($delegateObject) ) {
            throw yTest_Exception::badDelegateObject($delegateObject, $methodName);
        }

        $this->delegateObject = $delegateObject;
        $this->delegateMethodName = $delegateMethodName;
        $this->delegateClassName = get_class($delegateObject);
    }

    public function __toString() {
        return 'rewire method ' . $this->className . '::' . $this->methodName
            . '(...) to ' . $this->delegateClassName . '::' . $this->delegateMethodName
            . '(...) of given delegate instance';
    }

    public function apply() {
        $params = yTest_Signature::getParamsDecl($this->methodName, $this->className);

        if (yTest_Reflection::isStaticMethod($this->className, $this->methodName)) {
            $code = '$x = yTest_CallsRouter::$singleton->getDelegateObjectForStaticMethod("' . $this->className . '", "' . $this->methodName . '");' . "\n";
            $code .= 'return $x->' . $this->delegateMethodName . '(' .
                yTest_Signature::getArgsForSimpleCall($this->methodName, $this->className) . ');';

            yTest_CallsRouter::$singleton->onClassMethodRewire($this->className, $this->methodName, $this->delegateObject);
        } else {
            $code = 'global $yTest__orig_instance; $yTest__orig_instance = $this;' . "\n";
            $code .= '$x = yTest_CallsRouter::$singleton->getDelegateObjectForInstanceMethod($this, "' . $this->methodName . '");' . "\n";
            $code .= 'if($x === null) {' . "\n";
            $code .= '    return $this->' . yTest_Reflection::getOriginalMethodName($this->methodName)
                . '(' . yTest_Signature::getArgsForSimpleCall($this->methodName, $this->className) . ');' . "\n";
            $code .= "}\n";
            $code .= "else {\n";
            $code .= '    return $x->' . $this->delegateMethodName . '(' .
                yTest_Signature::getArgsForSimpleCall($this->methodName, $this->className) . ');' . "\n";
            $code .= "}\n";

            switch ($this->rewireMode) {
                case self::REWIRE_MODE_FOR_ALL_INSTANCES:
                    yTest_CallsRouter::$singleton->onClassMethodRewire($this->className, $this->methodName, $this->delegateObject);
                    break;
                case self::REWIRE_MODE_FOR_SPECIFIC_INSTANCE:
                    yTest_CallsRouter::$singleton->onInstanceMethodRewire($this->instance, $this->methodName, $this->delegateObject);
                    break;
                default:
                    yTest_error("invalid rewireMode");
                    break;
            }
        }

        yTest_debugCC(
            "PARAMS: $params",
            "CODE: $code");

        $flags = yTest_Reflection::getRunkitMethodFlags($this->className, $this->methodName);
        yTest_Reflection::replaceMethod($this->className, $this->methodName, $params, $code, $flags);

        //yTest_CallsRouter::instance()->dump();
    }

    public function undo() {
        yTest_Reflection::restoreMethod($this->className, $this->methodName);

        switch ($this->rewireMode) {
            case self::REWIRE_MODE_FOR_ALL_INSTANCES:
                yTest_CallsRouter::$singleton->onUndoClassMethodRewire($this->className, $this->methodName, $this->delegateObject);
                break;
            case self::REWIRE_MODE_FOR_SPECIFIC_INSTANCE:
                yTest_CallsRouter::$singleton->onUndoInstanceMethodRewire($this->instance, $this->methodName, $this->delegateObject);
                break;
            default:
                yTest_error("invalid rewireMode");
                break;
        }

        //yTest_CallsRouter::instance()->dump();
    }
};

