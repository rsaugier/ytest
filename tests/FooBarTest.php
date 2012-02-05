<?php

define('MY_STUPID', 42);

class FooBar {
    const STUPID2 = 42;

    private $foo = 15;
    private function bar($arg1, $arg2) {
        $this->foo = $arg1;
        return $arg1 + $arg2;
    }

    public function doGetFoo() {
        return $this->foo;
    }

    public function doSetFoo($val) {
        $this->foo = $val;
    }

    public function getMySplHash() {
        return spl_object_hash($this);
    }

    private static $sfoo = 26;
    private static function sbar($arg1, $arg2) {
        self::$sfoo = $arg1;
        return $arg1 + $arg2;
    }

    public static function callSBar($arg1, $arg2) {
        return self::sbar($arg1, $arg2);
    }

    private static function passbyref(&$result, $arg) {
        $result = $arg;
    }

    public static function doSetSFoo($arg) {
        self::$sfoo = $arg;
    }

    public static function doGetSFoo() {
        return self::$sfoo;
    }

    private function baz($arg1, $arg2 = 42) {
        $this->foo = $arg1 * $arg2;
    }

    public function callBaz($arg1) {
        $this->baz($arg1);
    }

    public static $pubstat = 134;
    private static $privstat = 135;

    public static function getPrivStat() {
        return self::$privstat;
    }

    public static function setPrivStat($val) {
        self::$privstat = $val;
    }
}


function myFunc(&$a, $b) {
    $a = 666;
    return 888;
}


class Recorder {
    public $recorded = array();

    public function record() {
        $this->recorded[] = func_get_args();
    }
}


class FooBarTest extends yTest_CustomTestCase {
    public function testStaticAccess() {
        $this->letMeAccess("FooBar", "sfoo");
        $this->letMeCall("FooBar", "sbar");

        $this->assertEquals(26, $this->getStaticProperty("FooBar", "sfoo"));
        $this->assertEquals(3, FooBar::ytx_call_sbar(1, 2));
        $this->assertEquals(1, $this->getStaticProperty("FooBar", "sfoo"));
    }

    public function testInstanceAccess() {
        $this->letMeAccess("FooBar", "foo");
        $this->letMeCall("FooBar", "bar");

        $foobar = new FooBar();
        $this->assertEquals(15, $this->getProperty($foobar, "foo"));
        $this->assertEquals(3, $foobar->ytx_call_bar(1, 2));
        $this->assertEquals(1, $this->getProperty($foobar, "foo"));
    }

    public function testPassByRef() {
        $this->letMeCall("FooBar", "passbyref");

        $out = 123;
        FooBar::ytx_call_passbyref($out, 456);
        $this->assertEquals(456, $out);
    }

    public function testUnwireMethod() {
        $foobar = new FooBar();
        $this->unwireMethod($foobar, "doSetFoo");
        $foobar->doSetFoo(666);
        $this->assertEquals(15, $foobar->doGetFoo());
    }

    /**
     * @depends testUnwireMethod
     */
    public function testUnwireMethod_bis() {
        $foobar = new FooBar();
        $foobar->doSetFoo(666);
        $this->assertEquals(666, $foobar->doGetFoo());
    }

    public function getHash($a) {
        return spl_object_hash($a);
    }

    public function testSplObjectHash() {
        $foo = new FooBar();
        $this->assertEquals(spl_object_hash($foo), spl_object_hash($foo));
        $this->assertEquals(spl_object_hash($foo), $this->getHash($foo));
        $this->assertEquals(spl_object_hash($foo), $foo->getMySplHash());
    }

    public function testRewireMethod_specificInstance() {
        $f1 = new FooBar();
        $f2 = new FooBar();

        $this->rewireMethod($f1, "doSetFoo", $f2);

        $f1->doSetFoo(666);
        $this->assertEquals(15, $f1->doGetFoo());
        $this->assertEquals(666, $f2->doGetFoo());
    }

    public function testRewireMethod_allInstances() {
        //echo "testRewireMethod_allInstances==================================================================\n";

        $recorder = new Recorder();

        $f1 = new FooBar();
        $f2 = new FooBar();

        $this->rewireMethod("FooBar", "doSetFoo", $recorder, "record");

        //echo "CALL1\n";
        $f1->doSetFoo(123);
        //echo "CALL2\n";
        $f2->doSetFoo(456);
        //echo "CALLend\n";

        $this->assertEquals(array(array(123), array(456)), $recorder->recorded);
    }

    public function testRewireMethod_static() {
        //echo "testRewireMethod_static==================================================================\n";

        $recorder = new Recorder();

        FooBar::doSetSFoo(111);
        $this->assertEquals(111, FooBar::doGetSFoo());

        $this->rewireMethod("FooBar", "doSetSFoo", $recorder, "record");

        FooBar::doSetSFoo(222);
        $this->assertEquals(111, FooBar::doGetSFoo());
        FooBar::doSetSFoo(333);
        $this->assertEquals(111, FooBar::doGetSFoo());

        $this->assertEquals(array(array(222), array(333)), $recorder->recorded);
    }

    public function testRewireMethod_static_private() {
        $recorder = new Recorder();

        $this->rewireMethod("FooBar", "sbar", $recorder, "record");
        FooBar::callSBar(45, 2);
        FooBar::callSBar(48, 1);
        $this->assertEquals(array(array(45, 2), array(48, 1)), $recorder->recorded);
    }

    public function testRewireMethod_private_defaultArgs() {
        $recorder = new Recorder();

        $foobar = new FooBar();

        $this->rewireMethod("FooBar", "baz", $recorder, "record");
        $foobar->callBaz(11);
        $foobar->callBaz(22);
        $this->assertEquals(array(array(11, 42), array(22, 42)), $recorder->recorded);
    }

    public function myFunc2(&$a, $b) {
        $a = $b;
        return 42;
    }

    public function testRewireFunction_user() {
        $this->rewireFunction('myFunc', $this, 'myFunc2');
        $a = null;
        $res = myFunc($a, 53280);
        $this->assertEquals(53280, $a);
        $this->assertEquals(42, $res);
    }

    public function testRewireFunction_user_noRewire() {
        $a = null;
        $res = myFunc($a, 53280);
        $this->assertEquals(666, $a);
        $this->assertEquals(888, $res);
    }

    public function myMtRand($a, $b) {
        return $a + $b;
    }

    public function testRewireFunction_mt_rand() {
        $this->rewireFunction('mt_rand', $this, 'myMtRand');
        $a = null;
        $res = mt_rand(17, 17);
        $this->assertEquals(34, $res);
    }

    public function testRewireFunction_mt_rand_noRewire() {
        $a = null;
        $res = mt_rand(17, 17);
        $this->assertEquals(17, $res);
    }

    public function testRunSql() {
        $stmt = $this->runSql('CREATE table `user` (
            `created` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\',
            `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `user_id` bigint(20) unsigned NOT NULL,
            PRIMARY KEY (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');

        $stmt = $this->runSql('SELECT * FROM `user` WHERE `user_id`=1315765812');
        $this->assertEquals(0, $stmt->rowCount());

        $stmt = $this->runSql('INSERT INTO `user` (`user_id`) VALUES (1315765812);');
        $this->assertEquals(1, $stmt->rowCount());

        $stmt = $this->runSql('SELECT * FROM `user` WHERE `user_id`=1315765812');
        $this->assertEquals(1, $stmt->rowCount());
    }

    public function testSetConstant_change() {
        $this->setConstant("MY_STUPID", 666);
        $this->assertEquals(666, MY_STUPID);
    }

    public function testSetConstant_noChange() {
        $this->assertEquals(42, MY_STUPID);
    }

    public function testSetClassConstant_change() {
        $this->setClassConstant("Foobar", "STUPID2", 666);
        $this->assertEquals(666, Foobar::STUPID2);
    }

    public function testSetClassConstant_noChange() {
        $this->assertEquals(42, Foobar::STUPID2);
    }

    public function testSetStaticProperty_priv() {
        $this->assertEquals(135, FooBar::getPrivStat());
        $this->setStaticProperty("FooBar", "privstat", 93100);
        $this->assertEquals(93100, FooBar::getPrivStat());
    }

    public function testSetStaticProperty_priv_noChange() {
        $this->assertEquals(135, FooBar::getPrivStat());
    }

    public function testSetStaticProperty_pub() {
        $this->assertEquals(134, FooBar::$pubstat);
        $this->setStaticProperty("FooBar", "pubstat", 93100);
        $this->assertEquals(93100, FooBar::$pubstat);
    }

    public function testSetStaticProperty_pub_noChange() {
        $this->assertEquals(134, FooBar::$pubstat);
    }

    public function testGetStaticProperty_priv() {
        $this->assertEquals(135, FooBar::getPrivStat());
        $this->assertEquals(135, $this->getStaticProperty("FooBar", "privstat"));
    }

    public function testGetStaticProperty_pub() {
        $this->assertEquals(134, FooBar::$pubstat);
        $this->assertEquals(134, $this->getStaticProperty("FooBar", "pubstat"));
    }

    public function testBackupStaticProperty_pub() {
        $this->assertEquals(134, FooBar::$pubstat);
        $this->backupStaticProperty('FooBar', 'pubstat');
        FooBar::$pubstat = 666;
    }

    public function testBackupStaticProperty_pub_nochange() {
        $this->assertEquals(134, FooBar::$pubstat);
    }

    public function testBackupStaticProperty_priv() {
        $this->assertEquals(135, FooBar::getPrivStat());
        $this->backupStaticProperty('FooBar', 'privstat');
        FooBar::setPrivStat(666);
    }

    public function testBackupStaticProperty_priv_nochange() {
        $this->assertEquals(135, FooBar::getPrivStat());
    }

    public function testLetMeAccesOrCall_multiple() {
        $this->letMeAccess("FooBar", "sfoo");
        $this->letMeCall("FooBar", "sbar");
        $this->letMeAccess("FooBar", "sfoo");
        $this->letMeCall("FooBar", "sbar");
        $this->letMeAccess("FooBar", "sfoo");
        $this->letMeCall("FooBar", "sbar");
    }
}
