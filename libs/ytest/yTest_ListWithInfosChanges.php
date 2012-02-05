<?php

abstract class yTest_ListWithInfosChanges extends yTest_ListChanges {
    /**
     * Description methods
     */

    public function add($id) {
        $change = new ChangeListWithInfosAdd($this, $id);
        $this->addChange($change);
        return $change;
    }

    public function change($id) {
        $change = new ChangeListWithInfosChange($this, $id);
        $this->addChange($change);
        return $change;
    }

    /**
     * Helpers
     */

    public function assertChanges($test, & $initialData, & $finalData, $msg = null) {
        $elements = $this->getElementsList($initialData, $finalData);
        foreach ($elements as $elementId) {
            if ( in_array($elementId, $this->ignoredElements) ) {
                continue;
            }
            $change = $this->getChangeById($elementId);
            $initialElement = $this->getElement($initialData, $elementId);
            $finalElement = $this->getElement($finalData, $elementId);

            // Optim: skip this element if no changes are described for it and related data has not changed
            if ( $change === null ) {
                if ( $initialElement === $finalElement ) {
                    if ( YTEST_DEBUG_USER_CHANGES ) {
                        echo 'Skip '.get_class($this)." for element ".$elementId." because no changes expected and no changes detected\n";
                    }
                    continue;
                } else {
                    throw new Exception('Unexpected change on '.$this->getElementDesc().' '.$elementId.': initial='.var_export($initialElement, true).' final='.var_export($finalElement, true));
                }
            }
        }

        foreach ($this->changes as $change) {
            if ( YTEST_DEBUG_USER_CHANGES ) {
                echo "assert ".$change."\n";
            }
            $change->assertChange($test,
                $this->getElement($initialData, $change->getId()),
                $this->getElement($finalData, $change->getId()),
                $this->getElementDesc(),
                $msg
                                 );
        }
    }

    protected function getChangeById($id) {
        foreach ($this->changes as $change) {
            if ( $change->getId() == $id ) {
                return $change;
            }
        }
        return null;
    }

    protected function getElementsList($initialData, $finalData) {
        $keys = array_keys($initialData);
        foreach (array_keys($finalData) as $key) {
            if ( ! in_array($key, $keys) ) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    protected function getElement($data, $id) {
        return array_key_exists($id, $data) ? $data[$id] : null;
    }

    public function addChange($change) {
        $existingChange = $this->getChangeById($change->getId());
        if ( $existingChange !== null ) {
            if ( ! $existingChange->isExtended() ) {
                throw new Exception('Duplicated change description for '.$this->getElementDesc().' '.$change->getId());
            }
            if ( get_class($existingChange) !== get_class($change) ) {
                throw new Exception('Mixing change description for '.$this->getElementDesc().' '.$change->getId());
            }
            // merge
            $subChanges = $change->getChanges();
            foreach ($subChanges as $subChange) {
                $existingChange->addChange($subChange);
            }
        } else {
            $this->changes[] = $change;
        }
    }
}

abstract class ChangeListWithInfosFirstLevel extends ChangeListDescription {
    protected $changes = array();

    public function isExtended() {
        return true;
    }

    public function getChanges() {
        return $this->changes;
    }

    public function addChange($change) {
        $this->changes[] = $change;
    }

    // Top stack

    public function change() {
        $args = func_get_args();
        return call_user_func_array(array($this->container, 'change'), $args);
    }

    public function add() {
        $args = func_get_args();
        return call_user_func_array(array($this->container, 'add'), $args);
    }

    public function remove() {
        $args = func_get_args();
        return call_user_func_array(array($this->container, 'remove'), $args);
    }
}

class ChangeListWithInfosAdd extends ChangeListWithInfosFirstLevel {
    public function value($propName, $value) {
        $this->addChange(new ChangeListWithInfosAddValue($this->container, $this->elementId, $this, $propName, $value));
        return $this;
    }

    public function assertChange($test, $initialElement, $finalElement, $elementDesc, $msg = null) {
        if ( $initialElement !== null ) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expecting addition of '.$elementDesc.' '.$this->elementId.' but it was already present initially');
        }
        if ( $finalElement === null ) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expected addition of '.$elementDesc.' '.$this->elementId.' did not happen');
        }

        foreach ($this->changes as $change) {
            $propertyName = $change->getPropertyName();
            $initialPropertyValue = $initialElement[$propertyName];
            $finalPropertyValue = $finalElement[$propertyName];
            if ( YTEST_DEBUG_USER_CHANGES ) {
                echo "assert ".$change."\n";
            }
            $change->assertChange($test, $initialPropertyValue, $finalPropertyValue, $elementDesc, $msg);
        }
    }
}

class ChangeListWithInfosChange extends ChangeListWithInfosFirstLevel {
    public function assertChange($test, $initialElement, $finalElement, $elementDesc, $msg = null) {
        if ( $initialElement === null ) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expecting change on '.$elementDesc.' '.$this->elementId.' which does not exists initially');
            return false;
        }
        if ( $finalElement === null ) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expecting change on '.$elementDesc.' '.$this->elementId.' which does not exists finally');
            return false;
        }

        $propertiesToCheck = $this->container->properties;
        foreach ($this->changes as $change) {
            $propertyName = $change->getPropertyName();
            $initialPropertyValue = $initialElement[$propertyName];
            $finalPropertyValue = $finalElement[$propertyName];
            unset($propertiesToCheck[array_search($propertyName, $propertiesToCheck)]);
            if ( YTEST_DEBUG_USER_CHANGES ) {
                echo "assert ".$change."\n";
            }
            $change->assertChange($test, $initialPropertyValue, $finalPropertyValue, $elementDesc, $msg);
        }

        foreach ($propertiesToCheck as $propertyName) {
            if ( YTEST_DEBUG_USER_CHANGES ) {
                echo 'Quick check '.$this." on ".$propertyName."\n";
            }

            $initialPropertyValue = $initialElement[$propertyName];
            $finalPropertyValue = $finalElement[$propertyName];

            if (!yTest_isEqual($initialPropertyValue, $finalPropertyValue)) {
                $test->fail(($msg !== null ? $msg."\n" : '').'Unexpected change on '.$propertyName.' for '.$elementDesc.' '.$this->elementId.' initial='.var_export($initialPropertyValue, true).' final='.var_export($finalPropertyValue, true));
            }
        }

        return true;
    }

    public function value($propName, $value) {
        $this->addChange(new ChangeListWithInfosChangeValue($this->container, $this->elementId, $this, $propName, $value));
        return $this;
    }

    public function diff($propName, $value) {
        $this->addChange(new ChangeListWithInfosChangeDiff($this->container, $this->elementId, $this, $propName, $value));
        return $this;
    }
}

class ChangeListWithInfosSecondLevel extends ChangeListWithInfosFirstLevel { /* extends ChangeListChangerExtendOperator*/
    protected $propName;
    protected $firstLevel;
    protected $value;

    public function __construct($container, $elementId, $firstLevel, $propName, $value) {
        parent::__construct($container, $elementId);
        $this->firstLevel = $firstLevel;
        $this->propName = $propName;
        $this->value = $value;
    }

    public function getId() {
        return $this->firstLevel->getId();
    }

    public function getPropertyName() {
        return $this->propName;
    }

    public function __toString() {
        return parent::__toString().' propName='.$this->propName.' value='.$this->value; //.' firstLevel='.$this->firstLevel;
    }
}

class ChangeListWithInfosValue extends ChangeListWithInfosSecondLevel {
    public function assertChange($test, $initialValue, $finalValue, $elementDesc, $msg = null) {
        if ($initialValue != null && yTest_isEqual($initialValue,$finalValue)) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expected change on property '.$this->propName.' did not happen');
        }

        if (!yTest_isEqual($finalValue, $this->value)) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expecting value '.var_export($this->value, true).' for property '.$this->propName.' of '.$elementDesc.' '.$this->getId().', got '.var_export($finalValue, true));
        }
    }
}

class ChangeListWithInfosAddValue extends ChangeListWithInfosValue {
    public function value() {
        $args = func_get_args();
        return call_user_func_array(array($this->firstLevel, 'value'), $args);
    }
}

class ChangeListWithInfosChangeValue extends ChangeListWithInfosValue {
    public function value() {
        $args = func_get_args();
        return call_user_func_array(array($this->firstLevel, 'value'), $args);
    }

    public function diff() {
        $args = func_get_args();
        return call_user_func_array(array($this->firstLevel, 'diff'), $args);
    }
}


class ChangeListWithInfosChangeDiff extends ChangeListWithInfosSecondLevel {
    public function assertChange($test, $initialValue, $finalValue, $elementDesc, $msg = null) {
        if (!yTest_isEqual(($finalValue - $initialValue), $this->value)) {
            $diff = $finalValue - $initialValue;
            $test->fail(($msg !== null ? $msg."\n" : '').'Expecting diff of '.$this->value.' for property '.$this->propName.' of '.$elementDesc.' '.$this->getId().', got '.$diff);
        }
    }
}
