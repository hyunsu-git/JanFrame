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
 * UnknownPropertyException Represents calling non-existent or private properties without  `getter` method
 */
class UnknownPropertyException extends InvalidArgumentException
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Unknown Property';
    }
}