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
 * Class IdValidator
 * 验证是否是有效的id
 */
class IdValidator extends Validator
{
    /**
     * 只能使用数字
     * @var bool
     */
    public $onlyNumber = false;

    /**
     * @var bool 是否允许数组
     */
    public $enableArray = false;

    /**
     * @var string 使用的正则表达式
     */
    public $pattern = '/^[A-Za-z0-9_.]+$/';

    /**
     * @var int 最大长度
     */
    public $maxLength = 64;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = '{attribute} is invalid.';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, $attribute = '')
    {
        if ($this->enableArray && is_array($value)) {
            foreach ($value as $item) {
                if (!$this->_validate($item)) {
                    return false;
                }
            }
            return true;
        }else{
            return $this->_validate($value);
        }
    }


    protected function _validate($value)
    {
        if (!is_numeric($value) && !is_string($value)) {
            return false;
        }
        if (!preg_match($this->pattern, $value)) {
            return false;
        }
        if ($this->onlyNumber && !is_numeric($value)) {
            return false;
        }
        if (mb_strlen($value) > $this->maxLength) {
            return false;
        }
        return true;
    }
}
