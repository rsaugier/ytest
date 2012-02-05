<?php

class yTest_RewireFunction extends yTest_AbstractCodeChange {
    private $functionName;

    private $delegateObject;
    private $delegateClassName;
    private $delegateMethodName;

    public function __construct($functionName, $delegateObject, $delegateMethodName = null) {
        $this->functionName = $functionName;

        if (! is_object($delegateObject) ) {
            throw yTest_Exception::badDelegateObject($delegateObject, $functionName);
        }

        $this->delegateObject = $delegateObject;

        if ($delegateMethodName === null) {
            $delegateMethodName = $functionName;
        }
        $this->delegateMethodName = $delegateMethodName;
        $this->delegateClassName = get_class($delegateObject);
    }

    public function __toString() {
        return 'rewire function ' . $this->functionName . '(...) to ' . $this->delegateClassName . '::' . $this->delegateMethodName
            . '(...) of given delegate instance';
    }

    public function apply() {
        $params = yTest_Signature::getParamsDecl($this->functionName);

        $code = '$x = yTest_CallsRouter::$singleton->getDelegateObjectForFunction("' . $this->functionName . '");' . "\n";
        $code .= 'return $x->' . $this->delegateMethodName . '(' .
            yTest_Signature::getArgsForSimpleCall($this->functionName) . ');';

        yTest_CallsRouter::$singleton->onFunctionRewire($this->functionName, $this->delegateObject);

        yTest_debugCC(
            "PARAMS: $params",
            "CODE: $code");

        yTest_Reflection::replaceFunction($this->functionName, $params, $code);

        //yTest_CallsRouter::instance()->dump();
    }

    public function undo() {
        yTest_Reflection::restoreFunction($this->functionName);

        //yTest_CallsRouter::instance()->dump();
    }
};

