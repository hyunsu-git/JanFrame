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
 * Class DateValidator
 * 日期时间验证器
 */
class DateValidator extends Validator
{

    /**
     * 类型1,字母开头,可以使用字母数字下划线
     */
    const TYPE_DATE = 'date';
    /**
     * 类型2,中文或字母开头,可以使用字母数字下划线中文
     */
    const TYPE_DATETIME = 'datetime';
    /**
     * 类型3,全中文
     */
    const TYPE_TIME = 'time';

    /**
     * @var string 日期的正则表达式
     */
    public $datePattern = "/^([0-9]{2,4})[\-\/]([0-9]{1,2})[\-\/]([0-9]{1,2})$/";

    /**
     * @var string 时间的正则表达式
     */
    public $timePattern = "/^([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2})$/";

    /**
     * @var string|string[] 日期的格式
     * 可以是字符串或者数组，如果是数组，则表示任何一种
     */
    public $mode = [self::TYPE_DATE, self::TYPE_DATETIME];


    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = '{attribute} is not a valid time';
        }
    }

    /**
     * 验证日期
     * @param $value
     * @return bool
     */
    protected function validateDate($value)
    {
        $pattern = $this->datePattern;

        if (preg_match($pattern, $value, $parts)) {
            return checkdate($parts[2], $parts[3], $parts[1]);
        }else{
            return false;
        }
    }

    /**
     * 验证时间
     * @param $value
     * @return bool
     */
    protected function validateTime($value)
    {
        $pattern = $this->timePattern;

        if (preg_match($pattern, $value, $parts)) {
            if ($parts[1] < 24 && $parts[2] < 60 && $parts[3] < 60) {
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function validateValue($value, $attribute = '')
    {
        if (is_array($this->mode)) {
            if (in_array(self::TYPE_DATE, $this->mode)) {
                if ($this->validateDate($value)) {
                    return true;
                }
            }
            if (in_array(self::TYPE_DATETIME, $this->mode)) {
                $ary = explode(' ', $value);
                if (is_array($ary) && sizeof($ary) == 2 && $this->validateDate($ary[0]) && $this->validateTime($ary[1])) {
                    return true;
                }
            }
            if (in_array(self::TYPE_TIME, $this->mode)) {
                if ($this->validateTime($value)) {
                    return true;
                }
            }
            return false;
        } else {
            if ($this->mode == self::TYPE_DATE) {
                if ($this->validateDate($value)) {
                    return true;
                }
            }
            if ($this->mode == self::TYPE_DATETIME) {
                $ary = explode(' ', $value);
                if (is_array($ary) && sizeof($ary) == 2 && $this->validateDate($ary[0]) && $this->validateTime($ary[1])) {
                    return true;
                }
            }
            if ($this->mode == self::TYPE_TIME) {
                if ($this->validateTime($value)) {
                    return true;
                }
            }
            return false;
        }
    }

}
