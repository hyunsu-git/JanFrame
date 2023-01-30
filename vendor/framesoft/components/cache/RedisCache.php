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

use Jan;
use jan\basic\InvalidCallException;
use jan\components\redis\Redis;

/**
 * 使用redis缓存 缓存全部使用hash的形式存放
 */
class RedisCache extends CacheBase
{
    /**
     * @var string 使用的Redis操作句柄在 Jan::$app 中的变量名
     * 也可以通过重写 [[getRedis()]] 函数
     */
    public $redis = 'redis';

    /**
     * @inheritDoc
     */
    public function buildKey($key)
    {
        $key = parent::buildKey($key);
        return $key . ':h';
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        if(!$this->enableCache()) return null;

        $redis = $this->getRedis();
        $key = $this->buildKey($key);
        $ary = $redis->hgetall($key);
        if ($ary) {
            if (isset($ary['_is_string']) && $ary['_is_string']) {
                $value = $ary['_value'];
                if ($ary['_serialize']) {
                    return $this->unserializeValue($value);
                } else {
                    return $value;
                }
            } else {
                return $ary;
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $duration = 0)
    {
        if(!$this->enableCache()) return;

        $redis = $this->getRedis();
        $key = $this->buildKey($key);
        $redis->hmset($key, $this->serializeValue($value));
        if ($duration > 0) {
            $redis->expire($key, $duration);
        }
    }

    /**
     * 将值格式化成数组以便使用hash存储
     * - 值是基础类型的,使用 `_is_string` 标记,存放到`_value`属性,返回一个数组
     * - 值是数组的直接返回
     * - 如果是实现了迭代器的对象,则遍历成数组返回
     * - 如果是其它类型,则进行序列化 使用 `_is_string` 标记,存放到`_value`属性,并将`_serialize` 标记为true,返回一个数组
     * @param mixed $value
     * @return array
     */
    public function serializeValue($value)
    {
        if (is_string($value) || is_numeric($value) || is_bool($value)) {
            return array(
                '_is_string' => true,
                '_value' => $value,
                '_serialize' => false,
            );
        } else if (is_array($value)) {
            return $value;
        } else if ($value instanceof \IteratorAggregate) {
            $ary = [];
            foreach ($value as $k => $v) $ary[$k] = $v;
            return $ary;
        } else {
            return array(
                '_is_string' => true,
                '_value' => parent::serializeValue($value),
                '_serialize' => true,
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function exists($key)
    {
        if(!$this->enableCache()) return false;

        $has = $this->getRedis()->exists($this->buildKey($key));
        return $has == 1;
    }

    /**
     * @inheritDoc
     */
    public function add($key, $value, $duration = 0)
    {
        if(!$this->enableCache()) return false;

        $redis = $this->getRedis();
        $key = $this->buildKey($key);
        if ($redis->exists($key)) {
            return false;
        }
        $this->set($key, $value, $duration);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        if(!$this->enableCache()) return;

        return $this->getRedis()->del($this->buildKey($key));
    }

    /**
     * redis存储方式不能使用全部清理
     */
    public function clear()
    {
        throw new InvalidCallException("The cache is stored in redis and cannot call 'clear()'.");
    }


    /**
     * @return Redis
     */
    protected function getRedis()
    {
        $redis = $this->redis;
        return Jan::$app->$redis;
    }
}