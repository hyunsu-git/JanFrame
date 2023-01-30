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


class AccountValidator extends StringValidator
{
    /**
     * 类型1,字母开头,可以使用字母数字下划线
     */
    const TYPE_MODE1 = 'mode1';
    /**
     * 类型2,中文或字母开头,可以使用字母数字下划线中文
     */
    const TYPE_MODE2 = 'mode2';
    /**
     * 类型3,全中文
     */
    const TYPE_MODE3 = 'mode3';

    /**
     * @var string 模式1所用的正则表达式
     */
    public $mode1_pattern = '/^[a-zA-Z][a-zA-Z0-9_]*$/';

    /**
     * @var string 模式2所用的正则表达式
     */
    public $mode2_pattern = '/^[\x{4e00}-\x{9fa5}a-zA-Z][\x{4e00}-\x{9fa5}a-zA-Z0-9_]*$/u';

    /**
     * @var string 模式3所用的正则表达式
     */
    public $mode3_pattern = '/^[\x{4e00}-\x{9fa5}]+$/u';

    /**
     * @var string 模式1不匹配的错误信息
     */
    public $mode1_message = '{attribute} must start with a letter and be a combination of letters, numbers, and underscores.';
    /**
     * @var string 模式2不匹配的错误信息
     */
    public $mode2_message = '{attribute} must begin with a Chinese character and be a combination of Chinese character,letter,number and underline.';
    /**
     * @var string 模式3不匹配的错误信息
     */
    public $mode3_message = '{attribute} must be all Chinese characters.';

    /**
     * @var string 账号采用的模式,可以使用数组
     */
    public $mode = self::TYPE_MODE1;

    /**
     * @var int 默认最大长度20
     */
    public $max = 20;

    /**
     * @var int 默认最小长度3
     */
    public $min = 3;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if ($this->message === null) {
            $this->message = '{attribute} is an invalid value.';
        }
        parent::init();

    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, $attribute = '')
    {
        if ($this->mode == self::TYPE_MODE1 && !preg_match($this->mode1_pattern,$value)) {
            $this->message = $this->mode1_message;
            return false;
        }
        if ($this->mode == self::TYPE_MODE2 && !preg_match($this->mode2_pattern,$value)) {
            $this->message = $this->mode2_message;
            return false;
        }
        if ($this->mode == self::TYPE_MODE3 && !preg_match($this->mode3_pattern,$value)) {
            $this->message = $this->mode3_message;
            return false;
        }
        return parent::validateValue($value, $attribute);
    }
}
