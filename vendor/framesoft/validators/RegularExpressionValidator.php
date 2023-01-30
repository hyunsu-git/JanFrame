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
 * Class RegularExpressionValidator
 * 使用正则表达式进行验证
 */
class RegularExpressionValidator extends Validator
{
    /**
     * @var string 正则表达式
     */
    public $pattern;

    /**
     * @var bool 设置不符合正则才行
     */
    public $not = false;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->pattern === null) {
            throw new InvalidValidationRule('The "pattern" property must be set.');
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

        return  !is_array($value) &&
            (!$this->not && preg_match($this->pattern, $value)
                || $this->not && !preg_match($this->pattern, $value));
    }
}
