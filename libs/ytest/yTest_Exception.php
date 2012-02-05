<?php

class yTest_Exception extends Exception {
    public static function missingDump($path) {
        return new self("missing dump file '$path'");
    }

    public static function sqlError($query, $details) {
        return new self("Error in SQL query on test DB :\n" . $query . "\nDetails:\n" . $details);
    }

    public static function badDelegateObject($object, $funcName) {
        return new self("Invalid rewire delegate object for func/meth '$funcName': " . var_export($object, true));
    }

    public static function invalid($invalidObjectType, $value = null) {
        return new self("Invalid $invalidObjectType: " . var_export($value, true));
    }

    public static function noSuch($invalidObjectType, $idOrName = null) {
        return new self("No such $invalidObjectType: " . var_export($idOrName, true));
    }
};