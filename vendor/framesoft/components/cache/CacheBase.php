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

namespace jan\components\cache;


use jan\basic\Component;

abstract class CacheBase extends Component implements ICache
{
    /**
     * @var bool 是否允许对存储的值进行序列化
     * 关闭该项省去了序列化的时间,可以提高性能,但是需要确保存入的为缓存系统支持的数据类型(大多数为字符串)
     * 默认是开启序列化
     */
    public $enableSerializeValue = true;

    /**
     * @var string 缓存的前缀,用户在类似Redis这种存储系统中区分键的用途
     * 在不同的缓存系统,前缀的作用不同.具体参见各种缓存具体实现
     */
    public $keyPrefix = '';

    /**
     * @var bool|callable|string|array 是否允许使用缓存
     * 支持一下几种配置
     * 1、直接配置 true 或者 false
     * 2、配置为一个函数返回 true 或者 false
     * 3、配置为字符串或数组，作为 call_user_func() 函数的参数
     */
    public $enableCache = true;

    /**
     * 判断是否允许缓存
     */
    public function enableCache()
    {
        if (is_callable($this->enableCache)) {
            return call_user_func($this->enableCache);
        } else {
            return $this->enableCache;
        }
    }

    /**
     * @inheritDoc
     */
    public function buildKey($key)
    {
        if (is_string($key)) {
            // 包含特殊字符或者超过32位,使用md5摘要
            if (!ctype_alnum($key) || strlen($key) >= 32) {
                $key = md5($key);
            }
        } else {
            $key = md5(serialize($key));
        }

        return $this->keyPrefix . $key;
    }

    /**
     * 对保存的值进行序列化,根据不同存储方式,自定义该方法
     *
     * @param $value
     * @return string
     */
    public function serializeValue($value)
    {
        return $this->enableSerializeValue ? serialize($value) : $value;
    }

    /**
     * 反序列化值,根据不同存储方式,自定义该方法
     *
     * @param $value
     * @return mixed
     */
    public function unserializeValue($value)
    {
        return $this->enableSerializeValue ? unserialize($value) : $value;
    }

    /**
     * Whether a offset exists
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     * @return bool true on success or false on failure.
     *                      </p>
     *                      <p>
     *                      The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * Offset to retrieve
     *
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     *
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * Offset to unset
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        return $this->delete($offset);
    }

    public function run() {}
}