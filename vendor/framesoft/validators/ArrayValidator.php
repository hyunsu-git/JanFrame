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
use jan\helper\ArrayHelper;

/**
 * Class ArrayValidator
 * 一维数组验证器
 */
class ArrayValidator extends Validator
{
    /**
     * @var string|array|callable 元素类型,
     * 如果是字符串，可以设置为各种验证器的字符串，非验证器的字符串，作为类中的自定义方法调用
     * 如果是数组，表示单条验证规则
     * 如果是方法，则直接调用
     * eg：
     * type=>'id'
     * type=>['string','max'=>100]
     * type=>function()
     * type=>'customerFun'
     */
    public $type;

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

        if (empty($this->type)) return true;

        // 取验证器名字
        if (is_array($this->type)) {
            $vl_str = $this->type[0];
        } else {
            $vl_str = $this->type;
        }
        if (empty(self::$_builtValidators)) {
            self::$_builtValidators = ArrayHelper::merge(self::$builtValidators, Jan::getConfig('validator', []));
        }
        $vk = array_keys(self::$_builtValidators);
        // 判断是否是验证器
        $is_validator = in_array($vl_str, $vk);

        $inst = null;
        if ($is_validator) {
            // 重新组装验证规则
            $rule = $this->type;
            if (is_array($rule)) {
                array_unshift($rule, $attribute);
            } else {
                $rule = [$attribute, $rule];
            }
            // 是验证器，则初始化验证器
            $inst = self::createValidatorByRule($rule, $this->model);

            foreach ($value as $item) {
                if (!$inst->validateValue($item, $attribute)) {
                    return false;
                }
            }

        } else {
            foreach ($value as $item) {
                if (is_callable($this->type)) {
                    $result = call_user_func($this->type, $item, $attribute);
                } else {
                    // 其它字符串作为自定义方法校验,参数是数组的 元素
                    if (!$this->model->hasMethod($this->type)) {
                        throw new InvalidValidationRule("Validation rule '{$this->type}' not found in class " . get_class($this->model));
                    }
                    $result = call_user_func(array($this->model, $this->type), $item, $attribute);
                }
                if (!$result) {
                    return false;
                }
            }
        }

        return true;
    }
}
