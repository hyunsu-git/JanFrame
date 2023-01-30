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

namespace jan\di;

use jan\basic\Exception;

/**
 * NotInstantiableException represents an error when a class cannot be instantiated.
 */
class NotInstantiableException extends Exception
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Not Instantiable';
    }

    public function __construct($class, $message = null, $code = 0, $previous = null)
    {
        if ($message === null) {
            $message = "Can not instantiate $class.";
        }
        parent::__construct($message, $code, $previous);
    }
}