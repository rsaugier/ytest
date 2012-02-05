<?php

abstract class yTest_ObjectChanges extends yTest_Changes {

    protected $comparableProperties = array();
    protected $properties = array();
    protected $debug = false;

    abstract protected function getElementDesc();

    /**
     * Description methods
     */

    public function diff($propertyName, $value) {
        $this->checkPropertyName($propertyName);
        $this->addChange(new ChangeObjectDiff($this, $propertyName, $value));
        return $this;
    }

    public function value($propertyName, $value) {
        $this->checkPropertyName($propertyName);
        $this->addChange(new ChangeObjectValue($this, $propertyName, $value));
        return $this;
    }

    /**
     * Helpers
     */

    public function getComparablePropertyNames() {
        return $this->comparableProperties;
    }

    private function getChangeByPropertyName($propertyName) {
        foreach ($this->changes as $change) {
            if ( $change->getInfoName() == $propertyName ) {
                return $change;
            }
        }
        return null;
    }

    private function checkPropertyName($propertyName) {
        if ( !in_array($propertyName, $this->properties) ) {
            throw new Exception('unknown info name: '.$propertyName);
        }
        if ( $this->getChangeByPropertyName($propertyName) !== null ) {
            throw new Exception('Duplicate change description on '.$propertyName);
        }
    }


    /**
     * The real stuff !
     */
    public function assertChanges($test, & $initialData, & $finalData, $msg = null) {
        if ( count($this->changes) == 0 && count($this->ignoredElements) == 0 ) {
            if ( $initialData !== $finalData ) {
                $diff1 = array_diff_assoc($initialData, $finalData);
                $diff2 = array_diff_assoc($finalData, $initialData);
                $test->fail(($msg !== null ? $msg."\n" : '').'Unexpected changes on '.$this->getElementDesc().': '.var_export(array('initial' => $diff1, 'final' => $diff2), true));
            }
        } else {
            foreach ($this->properties as $propName) {
                if ( in_array($propName, $this->ignoredElements) ) {
                    continue;
                }
                $change = $this->getChangeByPropertyName($propName);
                if ( $change === null ) {
                    if ( $this->debug ) {
                        echo 'Quick check '.get_class($this)." on ".$propName."\n";
                    }
                    $initialValue = $initialData[$propName];
                    $finalValue = $finalData[$propName];

                    if (!yTest_isEqual($initialValue, $finalValue)) {
                        $test->fail(($msg !== null ? $msg."\n" : '').'Unexpected change on '.$this->getElementDesc().'->'.$propName.': initial='.$initialValue.' final='.$finalValue);
                    }
                }
            }
        }

        foreach ($this->changes as $change) {
            if ( $this->debug ) {
                echo "assert ".$change."\n";
            }
            $change->assertChange($test, $initialData[$change->getInfoName()], $finalData[$change->getInfoName()], $msg);
        }
    }
}

class ChangeObjectFirstLevel extends yTest_ChangeDescription {
    protected $propertyName;

    public function __construct($container, $propertyName) {
        parent::__construct($container);
        $this->infoName = $propertyName;
    }

    public function getInfoName() {
        return $this->infoName;
    }

    public function __toString() {
        return parent::__toString().' infoName='.$this->infoName;
    }
}

class ChangeObjectFirstLevelNeedValue extends ChangeObjectFirstLevel {
    protected $value;

    public function __construct($container, $propertyName, $value) {
        parent::__construct($container, $propertyName);
        $this->value = $value;
    }
}

class ChangeObjectDiff extends ChangeObjectFirstLevelNeedValue {
    public function __construct($container, $propertyName, $value) {
        if ( ! in_array(
            $propertyName, $container->getComparablePropertyNames()
            ) ) {
            throw new Exception('Diff on '.$propertyName.' is stupid (it\'s not a number incrementing/decrementing)');
        }
        if ( ! is_numeric($value) ) {
            throw new Exception('Diff value on '.$propertyName.' must be a number: '.var_export($value, true));
        }
        parent::__construct($container, $propertyName, $value);
    }

    public function assertChange($test, $initialValue, $finalValue, $msg = null) {
        if (!yTest_isEqual($finalValue - $initialValue, $this->value)) {
            $test->fail(($msg !== null ? $msg."\n" : '').'failed to meet expected diff of '.$this->value.' on '.$this->infoName.', real diff: '.($finalValue - $initialValue).', initial value: '.$initialValue.', final value:'.$finalValue);
        }
    }
}

class ChangeObjectValue extends ChangeObjectFirstLevelNeedValue {
    public function assertChange($test, $initialValue, $finalValue, $msg = null) {

        if (yTest_isEqual($initialValue, $finalValue)) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expected change on property '.$this->infoName.' did not happen, value='.$finalValue);
        }

        if (!yTest_isEqual($finalValue, $this->value)) {
            $test->fail(($msg !== null ? $msg."\n" : '').'failed to meet expected value of '.$this->value.' on '.$this->infoName.', final value: '.$finalValue);
        }
    }
}
