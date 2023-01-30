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
 * Class NumberValidator
 * 数字和整数验证器
 */
class NumberValidator extends Validator
{
    /**
     * @var bool 限定只能是整数
     */
    public $integerOnly = false;

    /**
     * @var double 最大值
     */
    public $max;

    /**
     * @var double 最小值
     */
    public $min;

    /**
     * @var string 超过最大值的提示语
     */
    public $tooBig;

    /**
     * @var string 超过最小值的提示语
     */
    public $tooSmall;

    /**
     * @var string 整数的正则
     */
    public $integerPattern = '/^\s*[+-]?\d+\s*$/';
    /**
     * @var string 数字的正则
     */
    public $numberPattern = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = $this->integerOnly ? '{attribute} must be an integer.' : '{attribute} must be a number.';
        }

        if ($this->min !== null && $this->tooSmall === null) {
            $this->tooSmall = '{attribute} must be no less than {min}.';
        }
        if ($this->max !== null && $this->tooBig === null) {
            $this->tooBig = '{attribute} must be no greater than {max}.';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, $attribute = '')
    {
        if ($this->isNotNumber($value)) {
            return false;
        }
        $pattern = $this->integerOnly ? $this->integerPattern : $this->numberPattern;
        if (!preg_match($pattern, $value)) {
            return false;
        } elseif ($this->min !== null && $value < $this->min) {
            $this->message = $this->tooSmall;
            return false;
        } elseif ($this->max !== null && $value > $this->max) {
            $this->message = $this->tooBig;
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
            'min' => ['value' => $this->min, 'lang' => false],
            'max' => ['value' => $this->max, 'lang' => false],
        ]);
    }

    /**
     * 检查是否是数字
     * @param $value
     * @return bool
     */
    private function isNotNumber($value)
    {
        return is_array($value)
            || is_bool($value)
            || (is_object($value) && !method_exists($value, '__toString'))
            || (!is_object($value) && !is_scalar($value) && $value !== null);
    }
}
