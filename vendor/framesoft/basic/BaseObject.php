<?php
/**
 * This file is part of jan-frame.
 *
 * Licensed under The MIT License
 *
 * @author    hyunsu<hyunsu@foxmail.com>
 * @link      http://jan.hyunsu.cn
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version   1.0
 *
 * ============================= 重大版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace jan\basic;

use jan\helper\StringHelper;

/**
 * 框架中最基本的类，所有类都继承自此类
 * 除了提供几个最基本的方法，主要用于指示运行时堆栈中的框架文件
 */
class BaseObject
{
    /**
     * 通过配置数组初始化对象的属性
     *
     * @param array $config 键值对
     */
    protected function __construct($config = [])
    {
        if (!empty($config) && is_array($config)) {
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }
        $this->init();
    }

    /**
     * @return string 返回类的完全限定名称
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * 预留的初始化方法，子类可以重写
     * 该方法在对象构造完成后自动调用
     */
    protected function init() {}

    /**
     * 获取类中的属性
     * 如果访问不存在的属性或私有属性，请将其转换为调用 "getter" 形式的函数
     * 例如：
     * 访问某个类中的属性 $name，如果该属性不存在或者是私有属性，则会调用 $class->getName() 方法
     *
     * @param $name
     * @return mixed
     * @throws UnknownPropertyException
     * @throws InvalidCallException
     */
    public function __get($name)
    {
        $getter = 'get' . StringHelper::caseCamel($name);
        if (method_exists($this, $getter)) {
            // read property, e.g. getName()
            return $this->$getter();
        }

        if (method_exists($this, 'set' . StringHelper::caseCamel($name))) {
            throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
        }

        throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    /**
     * 设置类中的属性
     * 如果访问不存在的属性或私有属性，请将其转换为调用 "setter" 形式的函数
     * 例如：
     * 设置某个类中的属性 $name，如果该属性不存在或者是私有属性，则会调用 $class->setName() 方法
     *
     * @param $name
     * @param $value
     * @throws UnknownPropertyException
     * @throws InvalidCallException
     */
    public function __set($name, $value)
    {
        $setter = 'set' . StringHelper::caseCamel($name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }

        if (method_exists($this, 'get' . StringHelper::caseCamel($name))) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        }

        throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
    }

    /**
     * 对类中不存在的属性或私有属性判定转成 "getter" 形式的函数调用
     * 函数返回 `null` 时，`isset()` 方法会返回 `false`，否则返回 `true`
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        $getter = 'get' . StringHelper::caseCamel($name);
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        }

        return false;
    }

    /**
     * 对类中不存在的属性或私有属性调用`unset`方法时转成 "setter" 形式的函数调用，并将 `null` 作为参数传入
     *
     * @param $name
     */
    public function __unset($name)
    {
        $setter = 'set' . StringHelper::caseCamel($name);
        $getter = 'get' . StringHelper::caseCamel($name);
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } else {
            if (method_exists($this, $getter)) {
                throw new InvalidCallException('Unsetting an unknown or read-only property: ' . get_class($this) . '::' . $name);
            }
        }
    }

    /**
     * 返回类中是否存在某个方法
     * 默认是直接调用PHP原生方法 `method_exists`
     * 但是可以通过重写该方法来实现更复杂的判断
     *
     * @return bool
     */
    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }
}