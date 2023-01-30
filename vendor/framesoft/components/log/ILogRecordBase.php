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

namespace jan\components\log;

use jan\basic\Component;
use jan\basic\Exception;
use jan\basic\IException;
use jan\helper\SystemHelper;

/**
 * 日志组件的基类
 * 主要提供:快捷日志记录,日志的格式化
 * 如果用户自定义了日志组件,则需要继承该类,并实现 [write()] 和 [flush()]方法
 */
abstract class ILogRecordBase extends Component
{
    const LEVEL_DEBUG = 1;
    const LEVEL_SUCCESS = 2;
    const LEVEL_INFO = 3;
    const LEVEL_WARNING = 4;
    const LEVEL_ERROR = 5;

    public $ignoreException = [];

    /**
     * @var int[] 要记录的日志级别数组
     */
    public $levels = [self::LEVEL_DEBUG, self::LEVEL_SUCCESS, self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR];

    /**
     * @var int 日志过期时间,单位秒,0永不过期
     */
    public $expireTime = 0;

    /**
     * @var int debug 追溯的层数
     */
    public $debugStackLimit = 15;

    /**
     * 记录日志
     * @param array $content 格式化后的日志内容
     * @param int $level 日志级别
     */
    abstract protected function write($content, $level);

    /**
     * 刷新日志缓存
     * 该函数应该把所有缓存的日志,全部本地化
     * 每次请求结束后会调用该函数
     * 如果是每条日志即时记录,该函数可以为空
     */
    abstract public function flush();

    /**
     * 记录警告日志
     * @param $content
     * @param int $level 记录信息的详细程序,参见[[formatLogMessage]]的$detail_level参数
     */
    public function warning($content, $level = 4)
    {
        $msg = $this->formatLogMessage($content, false, $level);
        $msg['level'] = self::LEVEL_WARNING;
        $this->setName($msg, 'WARNING MESSAGE');
        $this->write($msg, self::LEVEL_WARNING);
    }

    /**
     * @param $content
     * @param $name
     */
    private function setName(&$content, $name)
    {
        if (!isset($content['name']) || empty($content['name'])) {
            $content['name'] = $name;
        }
    }

    /**
     * 记录普通日志
     * @param $content
     * @param int $level 记录信息的详细程序,参见[[formatLogMessage]]的$detail_level参数
     */
    public function info($content, $level = 4)
    {
        $msg = $this->formatLogMessage($content, false, $level);
        $msg['level'] = self::LEVEL_INFO;
        $this->setName($msg, 'NOTICE MESSAGE');
        $this->write($msg, self::LEVEL_INFO);
    }

    /**
     * 记录错误日志
     * @param $content
     * @param int $level 记录信息的详细程序,参见[[formatLogMessage]]的$detail_level参数
     */
    public function error($content, $level = 5)
    {
        $msg = $this->formatLogMessage($content, true, $level);
        $msg['level'] = self::LEVEL_ERROR;
        $this->setName($msg, 'ERROR MESSAGE');
        $this->write($msg, self::LEVEL_ERROR);
    }

    /**
     * 记录成功相关的日志
     * @param $content
     * @param int $level 记录信息的详细程序,参见[[formatLogMessage]]的$detail_level参数
     */
    public function success($content, $level = 2)
    {
        $msg = $this->formatLogMessage($content, false, $level);
        $msg['level'] = self::LEVEL_SUCCESS;
        $this->setName($msg, 'SUCCESS MESSAGE');
        $this->write($msg, self::LEVEL_SUCCESS);
    }

    /**
     * 记录debug相关信息,该函数在非debug模式下无效
     * @param $content
     * @param int $level 记录信息的详细程序,参见[[formatLogMessage]]的$detail_level参数
     */
    public function debug($content, $level = 5)
    {
        if (ENV_DEBUG || ENV_DEV) {
            $msg = $this->formatLogMessage($content, true, $level);
            $msg['level'] = self::LEVEL_DEBUG;
            $this->setName($msg, 'DEBUG MESSAGE');
            $this->write($msg, self::LEVEL_DEBUG);
        }
    }

    /**
     * 格式化日志消息
     * 附加上当前时间,内存概况,堆栈追溯
     *
     * @param mixed $obj 消息体,可以是任意类型数据
     * @param bool $debug 该参数已废弃,保留只是为了兼容性
     * @param int $detail_level 记录消息的详细程度,默认5级
     *                          1 只记录信息
     *                          2 1级基础上 + 时间 + 客户端地址
     *                          3 2级基础上 + 内存使用 + 堆栈信息
     *                          4 3级基础上 + 请求参数
     *                          5 4级基础上 + $_SERVER 信息
     * @return array
     * @time 2019-08-17 17:29
     */
    protected function formatLogMessage($obj, $debug = false, $detail_level = 5)
    {
        $message = array();

        if ($obj instanceof \Exception || $obj instanceof \Throwable) {

            if ($obj instanceof IException) {
                $message['name'] = $obj->getName();
            } else {
                $message['name'] = get_class($obj);
            }
            $file = str_replace(APP_PATH, '', $obj->getFile());
            $message['message'] = trim($obj->getMessage()) . " in " . $file . " on " . $obj->getLine();
        } else {
            $message['name'] = 'User Message';
            $message['message'] = $obj;
        }

        if ($detail_level > 1) {
            $message['timestamp'] = microtime(true);
            $message['datetime'] = date("Y-m-d H:i:s", $message['timestamp']);
            $message['level'] = '';
            $message['REMOTE_ADDR'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            $message['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        }

        if ($detail_level > 2) {
            $message['usage'] = [];
            $message['usage'][] = SystemHelper::memory_get_usage();
            $message['usage'][] = SystemHelper::memory_get_peak_usage();

            $message['trace'] = [];

            if ($debug || ENV_DEBUG === true || ENV_DEV) {
                if ($obj instanceof Exception || $obj instanceof \Error) {
                    $trace = $obj->getTrace();
                } else {
                    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->debugStackLimit);
                    //去掉第一层堆栈,也就是方法自身
                    if (isset($trace[0]) && isset($trace[0]['function']) && $trace[0]['function'] == 'formatLogMessage') {
                        unset($trace[0]);
                    }
                    $message['trace'] = [];
                }
                foreach ($trace as $item) {
                    if (isset($item['file'])) {
                        $message['trace'][] = str_replace(APP_PATH, '', "{$item['file']} on line {$item['line']}");
                    } else if (isset($item['class']) && isset($item['function'])) {
                        $message['trace'][] = "[memory] call {$item['class']}\\{$item['function']}";
                    }
                }

            }
        }

        if ($detail_level > 3) {
            $message['request'] = [];
            $message['request']['GET'] = $_GET;
            $message['request']['POST'] = $_POST;
            $message['request']['body'] = file_get_contents('php://input');
        }

        if ($detail_level > 4) {
            $message['SERVER'] = $_SERVER;
        }

        return $message;
    }
}