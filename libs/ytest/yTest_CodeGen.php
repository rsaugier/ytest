<?php

class yTest_CodeGen {
    const COMMON_PREFIX = 'ytx_';
    const CLASS_WITH_SINGLE_FUNC_STUB_PREFIX = 'cwsfs_';

    public static function genClassWithSingleFuncStub($funcOrStaticMethodName, $staticMethodClassName = null) {
        $newClassName = self::COMMON_PREFIX . self::CLASS_WITH_SINGLE_FUNC_STUB_PREFIX;
        if ($staticMethodClassName !== null) {
            $newClassName .= $staticMethodClassName . '_';
        }
        $newClassName .= $funcOrStaticMethodName;

        if (class_exists($newClassName, false)) {
            yTest_debugCC("ALREADY GEN'd: $newClassName");
        } else {
            $code = 'class ' . $newClassName . ' {' . "\n";
            $code .= '    public function ' . $funcOrStaticMethodName . '('
                . yTest_Signature::getParamsDecl($funcOrStaticMethodName, $staticMethodClassName) . ') {}' . "\n";
            $code .= "}\n";

            yTest_debugCC("CODE: $code");
            eval($code);
        }

        return $newClassName;
    }
}
