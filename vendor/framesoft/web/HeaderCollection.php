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

/**
 * 维护Http请求的header信息，并可以通过数组形式访问
 *
 * @property int            $count    header的数量，该属性只读
 * @property \ArrayIterator $iterator 用于头部信息遍历的迭代器，该属性只读
 */
class HeaderCollection implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * 保存header信息
     * 键是header名称，值是数组，数组的每个元素是一个header值
     *
     * @var array
     */
    private $_headers = [];

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_headers);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->_headers);
    }

    /**
     * 获取指定header的值
     *
     * @param string $name    header名称
     * @param mixed  $default 默认值
     * @param bool   $first   是否只返回指定名称的第一个header信息
     * @return string|array 当$first为true时返回字符串，否则返回数组
     */
    public function get($name, $default = null, $first = true)
    {
        $name = strtolower($name);
        if (isset($this->_headers[$name])) {
            return $first ? reset($this->_headers[$name]) : $this->_headers[$name];
        }

        return $default;
    }

    /**
     * 设置header值
     *
     * @param string       $name
     * @param string|array $value
     * @return $this
     */
    public function set($name, $value = '')
    {
        $name = strtolower($name);
        $this->_headers[$name] = (array)$value;

        return $this;
    }

    /**
     * 在指定header名称下追加一条头信息
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function add($name, $value)
    {
        $name = strtolower($name);
        $this->_headers[$name][] = $value;

        return $this;
    }

    /**
     * 仅当指定header名称不存在时候设置值，如果存在，则忽略本次设置
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setDefault($name, $value)
    {
        $name = strtolower($name);
        if (empty($this->_headers[$name])) {
            $this->_headers[$name][] = $value;
        }

        return $this;
    }

    /**
     * 返回指定header名称是否存在
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        $name = strtolower($name);

        return isset($this->_headers[$name]);
    }

    /**
     * 移除指定header名称
     *
     * @param string $name
     * @return mixed|null 如果存在则返回移除的header值，否则返回null
     */
    public function remove($name)
    {
        $name = strtolower($name);
        if (isset($this->_headers[$name])) {
            $value = $this->_headers[$name];
            unset($this->_headers[$name]);
            return $value;
        }

        return null;
    }

    /**
     * 移出所有header信息
     */
    public function removeAll()
    {
        $this->_headers = [];
    }

    /**
     * 将header信息作为数组返回
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_headers;
    }

    /**
     * 使用数组填充header
     *
     * @param array $array the headers to populate from
     */
    public function fromArray(array $array)
    {
        foreach ($array as $name => $value) {
            $this->add($name, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}
