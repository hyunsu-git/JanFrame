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

namespace jan\components\db;

class ConsistentHash
{
    /**
     * @var array 二维数组
     * 以特征作为键，保存所有虚拟位置
     * eg:
     * [
     *  '192.168.0.1'=>[336885862,843497917, ...]
     * ]
     */
    protected $serverList = [];

    /**
     * @var array 保存所有节点
     * eg:
     * [
     *  336885862=>192.168.0.1,
     *  3749174810=>192.168.0.1,
     *  4101403742=>192.168.0.2,
     *  ...
     * ]
     */
    protected $virtualPos = [];

    /**
     * @var int 虚拟节点数量
     */
    public $virtualPosNum = 20;

    /**
     * 将字符串转换为32位无符号整数hash值
     * @param $str
     * @return string
     */
    public function cHash($str)
    {
        $str = md5($str);
        return sprintf('%u', crc32($str));
    }


    /**
     * 添加一台服务器到服务器列表中
     * @param string $server 服务器IP地址
     */
    public function addServer($server)
    {
        if (!isset($this->serverList[$server])) {
            for ($i = 0; $i < $this->virtualPosNum; ++$i) {
                $pos = $this->cHash($server . '-' . $i);
                $this->virtualPos[$pos] = $server;
                $this->serverList[$server][] = $pos;
            }
            ksort($this->virtualPos, SORT_NUMERIC);
        }
    }

    /**
     * 在当前的服务器列表中找一台合适的服务器
     * @param string $key 键名(最好使用随机值)
     * @return mixed 返回服务器IP地址
     */
    public function lookup($key)
    {
        $point = $this->cHash($key);

        // 先取圆环上最小的一个节点当成结果
        $finalServer = current($this->virtualPos);

        // 循环圆环，找到最接近的值
        foreach ($this->virtualPos as $pos => $server) {
            if ($point <= $pos) {
                $finalServer = $server;
                break;
            }
        }

        // 重置圆环的指针为第一个
        reset($this->virtualPos);
        return $finalServer;
    }

    /**
     * 移除一台服务器（循环所有的虚节点，删除值为该服务器地址的虚节点）
     * @param $server
     */
    public function removeServer($server)
    {
        if (isset($this->serverList[$server])) {
            // 删除对应虚节点
            foreach ($this->serverList[$server] as $pos) {
                unset($this->virtualPos[$pos]);
            }

            // 删除对应服务器
            unset($this->serverList[$server]);
        }
    }
}