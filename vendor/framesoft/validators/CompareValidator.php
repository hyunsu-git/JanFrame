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
 * Class CompareValidator
 * 进行对比验证,可以对比具体的值或某个属性
 */
class CompareValidator extends Validator
{
    const TYPE_STRING = 'string';
    const TYPE_NUMBER = 'number';

    /**
     * @var string 将要比较的值作为哪种类型进行比较
     */
    public $type = self::TYPE_STRING;

    /**
     * @var string 要比较的字段名
     */
    public $compareAttribute;

    /**
     * @var string 要比较的值
     */
    public $compareValue;

    /**
     * @var string 进行比较的操作符
     * 可以是 '==','===','!=','!==','>','>=','<','<='
     */
    public $operator = '==';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if ($this->compareAttribute) {
            $field = $this->compareAttribute;

            if (isset($this->model->$field)) {
                $this->compareValue = $this->model->$field;
            } else {
                $this->compareValue = null;
            }
        }

        if ($this->message === null) {
            switch ($this->operator) {
                case '==':
                case '===':
                    $this->message = '{attribute} must be equal to {compareValueOrAttribute}.';
                    break;
                case '!=':
                case '!==':
                    $this->message = '{attribute} must not be equal to {compareValueOrAttribute}.';
                    break;
                case '>':
                    $this->message = '{attribute} must be greater than {compareValueOrAttribute}.';
                    break;
                case '>=':
                    $this->message = '{attribute} must be greater than or equal to {compareValueOrAttribute}.';
                    break;
                case '<':
                    $this->message = '{attribute} must be less than {compareValueOrAttribute}.';
                    break;
                case '<=':
                    $this->message = '{attribute} must be less than or equal to {compareValueOrAttribute}.';
                    break;
                default:
                    throw new InvalidValidationRule("Unknown operator: {$this->operator}");
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value,$attribute = '')
    {
        return $this->compareValues($this->operator, $this->type, $value, $this->compareValue);
    }

    /**
     * 根据操作符合类型对两个值进行比较
     * @param string $operator 操作符
     * @param string $type 类型
     * @param mixed $value 进行比较的值1
     * @param mixed $compareValue 进行比较的值2
     * @return bool
     */
    protected function compareValues($operator, $type, $value, $compareValue)
    {
        if ($type === self::TYPE_NUMBER) {
            $value = (float)$value;
            $compareValue = (float)$compareValue;
        } else {
            $value = (string)$value;
            $compareValue = (string)$compareValue;
        }
        switch ($operator) {
            case '==':
                return $value == $compareValue;
            case '===':
                return $value === $compareValue;
            case '!=':
                return $value != $compareValue;
            case '!==':
                return $value !== $compareValue;
            case '>':
                return $value > $compareValue;
            case '>=':
                return $value >= $compareValue;
            case '<':
                return $value < $compareValue;
            case '<=':
                return $value <= $compareValue;
            default:
                return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function formatError($field, $param = [])
    {
        $ary = [
            'compareValueOrAttribute' => [
                'value'=>$this->compareValue,
                'lang'=>false
            ],
        ];
        if ($this->compareAttribute) {
            $ary['compareValueOrAttribute'] = [
                'value'=>$this->compareAttribute,
                'lang'=>true
            ];
        }
        return parent::formatError($field, $ary);
    }
}
