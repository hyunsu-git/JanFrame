<?php
/**
 * This file is part of jan-frame.
 *
 * Licensed under The MIT License
 *
 * @author    hyunsu<hyunsu@foxmail.com>
 * @link      http://sun.hyunsu.cn
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version   1.0
 *
 * ============================= 重大版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace jan\basic;

/**
 * 运行中捕获到的错误，会被转成异常抛出
 */
class ErrorException extends \ErrorException implements IException
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        static $names = [
            E_COMPILE_ERROR => 'PHP Compile Error',
            E_COMPILE_WARNING => 'PHP Compile Warning',
            E_CORE_ERROR => 'PHP Core Error',
            E_CORE_WARNING => 'PHP Core Warning',
            E_DEPRECATED => 'PHP Deprecated Warning',
            E_ERROR => 'PHP Fatal Error',
            E_NOTICE => 'PHP Notice',
            E_PARSE => 'PHP Parse Error',
            E_RECOVERABLE_ERROR => 'PHP Recoverable Error',
            E_STRICT => 'PHP Strict Warning',
            E_USER_DEPRECATED => 'PHP User Deprecated Warning',
            E_USER_ERROR => 'PHP User Error',
            E_USER_NOTICE => 'PHP User Notice',
            E_USER_WARNING => 'PHP User Warning',
            E_WARNING => 'PHP Warning',
        ];

        return isset($names[$this->getCode()]) ? $names[$this->getCode()] : 'Error';
    }

    /**
     * 返回是否是致命错误的一种
     * @param $error
     * @return bool
     */
    public static function isFatalError($error)
    {
        return isset($error['type']) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING]);
    }
}