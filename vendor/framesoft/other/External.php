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

namespace jan\other;

use Jan;
use jan\basic\Component;
use jan\components\db\Connection;
use jan\components\redis\Redis;

class External extends Component
{
    /**
     * 获取一个数据库操作实例
     * 编写服务层代码，都应该使用该方法获取数据库操作句柄，以便适配多数据库的情况
     *
     * @param array $configs 传入该参数,会作为db组件的配置,生成一个新的操作实例
     * @return Connection
     */
    public function getDB($configs = [])
    {
        if (empty($configs)) {
            return Jan::$app->db;
        } else {
            return new Connection([
                'configs' => $configs
            ]);
        }
    }

    /**
     * 获取一个redis操作实例
     * 编写服务层代码，都应该使用该方法获取redis操作句柄，以便适配多redis的情况
     *
     * @param array $config 传入该参数,会作redis组件的配置,生成一个新的操作实例
     * @return Redis
     */
    public function getRedis($config = [])
    {
        if (empty($config)) {
            return Jan::$app->redis;
        } else {
            return new Redis(['config' => $config]);
        }
    }

    public function run() {}
}