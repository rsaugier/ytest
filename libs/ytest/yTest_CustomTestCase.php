<?php

abstract class yTest_CustomTestCase extends PHPUnit_Framework_TestCase {
    private $codeChanger = null;
    private $outputBufferingLevel = 0;

    protected function setUp() {
        parent::setUp();

        $this->codeChanger = new yTest_CodeChanger();
    }

    protected function tearDown() {
        if ($this->codeChanger !== null) {
            $this->codeChanger->undoAll();
            $this->codeChanger = null;
        }

        if ($this->outputBufferingLevel > 0) {
            for ($i = $this->outputBufferingLevel; $i > 0; $i--) {
                $this->stopRecordOutput();
            }
            $this->fail("stopRecordOutput() must be called as many times as recordOutput()");
        }
        parent::tearDown();
    }

    // SQL helpers
    ////////////////////////////////////////////////////////////

    public final function runSql($sqlQuery) {
        return yTest_Database::instance()->runQuery($sqlQuery);
    }

    // Visibility bypass & rewiring
    ////////////////////////////////////////////////////////////

    public final function letMeCall($className, $methodName) {
        $delName = yTest_AddPublicDelegate::getPublicDelegateName($className, $methodName);
        $ref = new ReflectionClass($className);
        if (! $ref->hasMethod($delName)) {
            return $this->addPublicDelegate($className, $methodName);
        } else {
            return null;  // TODO: find the pre-existing change & return it
        }
    }

    public final function letMeAccess($className, $propertyName) {
        $accName = yTest_AddPublicAccessors::getGetterName($className, $propertyName);
        $ref = new ReflectionClass($className);
        if (! $ref->hasMethod($accName)) {
            return $this->addPublicAccessors($className, $propertyName);
        } else {
            return null;  // TODO: find the pre-existing change & return it
        }
    }

    public final function rewireMethod($instanceOrClassName, $methodName, $delegateObject, $delegateMethodName = null) {
        if (is_string($instanceOrClassName)) {
            $change = yTest_RewireMethod::createForAllInstances(
                $instanceOrClassName, $methodName, $delegateObject, $delegateMethodName);
        } else {
            $change = yTest_RewireMethod::createForSpecificInstance(
                $instanceOrClassName, $methodName, $delegateObject, $delegateMethodName);
        }

        $this->codeChanger->enqueue($change);
        return $change;
    }

    public final function undoChange($change) {
        $this->codeChanger->undoChange($change);
    }

    public final function unwireMethod($instanceOrClassName, $methodName, $yesValue=null) {
        return $this->rewireMethod($instanceOrClassName, $methodName, yTest_Yes::instance($yesValue), "any");
    }

    public final function rewireFunction($functionName, $delegateObject, $delegateMethodName = null) {
        $change = new yTest_RewireFunction($functionName, $delegateObject, $delegateMethodName);
        $this->codeChanger->enqueue($change);
        return $change;
    }

    public final function unwireFunction($functionName, $yesValue=null) {
        return $this->rewireFunction($functionName, yTest_Yes::instance($yesValue), "any");
    }

    public final function makeMethodPublic($className, $methodName) {
        $change = new yTest_MakeMethodPublic($className, $methodName);
        $this->codeChanger->enqueue($change);
        return $change;
    }

    public final function makePropertyPublic($className, $methodName) {
        $change = new yTest_MakePropertyPublic($className, $methodName);
        $this->codeChanger->enqueue($change);
        return $change;
    }

    public final function addPublicAccessors($className, $propertyName) {
        $change = new yTest_AddPublicAccessors($className, $propertyName);
        $this->codeChanger->enqueue($change);
        return $change;
    }

    public final function addPublicDelegate($className, $methodName) {
        $change = new yTest_AddPublicDelegate($className, $methodName);
        $this->codeChanger->enqueue($change);
        return $change;
    }

    public final function setProperty($object, $propertyName, $value) {
        $className = get_class($object);
        $isPublic = yTest_Reflection::isPropertyPublic($className, $propertyName);
        if (! $isPublic) {
            $this->letMeAccess($className, $propertyName);
        }
        call_user_func(
            array($object, yTest_AddPublicAccessors::getSetterName($className, $propertyName)),
            $value);

        // [RS] PHP >= 5.3 :
        //~ $className = get_class($object);
        //~ $ref = new ReflectionProperty($className, $propertyName);
        //~ $ref->setValue($object, $value);
    }

    public final function getProperty($object, $propertyName) {
        $className = get_class($object);
        $isPublic = yTest_Reflection::isPropertyPublic($className, $propertyName);
        if (! $isPublic) {
            $this->letMeAccess($className, $propertyName);
        }
        return call_user_func(
            array($object, yTest_AddPublicAccessors::getGetterName($className, $propertyName)));

        // [RS] PHP >= 5.3 :
        //~ $className = get_class($object);
        //~ $ref = new ReflectionProperty($className, $propertyName);
        //~ return $ref->getValue($object);
    }

    public final function setStaticProperty($className, $propertyName, $value) {
        if (! yTest_Reflection::isStaticProperty($className, $propertyName) ) {
            throw yTest_Exception::noSuch("static property", "$className::$propertyName");
        }

        $isPublic = yTest_Reflection::isPropertyPublic($className, $propertyName);
        if (! $isPublic) {
            $this->letMeAccess($className, $propertyName);
        }

        $change = new yTest_SetStaticProperty($className, $propertyName, $value, $isPublic);
        $this->codeChanger->enqueue($change);

        // [RS] PHP >= 5.3 :
        //~ $ref = new ReflectionProperty($className, $propertyName);
        //~ $ref->setValue(null, $value);
    }

    public final function getStaticProperty($className, $propertyName) {
        if (! yTest_Reflection::isStaticProperty($className, $propertyName) ) {
            throw yTest_Exception::noSuch("static property", "$className::$propertyName");
        }

        $isPublic = yTest_Reflection::isPropertyPublic($className, $propertyName);
        if ($isPublic) {
            $ref = new ReflectionClass($className);
            return $ref->getStaticPropertyValue($propertyName);
        } else {
            $this->letMeAccess($className, $propertyName);
            return call_user_func(
                array($className, yTest_AddPublicAccessors::getGetterName($className, $propertyName)));
        }

        // [RS] PHP >= 5.3 :
        //~ $ref = new ReflectionProperty($className, $propertyName);
        //~ return $ref->getValue(null);
    }

    public final function backupStaticProperty($className, $propertyName) {
        if (! yTest_Reflection::isStaticProperty($className, $propertyName) ) {
            throw yTest_Exception::noSuch("static property", "$className::$propertyName");
        }

        $isPublic = yTest_Reflection::isPropertyPublic($className, $propertyName);
        if (! $isPublic) {
            $this->letMeAccess($className, $propertyName);
        }

        $change = new yTest_BackupStaticProperty($className, $propertyName, $isPublic);
        $this->codeChanger->enqueue($change);
    }

    public final function setConstant($constName, $value) {
        if (defined($constName)) {
            $change = yTest_RedefineConstant::createConstantRedefine($constName, $value);
        } else {
            $change = yTest_DefineConstant::createConstantDefine($constName, $value);
        }
        $this->codeChanger->enqueue($change);
        return $change;
    }

    public final function setClassConstant($className, $constName, $value) {
        $change = yTest_RedefineConstant::createClassConstantRedefine($className, $constName, $value);
        $this->codeChanger->enqueue($change);
        return $change;
    }



    // Output buffering
    ////////////////////////////////////////////////////////////

    public final function recordOutput() {
        if ( ob_start() === false ) {
            throw yTest_Exception('Can\'t start output buffering');
        }
        $this->outputBufferingLevel ++;
    }

    public final function stopRecordOutput() {
        $recorded = ob_get_clean();
        if ( $recorded === false ) {
            throw yTest_Exception('Can\'t stop output buffering');
        }
        $this->outputBufferingLevel --;
        if ($this->outputBufferingLevel < 0) {
            $this->fail("stopRecordOutput() called too many times!");
        }
        return $recorded;
    }

    // Mock helpers
    ////////////////////////////////////////////////////////////

    public final function getFunctionMock($functionName) {
        $newClassName = yTest_CodeGen::genClassWithSingleFuncStub($functionName);
        return $this->getMock($newClassName, array($functionName));
    }

    public final function getStaticMethodMock($className, $methodName) {
        $newClassName = yTest_CodeGen::genClassWithSingleFuncStub($methodName, $className);
        return $this->getMock($newClassName, array($methodName));
    }

    public final function mockFunction($functionName) {
        $mock = $this->getFunctionMock($functionName);
        $this->rewireFunction($functionName, $mock);
        return $mock;
    }

    public final function mockStaticMethod($className, $methodName) {
        $mock = $this->getStaticMethodMock($className, $methodName);
        $this->rewireMethod($className, $methodName, $mock);
        return $mock;
    }

    // PHPUnit shortcuts
    ////////////////////////////////////////////////////////////

    protected final function returnLambda($args, $code) {
        return $this->returnCallback(create_function($args, $code));
    }

    // Extra useful assertions
    ////////////////////////////////////////////////////////////

    /**
     * A more verbose version of assertRegexp().
     */
    public function assertMatch($pattern, $string, $message = '') {
        $res = preg_match($pattern, $string);
        if ( ($res === false) || ($res === 0) ) {
            $this->fail($message . "\n" . "'" . addcslashes($string, "'") . "' does not match regexp '" . addcslashes($pattern, "'") . "'.");
        }
    }

    /**
     * A more verbose version of assertRegexp().
     */
    public function assertNotMatch($pattern, $string, $message = '') {
        $res = preg_match($pattern, $string);
        if ( ($res !== false) && ($res !== 0) ) {
            $this->fail($message . "\n" . "'" . addcslashes($string, "'") . "' matches unwanted regexp '" . addcslashes($pattern, "'") . "'.");
        }
    }

    public final function assertArrayGet($array, $indices, $msg = '') {
        $val = $array;
        if (!is_array($indices)) {
            $indices = array($indices);
        }

        foreach ($indices as $index) {
            $this->assertType('array', $val, $msg);
            $this->assertArrayHasKey($index, $val, $msg);
            $val = $val[$index];
        }
        return $val;
    }

    public final function assertArrayHasValues($expected, $actual, $msg = '') {
        foreach ($expected as $key => $expectedValue) {
            $actualValue = $this->assertArrayGet($actual, $key, $msg);
            if (is_array($expectedValue)) {
                $this->assertType('array', $actualValue, $msg);
                $this->assertArrayHasValues($expectedValue, $actualValue, $msg);
            } else if ($expectedValue instanceof yTest_RegexpMatcher) {
                $expectedValue->match($this, $actualValue, $msg);
            } else {
                $this->assertEquals($expectedValue, $actualValue, $msg);
            }
        }
    }

    public final function assertArrayHasNotValues($expected, $actual, $msg = '') {
        foreach ($expected as $key => $expectedValue) {
            // if the key doesn't exist, there is no problem
            if (array_key_exists($key, $actual)) {
                $actualValue = $actual[$key];
                if (is_array($expectedValue)) {
                    if (gettype($actualValue) === 'array') {
                        $this->assertArrayNotHasValues($expectedValue, $actualValue, $msg);
                    }
                } else if ($expectedValue instanceof yTest_RegexpMatcher) {
                    $expectedValue->notMatch($this, $actualValue, $msg);
                } else {
                    $this->assertNotEquals($expectedValue, $actualValue, $msg);
                }
            }
        }
    }

    public final function assertAlmostEquals($expected, $actual, $epsilon = 0.0001, $msg = '') {
        if (abs($actual - $expected) >= $epsilon) {
            $this->fail("Failed asserting that $actual is almost equals to expected $expected (with epsilon=$epsilon)"
                . (($msg !== '') ? " :\n$msg" : ""));
        }
    }

    // Message prefix
    ////////////////////////////////////////////////////////////

    public final function prefixMessages($prefix) {
        echo "<prefix>: $prefix\n";
    }
}
