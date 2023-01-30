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

/**
 * 框架整体通过组件式的方式进行开发，所有的组件都继承自Component类
 */
abstract class Component extends BaseObject
{
    /**
     * @var string 组件的唯一编号，由系统自动生成
     */
    protected $_ID;

    /**
     * @var array 这里定义的事件，将自动加入全局事件系统中
     *            定义方式：键为事件名称，值为事件处理函数
     * eg:
     * [
     *    'EVENT_NAME' => function(){},
     * ]
     * @see Event::addEvent()
     */
    protected $events = [];

    /**
     * 该数组中的属性名，会忽略魔术方法
     * 默认情况下，继承自该类后，对于所有不存在的或私有属性的访问都会转成相应的 `getter` 或 `setter` 方法
     * 但是如果你想要访问的属性名在该数组中，则会直接访问该属性
     *
     * @var array
     */
    protected $ignoreMagicMethods = [];

    /**
     * @return array
     */
    public function getIgnoreMagicMethods()
    {
        return $this->ignoreMagicMethods;
    }

    /**
     * @param array $ignoreMagicMethods
     */
    public function setIgnoreMagicMethods($ignoreMagicMethods)
    {
        $this->ignoreMagicMethods = $ignoreMagicMethods;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        if (!is_array($this->events)) {
            $this->events = [];
        }
        return $this->events;
    }

    /**
     * Component constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        if (empty($this->_ID)) {
            $this->_ID = spl_object_hash($this);
        }

        parent::__construct($config);
    }

    /**
     * 组件的入口函数，所有的组件都必须实现该方法，该方法可以为空
     * 组件初始化完成后，会自动调用该方法
     * 引擎保证在初始化后调用此方法，但不保证立即调用它
     */
    abstract public function run();

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        if (in_array($name, $this->ignoreMagicMethods)) {
            return isset($this->$name) ? $this->$name : null;
        }

        return parent::__get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        if (in_array($name, $this->ignoreMagicMethods)) {
            $this->$name = $value;
            return;
        }
        parent::__set($name, $value);
    }

}