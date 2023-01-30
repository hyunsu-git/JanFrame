<?php
/**
 * File Name: Command.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 *
 * @author     : hyunsu
 * @date       : 2021/9/27 21:06
 * @email      : hyunsu@foxmail.com
 * @description:
 * @version    : 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace jan\command;


use jan\web\ModelBase;

abstract class Command extends ModelBase
{
    /**
     * @var string 命令简写
     * 比如 list 命令,简写为 -l
     */
    public static $shortName = '';

    /**
     * @var string 命令的说明
     */
    public static $description = '';


    /**
     * 命令接收的参数和说明
     * 例如:
     * [
     *    '-f'=>'要操作的文件路径',
     *    'url'=>'访问的文件的url路径'
     * ]
     *
     * @return array 键值对
     */
    public function options()
    {
        return array();
    }

    /**
     * 实际执行的方法
     *
     * @param array $args 命令行后面跟的参数
     * @return mixed
     */
    abstract public function exec($args);


    public function run() {}
}
