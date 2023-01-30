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

class SentinelClient
{
    /**
     * @var string sentinel的地址
     */
    public $host;

    /**
     * @var integer 端口号
     */
    public $port;

    /**
     * @var resource 保存到sentinel的连接
     */
    protected $_socket;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }


    public function __destruct()
    {
        $this->close();
    }

    /**
     * 获取sentinel的地址
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * 获取sentinel的端口
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * 检查 sentinel 是否可用
     * @return bool
     * @throws RedisSentinelConnectException
     */
    public function ping()
    {
        $this->connect();
        $this->write('PING');
        $this->write('QUIT');
        $data = $this->get();
        $this->close();
        return ($data === '+PONG');
    }

    /**
     * 返回主Redis相关的信息
     * 包括连接地址,端口,名称,最小投票数,id,超时时间等
     * Array
     * (
     * [0] => Array
     * (
     * [name] => mymaster
     * [ip] => 192.168.100.105
     * [port] => 6379
     * [runid] => 918dd513a476625d2425c93161afdade419b4751
     * [flags] => master
     * [link-pending-commands] => 0
     * [link-refcount] => 1
     * [last-ping-sent] => 0
     * [last-ok-ping-reply] => 405
     * [last-ping-reply] => 405
     * [down-after-milliseconds] => 10000
     * [info-refresh] => 2792
     * [role-reported] => master
     * [role-reported-time] => 97797821
     * [config-epoch] => 5
     * [num-slaves] => 2
     * [num-other-sentinels] => 2
     * [quorum] => 2
     * [failover-timeout] => 60000
     * [parallel-syncs] => 2
     * )
     * )
     * @return array
     * @throws RedisSentinelConnectException
     */
    public function masters()
    {
        $this->connect();
        $this->write('SENTINEL masters');
        $this->write('QUIT');
        $data = $this->extract($this->get());
        $this->close();
        return $data;
    }

    /**
     * 获取从服务器的相关信息
     * 返回的信息和格式和主服务一致,信息内容基本一致
     * @param $master
     * @return array
     * @throws RedisSentinelConnectException
     * @see masters()
     * [name] => 192.168.100.109:6381
     * [ip] => 192.168.100.109
     * [port] => 6381
     * [runid] =>
     * [flags] => s_down,slave,disconnected
     * [link-pending-commands] => 3
     * [link-refcount] => 1
     * [last-ping-sent] => 98173187
     * [last-ok-ping-reply] => 98173187
     * [last-ping-reply] => 98173187
     * [s-down-time] => 98163162
     * [down-after-milliseconds] => 10000
     * [info-refresh] => 1597295182837
     * [role-reported] => slave
     * [role-reported-time] => 98173187
     * [master-link-down-time] => 0
     * [master-link-status] => err
     * [master-host] => ?
     * [master-port] => 0
     * [slave-priority] => 100
     * [slave-repl-offset] => 0
     */
    public function slaves($master)
    {
        $this->connect();
        $this->write('SENTINEL slaves ' . $master);
        $this->write('QUIT');
        $data = $this->extract($this->get());
        $this->close();
        return $data;
    }

    /**
     * 将 sentinel 返回的信息格式化成易懂的键值对
     * @param string $data
     * @return array
     */
    protected function extract($data)
    {
        if (!$data) return array();
        $lines = explode("\r\n", $data);
        $is_root = $is_child = false;
        $c = count($lines);
        $results = $current = array();
        for ($i = 0; $i < $c; $i++) {
            $str = $lines[$i];
            $prefix = substr($str, 0, 1);
            if ($prefix === '*') {
                if (!$is_root) {
                    $is_root = true;
                    $current = array();
                    continue;
                } else if (!$is_child) {
                    $is_child = true;
                    continue;
                } else {
                    $is_root = $is_child = false;
                    $results[] = $current;
                    continue;
                }
            }
            $keylen = $lines[$i++];
            $key = $lines[$i++];
            $vallen = $lines[$i++];
            $val = $lines[$i++];
            $current[$key] = $val;
            --$i;
        }
        $results[] = $current;
        return $results;
    }

    /**
     * 发起连接
     * @throws RedisSentinelConnectException
     */
    public function connect()
    {
        $this->_socket = @fsockopen($this->host, $this->port, $errno, $errstr, 1);
        if (!$this->_socket) {
            throw new RedisSentinelConnectException($errstr, $errno);
        }
    }

    /**
     * 关闭到sentinel的连接
     */
    public function close()
    {
        if ($this->_socket !== null) {
            @fclose($this->_socket);
            $this->_socket = null;
        }
    }

    /**
     * @param $content
     * @return false|int
     */
    public function write($content)
    {
        return fwrite($this->_socket, $content . "\r\n");
    }

    /**
     * @return string
     */
    public function get()
    {
        $buf = '';
        while ($this->receiving()) {
            $buf .= fgets($this->_socket);
        }
        return rtrim($buf, "\r\n+OK\n");
    }

    /**
     * @return bool
     */
    protected function receiving()
    {
        return !feof($this->_socket);
    }
}