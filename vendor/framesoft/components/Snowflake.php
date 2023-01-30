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

namespace jan\components;

use Jan;
use jan\basic\Component;

/**
 * SnowFlake算法是Twitter设计的一个可以在分布式系统中生成唯一的ID的算法
 * 它可以满足每秒上万条消息ID分配的请求，这些消息ID是唯一的且有大致的递增顺序。
 *
 * 1位标识部分：在机器存储中最高位是符号位，正数是0，负数是1，一般生成的ID为正数，所以为0；
 * 41位时间戳部分：这个是毫秒级的时间，一般实现上不会存储当前的时间戳，而是时间戳的差值（当前时间-固定的开始时间），这样可以使产生的ID从更小值开始；41位的时间戳可以使用69年，(1L<< 41) / (1000L * 60 * 60 * 24 * 365) = 69年；
 * 10位节点部分：代表的是这个服务最多可以部署在 2^10 台机器上，也就是 1024 台机器。Twitter实现中使用前5位作为数据中心标识，后5位作为机器标识，可以部署1024个节点；
 * 12位序列号部分：12bit可以代表的最大正整数是 2 ^ 12 - 1 = 4096，也就是说可以用这个 12 bit 代表的数字来区分同一个毫秒内的 4096 个不同的 id。也就是同一毫秒内同一台机器所生成的最大ID数量为4096
 *
 * 算法实现依赖于时间戳，在同一时间戳下，序列号会自增，以确保唯一性
 * 但是由于PHP本身的脚本特性，在高并发情况下，依然无法避免重复ID的产生，必须借助某个外部存储来保证唯一性
 * 本类中利用了Redis的incrby命令实现
 */
class Snowflake extends Component
{
    /**
     * 时间戳长度
     */
    const MAX_TIMESTAMP_LENGTH = 41;

    /**
     * 数据中心长度
     */
    const MAX_DATACENTER_LENGTH = 5;

    /**
     * 机器号长度
     */
    const MAX_WORKID_LENGTH = 5;

    /**
     * 序列号长度，用来记录同毫秒内产生的不同id
     * 12 即 2^12-1=4095
     * 表示同一机器同一时间截（毫秒)内产生的4095个ID序号
     * 在机器性能满足的情况下，可以适当增大该数字，在1毫秒能产生更多的id
     */
    const MAX_SEQUENCE_LENGTH = 12;

    /**
     * @var int 数据中心编号
     */
    public $dataCenter = 1;

    /**
     * @var int 机器编号
     */
    public $workId = 1;

    /**
     * 固定的开始时间,默认为 2020-01-01的毫秒时间戳
     * 设置为int类型表示毫秒时间戳,string类型会尝试进行转换,转换失败会抛出异常
     *
     * @var int|string
     */
    public $startTime = 1577808000000;

    /**
     * @var bool 最终返回是否要转换成string类型
     */
    public $dataTypeString = false;

    /**
     * @var bool 是否在多线程下同步
     * @see id() 方法的第二个参数
     */
    public $synchronized = false;

    /**
     * 获取唯一id
     * 注意：直接调用该方法，在毫秒级并发下可能存在生成重复id的问题
     * 要解决重复问题，需要将第二个参数设置为true，并启动同目录下 `snowflake_worker.php` 文件
     *
     * 笔者在自己电脑的测试数据，测试没有任何逻辑，就是单纯的循环调用该函数，结果仅供参考
     * 直接本地生成id，每秒生成约 200 万个id
     * 开启线程同步后，依赖redis，每秒生成约 1.4万 个id
     *
     * @param bool      $string_type  true返回类型为string,false 返回类型为int
     * @param bool|null $synchronized 是否在多线程下同步，解决高并发下可能重复的问题
     *                                由于PHP脚本语言的性质，导致多个请求之间的数据无法同步
     *                                这也就导致了PHP下的雪花算法，在高并发的情况下依然会重复的问题
     *                                （java或者go之类语言的雪花算法可以通过单例模式+线程安全避免这一点）
     *                                开启该选项后，将依赖redis组件，需要在配置文件中配置redis组件
     * @return int|string
     */
    public function id($string_type = null, $synchronized = null)
    {
        return $this->generateId($string_type, $synchronized);
    }

    /**
     * 生成雪花id
     *
     * @param bool $string_type  true返回类型为string,false 返回类型为int
     * @param bool $synchronized 是否在多线程下同步
     * @return int|string
     */
    protected function generateId($string_type = null, $synchronized = null)
    {
        $current_time = self::getCurrentMicrotime();

        while (($sequence = $this->sequence($current_time, $synchronized)) > (-1 ^ (-1 << self::MAX_SEQUENCE_LENGTH))) {
            usleep(1);
            $current_time = self::getCurrentMicrotime();
        }

        $tp = $current_time - $this->getStartTimestamp();
        // 计算各数据段的偏移距离
        $worker_left_move = self::MAX_SEQUENCE_LENGTH;
        $data_center_left_move = self::MAX_WORKID_LENGTH + $worker_left_move;
        $timestamp_left_move = self::MAX_DATACENTER_LENGTH + $data_center_left_move;

        // 最后的拼装
        $id = ($tp << $timestamp_left_move)
            | ($this->dataCenter << $data_center_left_move)
            | ($this->workId << $worker_left_move)
            | ($sequence);

        if ($string_type === null) $string_type = $this->dataTypeString;
        if ($string_type) {
            $id = (string)($id);
        }

        return $id;
    }

    /**
     * @return int 获取当前毫秒时间戳
     */
    public static function getCurrentMicrotime()
    {
        return floor(microtime(true) * 1000) | 0;
    }

    /**
     * @return int 获取开始的时间戳
     */
    protected function getStartTimestamp()
    {
        if (!$this->startTime) return 0;

        if (is_numeric($this->startTime)) {
            return $this->startTime;
        }

        return strtotime($this->startTime) * 1000;
    }

    /**
     * @var int 序列号生成器的辅助变量
     */
    private $last_timestamp = 0;

    /**
     * @var int 序列号生成器的辅助变量
     */
    private $last_sequence = 0;

    /**
     * 本机提供序列号
     * 保证同一毫秒生成的序列号唯一
     *
     * @param int  $current_time 当前时间戳
     * @param bool $synchronized 是否在多线程下同步，解决高并发下可能重复的问题
     * @return int|mixed
     */
    protected function sequence($current_time, $synchronized = null)
    {
        if ($synchronized === null) $synchronized = $this->synchronized;
        if ($synchronized) {
            return $this->sequenceSync($current_time);
        } else {
            return $this->sequenceLocal($current_time);
        }
    }

    /**
     * 本机提供序列号
     * 保证同一毫秒生成的序列号唯一
     * 依赖于时间戳，多线程并发时可能会出现序列号重复的情况
     *
     * @param $current_time
     * @return int|mixed
     */
    protected function sequenceLocal($current_time)
    {
        if ($this->last_timestamp === $current_time) {
            $this->last_sequence++;
            $this->last_timestamp = $current_time;
            return $this->last_sequence;
        }

        $this->last_sequence = 0;
        $this->last_timestamp = $current_time;
        return 0;
    }

    /**
     * 线程同步提供序列号
     * 保证多线程同一毫秒生成的序列号唯一
     * 这里依赖于 redis 组件
     *
     * @param $current_time
     * @return int|mixed
     */
    protected function sequenceSync($current_time)
    {
        $key = "_snowflake:sequence:{$this->dataCenter}:{$this->workId}:{$current_time}";

        $sequence = Jan::$app->redis->incr($key);
        Jan::$app->redis->expire($key, 1);

        return $sequence;
    }

    public function run() {}
}