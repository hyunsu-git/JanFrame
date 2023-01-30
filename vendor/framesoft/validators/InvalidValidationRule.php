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

namespace jan\validators;

use jan\basic\Exception;

/**
 * 验证规则相关的异常
 */
class InvalidValidationRule extends Exception
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Invalid Validation Rule Exception';
    }
}