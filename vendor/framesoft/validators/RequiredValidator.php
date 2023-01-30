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


class RequiredValidator extends Validator
{
    public $skipOnEmpty = false;

    /**
     * @var bool 是否使用强验证;如果为true,则值验证不是null
     */
    public $strict = false;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = '{attribute} cannot be blank.';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value,$attribute = '')
    {
        if ($this->strict) {
            return $value !== null;
        }

        return !$this->isEmpty($value);
    }
}
