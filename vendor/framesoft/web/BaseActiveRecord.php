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

namespace jan\web;

use ArrayIterator;
use Jan;
use jan\basic\InvalidCallException;
use jan\basic\UnknownPropertyException;

/**
 * ActiveRecord的基类，主要做数据方面的处理
 * 注意：继承自该类后
 *      类中不能有和对应数据表字段名称一样的属性
 *      不建议单独声明public属性，这会造成属性无法跟踪
 *      如果必须要设置public属性，建议使用 `getter` 和 `setter` 方法代替
 *          e.g.
 *          ```php
 *          protected $custom;
 *          public function getCustom();
 *          public function setCustom();
 *          ```
 * @property $scenario string 场景
 * @see BaseActiveRecord::$_attributes 所有外部设置的属性，都实际保存在这个属性中
 */
class BaseActiveRecord extends Model
{
    /**
     * 保存属性和值，并且记录属性的每次变更
     * 这里依赖于魔术方法 `__set()` 实现，所以不建议单独声明public属性
     *
     * 格式为
     * 'field'=>array(
     *      'value' => $value,                  // 属性的当前值
     *      'old_value' => [$old1,$old2,...],   // 属性变更记录
     *      'init_value'=> null                 // 新增的值为null,结果集有值
     *      'read_only' => false,               // 属性是否为只读
     *      'is_updated' => false               // 新增默认为true,结果集赋值默认为false,数据被更新为true
     * )
     *
     * @var array
     */
    protected $_attributes = array();

    public static function instantiate()
    {
        return new static();
    }

    /**
     * @inheritDoc
     */
    public function attributes()
    {
        return array_unique(array_merge(array_keys($this->_attributes), parent::attributes()));
    }

    /**
     * @inheritDoc
     */
    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $e) {
            if (isset($this->_attributes[$name])) {
                return $this->_attributes[$name]['value'];
            }
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $e) {
            if (!isset($this->_attributes[$name])) {
                $this->_attributes[$name] = array(
                    'value'      => $value,
                    'old_value'  => null,
                    'init_value' => null,
                    'read_only'  => false,
                    'is_updated' => true,
                );
            } else {
                $ary = $this->_attributes[$name];
                if ($ary['read_only']) {
                    throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
                }
                $old_value = $ary['old_value'];
                if (is_array($old_value)) {
                    array_unshift($old_value, $ary['value']);
                } else {
                    $old_value = [$ary['value']];
                }
                $this->_attributes[$name] = array(
                    'value'      => $value,
                    'old_value'  => $old_value,
                    'init_value' => $ary['init_value'],
                    'read_only'  => $ary['read_only'],
                    'is_updated' => true,
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function __isset($name)
    {
        if (parent::__isset($name)) {
            return true;
        }
        return isset($this->_attributes[$name]);
    }

    /**
     * @inheritDoc
     */
    public function __unset($name)
    {
        if (parent::__isset($name)) {
            parent::__unset($name);
        } else {
            if (isset($this->_attributes[$name]) && $this->_attributes[$name]['read_only']) {
                throw new InvalidCallException('Unsetting read-only property: ' . get_class($this) . '::' . $name);
            }
            unset($this->_attributes[$name]);
        }
    }

    /**
     * 设置属性的只读属性
     * 如果属性不存在则不进行任何处理
     *
     * @param string $name
     * @param bool   $tag
     */
    public function setAttributeReadonly($name, $tag = true)
    {
        if (isset($this->_attributes[$name])) {
            $this->_attributes[$name]['read_only'] = $tag;
        }
    }


    /**
     * 将数据库查询结果转换成数组
     *
     * @param bool  $updated   只返回更新过的字段
     * @param array $restrains 限制字段,只返回包含在数组中的字段,为空表示不限制
     * @return array
     */
    public function toArray($updated = false, $restrains = null)
    {
        $ary = [];
        foreach ($this->_attributes as $key => $value) {
            if (is_array($restrains) && !in_array($key, $restrains)) {
                continue;
            }
            if ($updated) {
                if ($value['is_updated']) {
                    $ary[$key] = $value['value'];
                }
            } else {
                $ary[$key] = $value['value'];
            }
        }
        return $ary;
    }

    /**
     * @return string 直接输出ActiveRecord对象时,转换为输出json格式的字段
     */
    public function __toString()
    {
        return json_encode($this->getAttrValues());
    }

    /**
     * 此方法是接口 [[\IteratorAggregate]] 必须的
     * 返回一个用于遍历模型中属性值的迭代器
     */
    public function getIterator()
    {
        $attributes = $this->toArray();
        return new ArrayIterator($attributes);
    }

    /**
     * 外部通过键值对赋值
     *
     * @param $row array
     */
    public function populateRecord($row)
    {
        foreach ($row as $attr => $value) {
            $this->_attributes[$attr] = array(
                'value'      => $value,
                'old_value'  => null,
                'init_value' => $value,
                'read_only'  => false,
                'is_updated' => false
            );
        }
    }

    /**
     * 自动参数赋值，该方法是重写Model中的方法
     *
     * 和`Model`中的赋值不同，由于ActiveRecord不建议手动声明public属性，因此这里的自动赋值，是将请求参数，赋值给类
     * 由于该操作存在一定的风险，因此受到以下限制：
     *      1、必须在 `rules()` 方法中定义了验证规则的属性才会被赋值
     *        对此新增了 `safe` 验证器，不做任何校验，表示该值是安全的
     *      2、如果设置了场景scenario属性，则同时受到场景的限制
     *
     * @param string $method 使用的请求方式，传入post|get|route，不传表示使用请求相关的所有参数
     *                       请求相关的参数包括三种：
     *                       1、get参数
     *                       2、post参数
     *                       3、路由参数
     *                       使用的优先级为：路由参数 > get参数 > post参数
     * @param array  $source 使用的数据源，传入该参数，$method 参数无效
     */
    public function autoAssign($method = null, array $source = [])
    {

        if (empty($source)) {
            switch (strtoupper($method)) {
                case self::METHOD_GET:
                    $source = Jan::$app->request->get();
                    break;
                case self::METHOD_POST:
                    $source = Jan::$app->request->post();
                    break;
                case self::METHOD_ROUTE:
                    $source = Jan::$app->request->param();
                    break;
                default:
                    $source = array_merge(
                        Jan::$app->request->post(),
                        Jan::$app->request->get(),
                        Jan::$app->request->param()
                    );
                    break;
            }
        }

        $fields = array_keys($source);
        // 取验证规则设置的字段,和数据源字段的交集
        $fields = array_intersect($fields, $this->ruleFields());
        $fields = $this->filterFieldsByScenario($fields);

        foreach ($source as $field => $val) {
            if (!in_array($field, $fields)) {
                continue;
            }
            if ($this->trimParamsSpace && is_string($val)) {
                $this->$field = trim($val);
            } else {
                $this->$field = $val;
            }
        }
    }

    /**
     *
     * 根据场景过滤字段
     *
     * @param array  $fields
     *
     * @param string $scenario
     *
     * @return array
     *
     */
    protected function filterFieldsByScenario(array $fields, $scenario = null)
    {
        if (empty($scenario)) $scenario = $this->scenario;
        if (empty($scenario)) {
            return $fields;
        }
        $scenarios = $this->getScenarios();
        if (!isset($scenarios[$scenario])) {
            // 未设置场景相关字段
            return [];
        }
        $scenarios = $scenarios[$scenario];
        if ($scenarios === '*') {
            $scenarios = ['*'];
        }
        if ($scenarios[0] === '*') {
            // 表示取反
            unset($scenarios[0]);
            return array_values(array_diff($fields, $scenarios));
        } else {
            return array_values(array_intersect($fields, $scenarios));
        }
    }
}