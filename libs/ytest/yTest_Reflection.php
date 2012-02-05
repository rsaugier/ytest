<?php

// Reflection/runkit helpers
class yTest_Reflection {

    public static function getProperty($className, $propertyName) {
        $ref = new ReflectionClass($className);
        try {
            $prop = $ref->getProperty($propertyName);
            return $prop;
        } catch (ReflectionException $e) {
            $parent = $ref->getParentClass();
            if ($parent === false) {
                yTest_assert('Could not get parent');
            } else {
                $parentName = $parent->getName();
                return self::getProperty($parentName, $propertyName);
            }
        }
    }

    public static function isStaticMethod($className, $methodName) {
        $ref = new ReflectionMethod($className, $methodName);
        return $ref->isStatic();
    }

    public static function isStaticProperty($className, $propertyName) {
        $prop = self::getProperty($className, $propertyName);
        return $prop->isStatic();
    }

    public static function isPropertyPublic($className, $propertyName) {
        $prop = self::getProperty($className, $propertyName);
        return $prop->isPublic();
    }

    public static function getRunkitMethodFlags($className, $methodName) {
        $ref = new ReflectionMethod($className, $methodName);
        $flags = 0;
        if ($ref->isPrivate()) {
            $flags |= RUNKIT_ACC_PRIVATE;
        } elseif ($ref->isProtected()) {
            $flags |= RUNKIT_ACC_PROTECTED;
        } else {
            $flags |= RUNKIT_ACC_PUBLIC;
        }
        if ($ref->isStatic()) {
            $flags |= RUNKIT_ACC_STATIC;
        }
        return $flags;
    }

    const ORIGINAL_PREFIX = 'orig_';

    public static function flagsToString($flags) {
        $flagsAsStr = '';
        if ($flags & RUNKIT_ACC_PUBLIC) {
            $flagsAsStr = 'public';
        } elseif ($flags & RUNKIT_ACC_PROTECTED) {
            $flagsAsStr = 'protected';
        } elseif ($flags & RUNKIT_ACC_PRIVATE) {
            $flagsAsStr = 'private';
        }
        if ($flags & RUNKIT_ACC_STATIC) {
            $flagsAsStr .= ' static';
        }
        return $flagsAsStr;
    }

    public static function replaceMethod($className, $methodName, $args, $code, $flags) {
        yTest_debugCC("replaceMethod $className::$methodName($args) with code (flags = " . self::flagsToString($flags) . "):\n" . $code);

        $res = runkit_method_rename($className, $methodName, strtolower(self::getOriginalMethodName($methodName)));
        yTest_assert($res);
        $res = runkit_method_add($className, strtolower($methodName), $args, $code, $flags);
        yTest_assert($res);
        //var_dump(get_class_methods($className));
    }

    public static function getOriginalMethodName($methodName) {
        return yTest_AbstractCodeChange::COMMON_PREFIX . self::ORIGINAL_PREFIX . $methodName;
    }

    public static function restoreMethod($className, $methodName) {
        yTest_debugCC("restoreMethod $className::$methodName");

        $res = runkit_method_remove($className, strtolower($methodName));
        yTest_assert($res);
        $res = runkit_method_rename($className, strtolower(self::getOriginalMethodName($methodName)), strtolower($methodName));
        yTest_assert($res);
        //var_dump(get_class_methods($className));
    }

    public static function replaceFunction($functionName, $args, $code) {
        yTest_debugCC("replaceFunction $functionName($args) with code:\n" . $code);

        // [RS] runkit will crash if you try to call runkit_function_rename()
        $res = runkit_function_copy($functionName, strtolower(self::getOriginalFunctionName($functionName)));
        yTest_assert($res);
        $res = runkit_function_remove($functionName);
        yTest_assert($res);
        $res = runkit_function_add(strtolower($functionName), $args, $code);
        yTest_assert($res);
    }

    public static function getOriginalFunctionName($functionName) {
        return yTest_AbstractCodeChange::COMMON_PREFIX . self::ORIGINAL_PREFIX . $functionName;
    }

    public static function restoreFunction($functionName) {
        yTest_debugCC("restoreFunction $functionName");

        // [RS] runkit will crash if you try to call runkit_function_rename()
        $res = runkit_function_remove(strtolower($functionName));
        yTest_assert($res);
        $res = runkit_function_copy(strtolower(self::getOriginalFunctionName($functionName)), strtolower($functionName));
        yTest_assert($res);
        $res = runkit_function_remove(strtolower(self::getOriginalFunctionName($functionName)));
        yTest_assert($res);
    }

    public static function defineConstant($className, $constName, $value) {
        $runkitName = ($className === null ? "" : ($className . "::") ). $constName;
        yTest_debugCC("defineConstant $runkitName to value " . var_export($value, true));

        $res = runkit_constant_add($runkitName, $value);
        yTest_assert($res);
    }

    public static function undefineConstant($className, $constName) {
        $runkitName = ($className === null ? "" : ($className . "::") ). $constName;
        yTest_debugCC("undefineConstant $runkitName");

        $res = runkit_constant_remove($runkitName);
        yTest_assert($res);
    }

    public static function redefineConstant($className, $constName, $value) {
        $runkitName = ($className === null ? "" : ($className . "::") ). $constName;
        yTest_debugCC("redefineConstant $runkitName to value " . var_export($value, true));

        $res = runkit_constant_redefine($runkitName, $value);
        yTest_assert($res);
    }
};
