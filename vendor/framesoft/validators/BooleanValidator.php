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

/**
 * Class BooleanValidator
 * 验证是否为 bool 类型
 */
class BooleanValidator extends Validator
{
    /**
     * @var bool 是否使用全等比较
     * 如果为false,则 1,'1',0,'0'都可以
     */
    public $strict = false;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = '{attribute} must be either "{true}" or "{false}".';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value,$attribute = '')
    {
        if ($this->strict) {
            return $value === true || $value === false;
        } else {
            return $value === true || $value === false || $value === 1 || $value === '1' || $value === 0 || $value === '0';
        }
    }
}
