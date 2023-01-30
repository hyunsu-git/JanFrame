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

namespace jan\helper;

/**
 * SystemHelper 系统相关的辅助函数
 */
class SystemHelper extends BaseHelper
{
    /**
     * 返回当前分配给PHP脚本的内存量
     * @param bool $format 是否格式化
     * @return int|string 不格式化则返回原始字节,格式化则返回最适合的字符串大小
     */
    public static function memory_get_usage($format = true)
    {
        if (function_exists('memory_get_usage')) {
            $value = memory_get_usage();
            return $format ? FileHelper::convertSuitableUnit($value) : $value;
        } else {
            return 0;
        }
    }

    /**
     * 返回内存使用峰值
     * @param bool $format 是否格式化
     * @return int|string 不格式化则返回原始字节,格式化则返回最适合的字符串大小
     */
    public static function memory_get_peak_usage($format = true)
    {
        if (function_exists('memory_get_peak_usage')) {
            $value = memory_get_peak_usage();
            return $format ? FileHelper::convertSuitableUnit($value) : $value;
        } else {
            return 0;
        }
    }
}