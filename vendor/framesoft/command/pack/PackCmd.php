<?php
/**
 * File Name: PackCmd
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2022-01-08 11:36 上午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace jan\command\pack;


use jan\command\Command;

/**
 * Class PackCmd
 * 管理扩展包的命令
 */
class PackCmd extends Command
{
    /**
     * @var string 命令简写
     */
    public static $shortName = '-P';

    /**
     * @var string 命令的说明
     */
    public static $description = '扩展包管理工具';

    /**
     * 命令接收的参数和说明
     * @return array 键值对
     */
    public function options()
    {
        return array(
            'search' => '<name>      搜索扩展包',
            'install' => '<name>      安装扩展包',
            'upload' => '<dir>       上传扩展包',
        );
    }

    /**
     * @inheritDoc
     */
    public function exec($args)
    {
        if (isset($args['search'])) {

        }
    }


}
