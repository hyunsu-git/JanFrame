<?php
/**
 * File Name: HelpCmd.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/9/27 22:36
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */


namespace jan\command;


use Jan;
use jan\helper\StringHelper;

class HelpCmd extends Command
{

    public static $shortName = '-h';

    public static $description = '[<cmd>] 查看帮助信息';

    public function options()
    {
        return [
            '<cmd>' => '查看某个具体命令的帮助信息',
        ];
    }

    /**
     * 实际执行的方法
     * @param array $args 命令行后面跟的参数
     */
    public function exec($args)
    {
        if (empty($args)) {
            $this->common();
        }else{
            $keys = array_keys($args);
            if (count($keys) > 0 && $keys[0]) {
                // 查看某个命令的帮助
                if (!$this->cmd($keys[0])) {
                    echo StringHelper::command_color("Nonexistent command: {$keys[0]}",COMMAND_COLOR_RED) . PHP_EOL;
                    $this->common();
                }
            }else{
                $this->common();
            }
        }
    }


    private function cmd($cmd)
    {
        $config = Jan::getConfig('command');
        if (isset($config[$cmd])) {
            /**
             * @var Command $obj
             */
            $obj = Jan::createObject($config[$cmd]);
            $print = "命令：{$cmd}
简写：{$obj::$shortName}
命令说明：
  {$obj::$description}
参数列表：
";
            $params = $obj->options();
            foreach ($params as $key=>$val) {
                $print .= ' ' . sprintf('%-10s', $key);
                $print .= ' ' . $val . PHP_EOL;
            }

            echo $print;

            return true;
        }else{
            return false;
        }
    }

    private function common()
    {
        $ary_cmd = [];
        $ary_short = [];
        $ary_desc = [];

        $config = Jan::getConfig('command');
        /**
         * @var $cls Command
         */
        foreach ($config as $cmd => $cls) {
            $ary_cmd[] = ' ' . sprintf('%-10s', $cmd);
            $ary_short[] = ' ' . sprintf('%-5s', $cls::$shortName);
            $ary_desc[] = ' ' . $cls::$description;
        }

        $print = "命令用法：php jan [cmd] [args]。
参数说明：
  参数分为带有短横线（如：-f）和不带短横线（如 file）
  带有短横线的表示下个参数作为值。如 php jan cmd -f test.txt；会将 test.txt 作为参数 -f 的值
  不带短横线的需要用等号连接值。如 php jan cmd file=test.txt；会将 test.txt 作为参数 file 的值 

可用命令列表
";
        for ($i = 0; $i < count($ary_cmd); $i++) {
            $print .= $ary_cmd[$i] . $ary_short[$i] . $ary_desc[$i] . PHP_EOL;
        }

        echo $print;
    }
}
