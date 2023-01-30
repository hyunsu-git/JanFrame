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


use Jan;
use jan\basic\Component;
use jan\basic\i18n;
use jan\basic\InvalidCallException;
use jan\helper\ArrayHelper;


class Validator extends Component
{
    /**
     * @var array 合并自定义验证器后的配置
     */
    protected static $_builtValidators = [];

    public static $builtValidators = [
        'safe' => '\jan\validators\SafeValidator',
        'boolean' => '\jan\validators\BooleanValidator',
        'bool' => '\jan\validators\BooleanValidator',
        'compare' => '\jan\validators\CompareValidator',
        'email' => '\jan\validators\EmailValidator',
        'in' => '\jan\validators\RangeValidator',
        'integer' => [
            'class' => '\jan\validators\NumberValidator',
            'integerOnly' => true,
        ],
        'int' => [
            'class' => '\jan\validators\NumberValidator',
            'integerOnly' => true,
        ],
        'double' => '\jan\validators\NumberValidator',
        'number' => '\jan\validators\NumberValidator',
        'match' => '\jan\validators\RegularExpressionValidator',
        'required' => '\jan\validators\RequiredValidator',
        'string' => '\jan\validators\StringValidator',
        'url' => '\jan\validators\UrlValidator',
        'idcard' => '\jan\validators\IdcardValidator',
        'account' => '\jan\validators\AccountValidator',
        'array' => '\jan\validators\ArrayValidator',
        'custom' => '\jan\validators\UserValidator',
        'mobile' => [
            'class' => '\jan\validators\RegularExpressionValidator',
            'pattern' => '/^1[345678]\d{9}$/'
        ],
        'unique' => [
            'class' => '\jan\validators\DbValidator',
            'unique' => true,
            'exist' => false,
        ],
        'exist' => [
            'class' => '\jan\validators\DbValidator',
            'unique' => false,
            'exist' => true,
        ],
        'sorter' => '\jan\validators\SorterValidator',
        'id' => '\jan\validators\IdValidator',
        'date' => '\jan\validators\DateValidator',
        'groupAdd' => '\jan\validators\GroupAndValidator',
        '&&' => '\jan\validators\GroupAndValidator',
        'groupOr' => '\jan\validators\GroupOrValidator',
        '||' => '\jan\validators\GroupOrValidator',
    ];

    /**
     * @var string 验证失败,返回的错误信息
     */
    public $message;

    /**
     * @var bool 单条验证规则同时验证多个字段,如果前面字段验证不通过,是否跳过后面的验证
     */
    public $skipOnError = true;

    /**
     * @var bool 如果值为null或空字符串,是否跳过
     */
    public $skipOnEmpty = true;

    /**
     * @var callable 自定义的判断是否为空的函数,默认情况下,null或空字符串或空数组都为空
     */
    public $isEmptyHandle;

    /**
     * @var string 错误信息
     */
    public $error;

    /**
     * @var \jan\web\Model
     */
    public $model;

    /**
     * 根据验证规则创建一个验证器
     * @param array $rule 一条验证规则
     * @param \jan\web\Model $model 验证规则所在的Model实例
     * @return Validator
     */
    public static function createValidatorByRule($rule, $model)
    {
        // 第二元素作为验证类型
        $type = $rule[1];

        $config = (isset($rule[2]) && is_array($rule[2])) ? $rule[2] : [];
        $config['model'] = $model;
        // 循环规则,所有的非数字下标,作为$config一部分
        foreach ($rule as $key => $value) {
            if (is_string($key)) {
                $config[$key] = $value;
            }
        }

        /** @var $validator Validator */
        if (empty(self::$_builtValidators)) {
            // 合并自定义验证器
            self::$_builtValidators = ArrayHelper::merge(self::$builtValidators, Jan::getConfig('validator', []));
        }
        if (!isset(self::$_builtValidators[$type])) {
            $validator = Jan::createObject(self::$_builtValidators['custom'], [$type, $config], null, false);
        } else {
            $validator = Jan::createObject(self::$_builtValidators[$type], $config);
        }
        return $validator;
    }

    /**
     * 判断是否是一个验证器
     * @param string $name
     * @return bool
     */
    public static function isValidator($name)
    {
        if (empty(self::$_builtValidators)) {
            // 合并自定义验证器
            self::$_builtValidators = ArrayHelper::merge(self::$builtValidators, Jan::getConfig('validator', []));
        }

        return isset(self::$_builtValidators[$name]);
    }

    /**
     * 外部调用,进行验证
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function validate($attribute, $value)
    {
        if ($this->skipOnEmpty && $this->isEmpty($value)) {
            // 空值跳过
            return true;
        }

        $result = $this->validateValue($value, $attribute);
        if (!$result) {
            $this->error = $this->formatError($attribute);
        }
        return $result;
    }


    /**
     * 对错误信息进行格式化和翻译
     * @param string $field 字段名称
     * @param array $param 要替换的其他信息,是一个二维数组 格式:
     *              [
     *                  '字段名称'=>['value'=>'要替换的值','lang'=>'是否进行翻译']
     *              ]
     * @return string
     */
    public function formatError($field, $param = [])
    {
        $props = [
            'attribute' => i18n::t($field),
        ];
        foreach ($param as $name => $conf) {
            if (isset($conf['lang']) && $conf['lang']) {
                $props[$name] = i18n::t($conf['value']);
            } else {
                $props[$name] = $conf['value'];
            }
        }
        return i18n::t($this->message, $props);
    }

    /**
     * 返回验证是否有错误
     * @return bool
     */
    public function hasError()
    {
        return !empty($this->error);
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 判断值是否为空
     * @param $value
     * @return bool|mixed
     */
    protected function isEmpty($value)
    {
        if ($this->isEmptyHandle !== null) {
            return call_user_func($this->isEmptyHandle, $value);
        }

//        return $value === null || $value === [] || $value === '';
        return $value !== 0 && $value !== '0' && $value !== false && empty($value);
    }

    /**
     * 子类需要重写该方法,用于验证值是否有效
     * @param mixed $value 属性值
     * @param string $attribute 属性名
     * @return boolean
     */
    public function validateValue($value, $attribute = '')
    {
        throw new InvalidCallException(get_class($this) . ' does not support validateValue().');
    }

    public function run()
    {
    }
}
