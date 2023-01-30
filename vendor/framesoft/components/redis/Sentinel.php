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

namespace jan\components\redis;

use Jan;
use jan\basic\Component;
use jan\basic\Exception;
use jan\helper\ArrayHelper;

class Sentinel extends Component
{
    /**
     * @var array 配置项
     */
    protected $config;

    /**
     * @var string 主服务器配置文件中的名称
     * 关于哨兵的部分相关配置:
     * # 配置监听的主服务器，这里sentinel monitor代表监控，mymaster代表服务器的名称，可以自定义，192.168.11.128代表监控的主服务器，6379代表端口，2代表只有两个或两个以上的哨兵认为主服务器不可用的时候，才会进行failover操作。
     * sentinel monitor mymaster 192.168.11.128 6379 2
     */
    protected $_master_name;

    /**
     * @var SentinelClient[]
     */
    protected $_clients = array();

    /**
     * @var array  存储主Redis服务器的信息
     * 键是 masterName ,值是信息的数组
     */
    protected $_masters;

    /**
     * @var array 存储从Redis服务器的信息
     * 索引数组
     */
    protected $_slaves;

    /**
     * @var bool 是否连接过的标记
     */
    protected $_connected = false;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->_master_name = ArrayHelper::getValue($this->config,'masterName');

        $config = ArrayHelper::getValue($this->config, 'sentinels');

        foreach ($config as $item) {
            $this->addClient(new SentinelClient($item['host'], $item['port']));
        }
    }

    /**
     * 连接 sentinel 获取主从服务器的信息
     * 只会连接一次
     */
    protected function connect()
    {
        if (!$this->_connected) {
            $this->connectEachIfNotConnected();
            $this->_connected = true;
        }
    }

    /**
     * 连接 sentinel 获取主从服务器的信息
     * - 循环连接每个 sentinel
     * - 第一个连接失败,通知管理员,连接成功直接返回
     * - 尝试连接第二个
     */
    protected function connectEachIfNotConnected()
    {
        foreach ($this->_clients as $client) {
            try {
                $this->_masters = $client->masters();
                $this->_slaves = $client->slaves($this->_master_name);
                return;
            } catch (RedisSentinelConnectException $exception) {
                $this->outputException($client,$exception);
            }
        }
    }

    /**
     * 输出 sentinel 连接
     * @param SentinelClient $Client
     * @param Exception $exception
     */
    protected function outputException(SentinelClient $Client, Exception $exception) {
        $output = __METHOD__ . $Client->getHost() . ":" . $Client->getPort() . " " . $exception->getMessage() . PHP_EOL;
//        print_r($exception);
        Jan::$app->log->error($output);
        Jan::$app->log->error($exception);
        //todo 通知管理员哨兵出现问题
    }

    /**
     * @param SentinelClient $client
     */
    public function addClient(SentinelClient $client)
    {
        $this->_clients[] = $client;
    }

    /**
     * 获取主 Redis 服务器的相关信息
     * @see SentinelClient::masters()
     * @return array
     */
    public function getMaster() {
        $this->connect();
        $masters = array();
        foreach ($this->_masters as $master) {
            $masters[$master['name']] = $master;
        }
        return $masters[$this->_master_name];
    }

    /**
     * 获取所有从Redis服务器的信息
     * @see SentinelClient::slaves()
     * @return array
     */
    public function getSlaves() {
        $this->connect();
        $slaves = array();
        foreach ($this->_slaves as $slave){
            if (!empty($slave) && $slave['flags'] == 'slave') {
                $slaves[] = $slave;
            } else {
                throw new RedisSentinelConnectException("The slave redis information is empty");
            }
        }
        return $slaves;
    }

    /**
     * 随机获取一台从服务器的信息
     * @see SentinelClient::slaves()
     * @return array
     */
    public function getSlave() {
        $slaves = $this->getSlaves();
        $idx = rand(0, count($slaves) - 1);
        return $slaves[$idx];
    }

    public function run()
    {
    }
}