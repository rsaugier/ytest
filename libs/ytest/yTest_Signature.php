<?php

// [RS] Helpers to deal with method & function signatures, using PHP5's reflection.
class yTest_Signature {
    /**
     * Returns a string like this:
     * "&$param1, &$param2, $param3, $param4 = 15"
     *
     * @param $methodName May be a function name too
     * @param $className May be null if methodName is a function name
     */
    public static function getParamsDecl($methodName, $className = null) {
        $parts = array();
        $infos = self::getParamsInfo($methodName, $className);
        foreach ($infos as $info) {
            list($name, $byRef, $optional, $defaultVal) = $info;
            $param = ($byRef ? '&' : '') . '$' . $name;
            if ($optional) {
                $param .= ' = ' . var_export($defaultVal, true);
            }
            $parts[] = $param;
        }
        return implode(", ", $parts);
    }

    /**
     * Returns a string like this:
     * "$param1, $param2, $param3, $param4"
     *
     * @param $methodName May be a function name too
     * @param $className May be null if methodName is a function name
     */
    public static function getArgsForSimpleCall($methodName, $className = null) {
        $parts = array();
        $infos = self::getParamsInfo($methodName, $className);
        foreach ($infos as $info) {
            $parts[] = '$' . $info[0];
        }
        return implode(", ", $parts);
    }

    private static function getParamsInfo($methodName, $className) {
        $infos = array();

        if ($className === null) {
            $ref = new ReflectionFunction($methodName);
        } else {
            $ref = new ReflectionMethod($className, $methodName);
        }

        foreach ($ref->getParameters() as $param) {
            $pos = $param->getPosition();
            $name = $param->getName();
            $name = trim($name, '"');
            $byRef = $param->isPassedByReference();
            $optional = $param->isOptional();
            if ($optional) {
                if (! $param->isDefaultValueAvailable() ) {
                    if ( $className !== null ) {
                        yTest_error(
                            "yTest internal error: yTest_Signature::getParamsInfo() failed to find default parameter value of param '$name' of $className::$methodName");
                    }
                    $defaultVal = null;
                } else {
                    $defaultVal = $param->getDefaultValue();
                }
            } else {
                $defaultVal = null;
            }
            $infos[$pos] = array($name, $byRef, $optional, $defaultVal);
        }
        return $infos;
    }
};

?>
