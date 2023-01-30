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

namespace jan\components\session;

use Jan;

/**
 * Class SessionRedis
 * 将Session保存到redis中
 * 重写父类 getUseCustomStorage() 方法返回 true,用于开启自定义存储
 * 重写了几个相关的方法
 */
class SessionRedis extends Session
{

    /**
     * @var \jan\components\redis\Redis
     */
    public $redis;

    /**
     * @var string 存储在redis中键的格式
     */
    public $key = 'session:%s:s';

    /**
     * @var integer session过期时间,默认使用配置文件的时间
     */
    public $gcMaxlifetime;


    public function init()
    {
        parent::init();
        if ($this->gcMaxlifetime === null) {
            $this->gcMaxlifetime = ini_get('session.gc_maxlifetime');
        }
    }

    /**
     * @inheritDoc
     */
    public function getUseCustomStorage()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function openSession($savePath, $sessionName)
    {
        $this->redis = Jan::$app->redis;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function readSession($id)
    {
        $key = sprintf($this->key, $id);
        $data = $this->redis->get($key);
        if(empty($data)) return '';
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function writeSession($id, $data)
    {
        $key = sprintf($this->key, $id);
        if ($this->gcMaxlifetime > 0) {
            return $this->redis->setex($key, $this->gcMaxlifetime, $data);
        }else{
            return $this->redis->set($key, $data);
        }
    }

    /**
     * @inheritDoc
     */
    public function destroySession($id)
    {
        $key = sprintf($this->key, $id);
        $this->redis->del($key);
    }

    /**
     * @inheritDoc
     */
    public function gcSession($maxLifetime)
    {
        return parent::gcSession($maxLifetime);
    }
}