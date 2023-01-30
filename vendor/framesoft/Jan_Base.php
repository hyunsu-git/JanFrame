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

use jan\basic\BaseObject;
use jan\basic\Component;
use jan\basic\Event;
use jan\basic\InvalidConfigException;
use jan\di\Container;
use jan\di\NotInstantiableException;
use jan\helper\ArrayHelper;

/**
 * Jan_Base is the core helper class for the framework.
 */
class Jan_Base extends BaseObject
{
    /**
     * @var mixed the singleton instance
     */
    private static $_instance = null;

    /**
     * Get the instance of the class
     * @param array $config Key value pair
     * @return static
     */
    public static function Inst($config = [])
    {
        if (self::$_instance === null) {
            self::$_instance = new static($config);
        }
        return self::$_instance;
    }

    /**
     * Prevent the class from being cloned
     */
    private function __clone()
    {

    }

    /**
     * @var array after merging system configuration and user configuration
     * @see ArrayHelper::merge()
     */
    public static $config = [];

    /**
     * @var \jan\basic\Application
     */
    public static $app;

    /**
     * Get the merged configuration
     * key of array can be dot separated
     * ```php
     * $config = [
     *     'a' => [
     *        'b' => [
     *           'c' => 'd'
     *       ]
     *    ]
     * ];
     * $value = Jan_Base::getConfig('a.b.c'); // return 'd'
     * ```
     *
     * @param string $name the name of the configuration
     * @param mixed  $default the default value if the configuration is not found
     *
     * @return array|string|null
     */
    public static function getConfig($name, $default = null)
    {
        $value = ArrayHelper::getValue(self::$config, $name);
        return $value === null ? $default : $value;
    }

    /**
     * Create an object and component instance
     * can use the full class name or array with `class` element
     * ```php
     * $object = Jan_Base::createObject('jan\basic\Application');
     * $object = Jan_Base::createObject([
     *                          'class' => 'jan\basic\Application',
     *                          'name' => 'JanFrame'
     *                          ...
     *                      ]);
     * ```
     *
     * @param string|array $class       full class name or array with `class` element
     * @param array        $conf        as argument to the constructor
     * @param string       $name        the name of the component
     * @param bool         $isComponent 实例化的是否是组件.该参数在$class为数组时候生效
     *                                  如果是组件,$class和$conf合并作为构造函数的参数
     *                                  非组件, $class作为依赖属性传入
     * @return object
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws NotInstantiableException
     */
    public static function createObject($class, $conf = [], $name = '', $isComponent = true)
    {
        $param = [];

        if (is_array($class)) {
            // $class 是数组,则提取 'class' 下标作为类名
            if (!isset($class['class'])) {
                throw new InvalidConfigException("The configuration of component {$name} must have a key called `class`");
            }
            if ($isComponent) {
                // 组件,则合并 $conf
                $conf = ArrayHelper::merge($class, $conf);
                $class = $conf['class'];
                unset($conf['class']);
            } else {
                $param = $class;
                $class = $param['class'];
                unset($param['class']);
            }
        }

        if ($isComponent) {
            $obj = Container::get($class, [$conf], $param);
        } else {
            $obj = Container::get($class, $conf, $param);
        }

        // 如果是组件,将监听加入事件系统
        if ($obj instanceof Component) {
            foreach ($obj->getEvents() as $en => $ev) {
                // 值是索引数组,就是一个事件多个监听
                if (ArrayHelper::isIndexArray($ev)) {
                    foreach ($ev as $item) {
                        Event::Inst()->addEvent($en, $item);
                    }
                } else {
                    Event::Inst()->addEvent($en, $ev);
                }
            }
        }

        return $obj;
    }
}