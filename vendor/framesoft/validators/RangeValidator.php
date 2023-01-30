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

use jan\helper\ArrayHelper;
use Traversable;
use Closure;

/**
 * Class RangeValidator
 * 范围验证器
 */
class RangeValidator extends Validator
{
    /**
     * @var array|Closure|Traversable 限定的数组
     */
    public $range;

    /**
     * @var bool 是否使用全等比较
     */
    public $strict = false;

    /**
     * @var bool 是否比较不在范围内
     */
    public $not = false;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if (!is_array($this->range)
            && !($this->range instanceof Closure)
            && !($this->range instanceof Traversable)
        ) {
            throw new InvalidValidationRule('The "range" property must be set.');
        }
        if ($this->message === null) {
            $this->message = '{attribute} is invalid.';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value,$attribute = '')
    {
        $in = false;

        if (ArrayHelper::isIn($value, $this->range, $this->strict)) {
            $in = true;
        }

        return $this->not ? !$in : $in;
    }
}
