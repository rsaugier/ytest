<?php

class yTest_Url {
    /**
     * @return false | array('arg1' => 'value1', ...)
     */
    public static function getArgsFromUrl($url) {
        $res = parse_url($url, PHP_URL_QUERY);
        if ($res === false) {
            throw yTest_Exception::invalid('URL', $url);
        }
        $args = explode('&', $res);
        $map = array();
        foreach ($args as $a) {
            list($key, $val) = explode('=', $a);
            $map[ $key ] = $val;
        }
        return $map;
    }

};
