<?php
/**
 * File Name: VersionCmd.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/9/27 22:19
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */


namespace jan\command;

/**
 * Class VersionCmd
 */
class VersionCmd extends Command
{
    public static $shortName = '-v';

    public static $description = '查看框架的版本号';

    /**
     * 实际执行的方法
     * @param array $args 命令行后面跟的参数
     * @return mixed
     */
    public function exec($args)
    {
        echo "框架版本：" . FRAME_VERSION . PHP_EOL;
        return true;
    }

}
