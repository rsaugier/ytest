<?php

abstract class yTest_AbstractCodeChange {
    const COMMON_PREFIX = 'ytx_';

    // Apply the code change using Reflection, runkit, etc...
    abstract protected function apply();

    // Undo the change using Reflection, runkit, etc...
    abstract protected function undo();
};


?>