<?php

abstract class yTest_ListChanges extends yTest_Changes {
    abstract protected function getElementDesc();

    /**
     * Description methods
     */

    public function add($id) {
        $this->addChange(new ChangeListAdd($this, $id));
        return $this;
    }

    public function remove($id) {
        $this->addChange(new ChangeListRemove($this, $id));
        return $this;
    }

    /**
     * Helpers
     */

    protected function getChangeById($id) {
        foreach ($this->changes as $change) {
            if ( $change->getId() == $id ) {
                return $change;
            }
        }
        return null;
    }

    public function assertChanges($test, & $initialData, & $finalData, $msg = null) {
        foreach ($this->getElementsList($initialData, $finalData) as $elementId) {
            if ( in_array($elementId, $this->ignoredElements) ) {
                continue;
            }
            if ( $this->getChangeById($elementId) === null ) {
                if ( $this->getElement($initialData, $elementId) === null ) {
                    $test->fail(($msg !== null ? $msg."\n" : '').'Unexpected addition of '.$this->getElementDesc().' '.$elementId);
                }
                if ( $this->getElement($finalData, $elementId) === null ) {
                    $test->fail(($msg !== null ? $msg."\n" : '').'Unexpected removal of '.$this->getElementDesc().' '.$elementId);
                }
            }
        }

        foreach ($this->changes as $change) {
            if (YTEST_DEBUG_CHANGE_ASSERTIONS) {
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

    protected function getElementsList($initialData, $finalData) {
        return array_merge($initialData, $finalData);
    }

    protected function getElement($data, $id) {
        return in_array($id, $data) ? $id : null;
    }
}

class ChangeListDescription extends yTest_ChangeDescription {
    protected $elementId;

    public function __construct($container, $elementId) {
        parent::__construct($container);
        $this->elementId = $elementId;
    }

    public function getId() {
        return $this->elementId;
    }

    public function __toString() {
        return parent::__toString().' elementId='.$this->elementId;
    }

    // Top stack
    public function add() {
        $this->container->addChange($this);
        $args = func_get_args();
        return call_user_func_array(array($this->container, 'add'), $args);
    }

    public function remove() {
        $this->container->addChange($this);
        $args = func_get_args();
        return call_user_func_array(array($this->container, 'remove'), $args);
    }
}

class ChangeListAdd extends ChangeListDescription {
    public function assertChange($test, $initialElement, $finalElement, $elementDesc, $msg = null) {
        if ( $initialElement !== null ) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expecting addition of '.$elementDesc.' '.$this->elementId.' but it was already present initially');
        }
        if ( $finalElement === null ) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expected addition of '.$elementDesc.' '.$this->elementId.' did not happen');
        }
    }
}

class ChangeListRemove extends ChangeListDescription {
    public function assertChange($test, $initialElement, $finalElement, $elementDesc, $msg = null) {
        if ( $initialElement === null ) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expecting removal of '.$elementDesc.' '.$this->elementId.' but it was not present initially');
        }
        if ( $finalElement !== null ) {
            $test->fail(($msg !== null ? $msg."\n" : '').'Expected removal of '.$elementDesc.' '.$this->elementId.' did not happen');
        }
    }
}
