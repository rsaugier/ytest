<?php

// [RS] Needs runkit.
class yTest_AddPublicDelegate extends yTest_AbstractCodeChange {
    const PREFIX = 'call_';

    private $className;
    private $methodName;
    private $delegateName;

    public static function getPublicDelegateName($className, $methodName) {
        return strtolower(self::COMMON_PREFIX . self::PREFIX . $methodName);
    }

    public function __construct($className, $methodName) {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->delegateName = self::getPublicDelegateName($className, $methodName);
    }

    public function __toString() {
        return 'add public method delegate ' . $this->delegateName . '(...) to method ' . $this->methodName . '(...)';
    }

    public function apply() {
        if (method_exists($this->className, yTest_Reflection::getOriginalMethodName($this->methodName))) {
            throw new yTest_Exception('trying to create public delegate (letMeCall) on the already rewired method ' . $this->className . '::' . $this->methodName);
        }

        $params = yTest_Signature::getParamsDecl($this->methodName, $this->className);

        $runkitFlags = RUNKIT_ACC_PUBLIC;
        if (yTest_Reflection::isStaticMethod($this->className, $this->methodName)) {
            $code = 'return self::' . $this->methodName . '(' .
                yTest_Signature::getArgsForSimpleCall($this->methodName, $this->className) . ');';
            $runkitFlags |= RUNKIT_ACC_STATIC;
        } else {
            $code = 'return $this->' . $this->methodName . '(' .
                yTest_Signature::getArgsForSimpleCall($this->methodName, $this->className) . ');';
        }

        yTest_debugCC(
            "PARAMS: $params",
            "CODE: $code");

        runkit_method_add(
            $this->className,
            $this->delegateName,
            $params,
            $code,
            $runkitFlags
        );
    }

    public function undo() {
        runkit_method_remove($this->className, $this->delegateName);
    }
};

?>
