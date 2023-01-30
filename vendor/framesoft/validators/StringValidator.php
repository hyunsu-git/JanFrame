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


class StringValidator extends Validator
{
    /**
     * @var integer|array 字符串长度
     * 可以是整数,表示确切长度
     * 可以是数组,第一个元素表示最小长度,第二个元素表示最大长度.eg:[3]表示最小3为,最大不限  [3,10]表示最小3位,最大10位
     */
    public $length;

    /**
     * @var integer 最大长度,如果没定义并且 $length 是数组,则使用 $length定义的值
     */
    public $max;

    /**
     * @var integer 最小长度,如果没定义并且 $length 是数组,则使用 $length定义的值
     */
    public $min;

    /**
     * @var string 长度太短的提示信息
     */
    public $tooShort;

    /**
     * @var string 长度太长的提示信息
     */
    public $tooLong;

    /**
     * @var string 长度不正确的提示信息
     */
    public $notEqual;

    /**
     * @var string 字符串使用的字符集,默认使用设置的 charset 或者 UTF-8
     */
    public $encoding;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if (is_array($this->length)) {
            if ($this->min === null && isset($this->length[0])) {
                $this->min = $this->length[0];
            }
            if ($this->max === null && isset($this->length[1])) {
                $this->max = $this->length[1];
            }
            $this->length = null;
        }

        if ($this->encoding === null) {
            $this->encoding = \Jan::getConfig('charset', 'UTF-8');
        }

        if ($this->message === null) {
            $this->message = '{attribute} must be a string.';
        }

        if ($this->min !== null && $this->tooShort === null) {
            $this->tooShort = 'Length of the {attribute} is too short.';
        }

        if ($this->max !== null && $this->tooLong === null) {
            $this->tooLong = 'Length of the {attribute} is too long.';
        }

        if ($this->length !== null && $this->notEqual === null) {
            $this->notEqual = 'Length of the {attribute} must be {length} digits.';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value,$attribute = '')
    {
        if (!is_string($value)) {
            return false;
        }

        $length = mb_strlen($value, $this->encoding);

        if ($this->length !== null && $length !== $this->length) {
            $this->message = $this->notEqual;
            return false;
        }
        if ($this->min !== null && $length < $this->min) {
            $this->message = $this->tooShort;
            return false;
        }
        if ($this->max !== null && $length > $this->max) {
            $this->message = $this->tooLong;
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function formatError($field, $param = [])
    {
        return parent::formatError($field, [
            'length'=>[
                'value'=>$this->length,
                'lang'=>false,
            ]
        ]);
    }
}
