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
 * Class SorterValidator
 * 排序规则校验器
 */
class SorterValidator extends Validator
{
    /**
     * 类型1,键为字段,值为规则
     * 这种方式可以对多个字段排序
     * eg: ['id'=>'asc','name'=>'desc']
     */
    const TYPE_MODE1 = 'mode1';

    /**
     * 类型2,field下标表示字段,order下标表示规则
     * 这种方式,只能对单个字段排序
     * eg ['field'=>'id','order'=>'desc']
     */
    const TYPE_MODE2 = 'mode2';

    /**
     * @var string 数据表字段名称校验正在表达式
     */
    public $pattern = '/^[a-zA-Z0-9_]+$/';

    /**
     * 排序规则采用的模式
     * 可以是数组,表示支持多种模式,满足任何一种都可以
     * @var string[]|string
     */
    public $mode = [self::TYPE_MODE1, self::TYPE_MODE2];

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
        if (!is_array($value)) {
            $this->message = '{attribute} must be an array.';
            return false;
        }

        $mode = $this->mode;
        if (is_string($mode)) $mode = [$mode];

        if (in_array(self::TYPE_MODE1, $mode)) {
            if($this->validateMode1($value)) return true;
        }
        if (in_array(self::TYPE_MODE2, $mode)) {
            if($this->validateMode2($value)) return true;
        }

        return false;
    }

    /**
     * 模式1验证
     * @param $value
     * @return bool
     */
    protected function validateMode1($value)
    {
        foreach ($value as $k=>$v) {
            if (!is_string($k) || !preg_match($this->pattern, $k) || !is_string($v)) {
                return false;
            }
            $v = strtoupper($v);
            if ($v != 'ASC' && $v != 'DESC') {
                return false;
            }
        }

        return true;
    }

    /**
     * 模式2验证
     * @param $value
     * @return bool
     */
    protected function validateMode2($value)
    {
        if (isset($value['field']) && isset($value['order'])) {
            if (preg_match($this->pattern, $value['field']) && in_array(strtoupper($value['order']), ['ASC', 'DESC'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * 将排序参数格式化成mode1格式
     * @param $value
     * @return array|mixed
     */
    public static function format2Mode1($value)
    {
        if (isset($value['field']) && isset($value['order'])) {
            $value = [$value['field'] => $value['order']];
        }
        return $value;
    }
}

