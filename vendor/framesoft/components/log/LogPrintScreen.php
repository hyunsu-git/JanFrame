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

use Jan;
use jan\components\response\Response;
use jan\helper\StringHelper;
use jan\helper\VarDumper;

/**
 * 将日志直接打印到屏幕上
 */
class LogPrintScreen extends ILogRecordBase
{
    /**
     * @inheritDoc
     */
    protected function write($content, $level)
    {
        if (IS_CLI) {
            $this->printConsole($content, $level);
        }else{
            $this->printWeb($content, $level);
        }
    }

    protected function printWeb($content, $level)
    {
        Jan::$app->response->format = Response::FORMAT_RAW;
        VarDumper::dump($content);
    }

    protected function printConsole($content, $level)
    {
        switch ($level) {
            case self::LEVEL_DEBUG:
                echo StringHelper::commandColor(print_r($content, true),
                        COMMAND_COLOR_CYAN, COMMAND_COLOR_BLACK,
                        false,false,false,false
                    ) . PHP_EOL;
                break;
            case self::LEVEL_SUCCESS:
                echo StringHelper::commandColor(print_r($content, true),
                        COMMAND_COLOR_GREEN) . PHP_EOL;
                break;
            case self::LEVEL_WARNING:
                echo StringHelper::commandColor(print_r($content, true),
                        COMMAND_COLOR_YELLOW) . PHP_EOL;
                break;
            case self::LEVEL_ERROR:
                echo StringHelper::commandColor(print_r($content, true),
                        COMMAND_COLOR_RED) . PHP_EOL;
                break;
            default:
                echo StringHelper::commandColor(print_r($content, true)) . PHP_EOL;
        }
    }


    /**
     * @inheritDoc
     */
    public function flush()
    {

    }

    public function run()
    {

    }
}