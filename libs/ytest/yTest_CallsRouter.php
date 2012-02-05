<?php

// A global singleton that keeps a trace of all current "methods rewiring"
class yTest_CallsRouter {
    // Fields
    ////////////////////////////////////////////////////////////

    // **PUBLIC** singleton variable (accessed directly by rewired methods!!!)
    public static $singleton = null;

    // array( objectHash => array( methodName => delegateObject ) )
    private $objectHashToRewireTable = array();

    // array( className => array( methodName => delegateObject ) )
    private $classNameToRewireTable = array();

    // array( className => array ( methodName => true ) )
    private $classNameToRewiredMethods = array();

    // array( funcName => delegateObject )
    private $funcNameToDelegate = array();

    // Singleton stuff
    ////////////////////////////////////////////////////////////

    // Singleton getter
    public static function instance() {
        if (self::$singleton === null) {
            yTest_error("yTest_CallsRouter singleton must be initialized manually before access");
        }
        return self::$singleton;
    }

    // Initialization function
    public static function initialize() {
        if (self::$singleton !== null) {
            yTest_error("yTest_CallsRouter singleton already initialized");
        }
        self::$singleton = new yTest_CallsRouter();
    }

    private function __construct() {
    }

    // Internal methods
    ////////////////////////////////////////////////////////////

    private function flagMethodAsRewired($className, $methodName, $rewired) {
        if ($rewired) {
            if (!array_key_exists($className, $this->classNameToRewiredMethods)) {
                $this->classNameToRewiredMethods[$className] = array();
            }
            $this->classNameToRewiredMethods[$className][$methodName] = true;
        } else {
            if (array_key_exists($className, $this->classNameToRewiredMethods)) {
                $methods =& $this->classNameToRewiredMethods[$className];
                unset( $methods[$methodName] );
                if (count($methods) == 0) {
                    unset($this->classNameToRewiredMethods[$className]);
                }
            } else {
                yTest_error("flagMethodAsRewired() internal error");
            }
        }
    }

    // Event handlers (called by yTest_RewireMethod)
    ////////////////////////////////////////////////////////////

    public function onInstanceMethodRewire($instance, $methodName, $delegateObject) {
        $className = get_class($instance);

        yTest_debugCC("onInstanceMethodRewire $className::$methodName");

        $this->flagMethodAsRewired($className, $methodName, true);

        $id = spl_object_hash($instance);
        if (!array_key_exists($id, $this->objectHashToRewireTable)) {
            $this->objectHashToRewireTable[$id] = array();
        }
        $this->objectHashToRewireTable[$id][$methodName] = $delegateObject;
    }

    public function onClassMethodRewire($className, $methodName, $delegateObject) {
        yTest_debugCC("onClassMethodRewire $className::$methodName");

        $this->flagMethodAsRewired($className, $methodName, true);

        if (!array_key_exists($className, $this->classNameToRewireTable)) {
            $this->classNameToRewireTable[$className] = array();
        }
        $this->classNameToRewireTable[$className][$methodName] = $delegateObject;
    }

    public function onFunctionRewire($functionName, $delegateObject) {

        yTest_debugCC("onFunctionRewire $functionName");
        $this->funcNameToDelegate[$functionName] = $delegateObject;
    }

    public function onUndoInstanceMethodRewire($instance, $methodName) {
        $className = get_class($instance);
        $this->flagMethodAsRewired($className, $methodName, false);

        $id = spl_object_hash($instance);
        if (!array_key_exists($id, $this->objectHashToRewireTable)) {
            yTest_error("onUndoInstanceMethodRewire() internal error");
        } else {
            $table =& $this->objectHashToRewireTable[$id];
            unset( $table[$methodName] );
            if (count($table) == 0) {
                unset( $this->objectHashToRewireTable[$id] );
            }
        }
    }

    public function onUndoClassMethodRewire($className, $methodName) {
        $this->flagMethodAsRewired($className, $methodName, false);

        if (!array_key_exists($className, $this->classNameToRewireTable)) {
            yTest_error("onUndoClassMethodRewire() internal error");
        } else {
            $table =& $this->classNameToRewireTable[$className];
            unset( $table[$methodName] );
            if (count($table) == 0) {
                unset( $this->classNameToRewireTable[$className] );
            }
        }
    }

    public function onUndoFunctionRewire($functionName) {
        if (!array_key_exists($functionName)) {
            yTest_error("onUndoFunctionRewire() internal error");
        }
        unset( $this->funcNameToDelegate[$functionName] );
    }

    // Public state inspection methods
    ////////////////////////////////////////////////////////////

    public function isMethodRewired($className, $methodName) {
        if (!array_key_exists($className, $this->classNameToRewiredMethods)) {
            return false;
        }
        $methods = $this->classNameToRewiredMethods[$className];

        return array_key_exists($methodName, $methods);
    }

    public function getDelegateObjectForInstanceMethod($instance, $methodName) {
        $hash = spl_object_hash($instance);

        if (array_key_exists($hash, $this->objectHashToRewireTable)) {
            if (array_key_exists($methodName, $this->objectHashToRewireTable[$hash])) {
                return $this->objectHashToRewireTable[$hash][$methodName];
            }
        }

        $className = get_class($instance);

        return $this->getDelegateObjectForStaticMethod($className, $methodName, false);
    }

    public function getDelegateObjectForStaticMethod($className, $methodName, $dieOnError = true) {
        if (array_key_exists($className, $this->classNameToRewireTable)) {
            if (array_key_exists($methodName, $this->classNameToRewireTable[$className])) {
                return $this->classNameToRewireTable[$className][$methodName];
            }
        }

        $parentClassName = get_parent_class($className);
        if ($parentClassName !== false) {
            return $this->getDelegateObjectForStaticMethod($parentClassName, $methodName, $dieOnError);
        } else {
            if ($dieOnError) {
                // This should never happen, a static method's code is either 'original', or has to be correctly rewired to a delegate...
                yTest_error("getDelegateObjectForStaticMethod() failed for className=$className, method=$methodName");
            } else {
                return null;
            }
        }
    }

    public function getDelegateObjectForFunction($functionName) {
        if (!array_key_exists($functionName, $this->funcNameToDelegate)) {
            yTest_error("getDelegateObjectForFunction() failed for functionName=$functionName");
        }
        return $this->funcNameToDelegate[$functionName];
    }

    // Debug stuff
    ////////////////////////////////////////////////////////////

    public function dump() {
        echo "\nyTest_CallsRouter dump():\n<<<\n";
        echo "Rewired methods:\n";
        foreach ($this->classNameToRewiredMethods as $className => $methods) {
            foreach ($methods as $method => $foo) {
                echo '  ' . $className . '::' . $method . "\n";
                if (array_key_exists($className, $this->classNameToRewireTable)
                    && array_key_exists($method, $this->classNameToRewireTable[$className]) ) {
                    $obj = $this->classNameToRewireTable[$className][$method];
                    echo '    all -> instance of \'' . get_class($obj) . '\' (hash=' . spl_object_hash($obj) . ")\n";
                }
                foreach ($this->objectHashToRewireTable as $inst => $meths) {
                    if (array_key_exists($method, $meths)) {
                        $obj = $meths[$method];
                        echo '    instance of hash=' . $inst . ' -> instance of ' . get_class($obj) . ' (hash=' . spl_object_hash($obj) . ")\n";
                    }
                }
            }
        }
        echo "Rewired functions:\n";
        foreach ($this->funcNameToDelegate as $funcName => $obj) {
            echo '  ' . $funcName . ' -> instance of \'' .get_class($obj) . '\' (hash=' . spl_object_hash($obj) . ")\n";
        }
        echo ">>>\n";
    }
};
