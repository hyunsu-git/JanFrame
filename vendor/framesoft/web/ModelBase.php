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

use IteratorAggregate;
use ArrayAccess;
use ArrayIterator;
use jan\basic\Component;
use ReflectionClass;
use ReflectionProperty;

/**
 * Class ModelBase
 * 基本Model类,主要是让类实现类似数组的用法
 */
abstract class ModelBase extends Component implements IteratorAggregate,ArrayAccess
{
    /**
     * 返回类中所有的非静态公有属性
     * @return array
     */
    public function attributes()
    {
        $class = new ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }

    /**
     *
     * 返回类中属性值的键值对
     *
     * @param array $names 需要返回的属性名称列表
     *                     默认为空,使用[[attributes()]]方法的返回值
     *
     * @param array $except 需要排除的属性名称列表
     *
     * @return array
     *
     */
    public function getAttrValues(array $names = null, array $except = [])
    {
        $values = [];
        if ($names === null) {
            $names = $this->attributes();
        }
        foreach ($names as $name) {
            $values[$name] = $this->$name;
        }
        foreach ($except as $name) {
            unset($values[$name]);
        }

        return $values;
    }

    /**
     * 此方法是接口 [[\IteratorAggregate]] 必须的
     * 返回一个用于遍历模型中属性值的迭代器
     */
    public function getIterator()
    {
        $attributes = $this->getAttrValues();
        return new ArrayIterator($attributes);
    }

    /**
     * 此方法是SPL接口[[\ArrayAccess]]所必需的
     * 设置指定偏移量处元素的值
     * 使用类似 `$model[$offset] = $value;` 语法时候隐式调用
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * 此方法是SPL接口[[\ArrayAccess]]所必需的
     * 返回在指定偏移量处是否存在元素
     * 使用类似 `isset($model[$offset])` 语法时候隐式调用
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * 此方法是SPL接口[[\ArrayAccess]]所必需的
     * 将指定偏移量处的元素值设置为null
     * 使用类似 `unset($model[$offset])` 语法时候隐式调用
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }

    /**
     * 此方法是SPL接口[[\ArrayAccess]]所必需的
     * 获取指定偏移量处的元素的值
     * 使用类似 `$value = $model[$offset];` 语法时候隐式调用
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

}