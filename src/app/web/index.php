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


/**
 * 当前的开发环境,不同的开发环境使用不同的配置
 * 取值 DEV,PROD,TEST 不区分大小写
 */
define('ENV', 'DEV');

/**
 * 是否是debug模式
 * debug模式不限制于开发环境,线上环境也可以开启debug模式
 * debug模式下一些会输出一些特定信息,或者详细信息
 * 注:非必要情况,线上环境请关闭debug模式
 */
define('ENV_DEBUG', true);

/**
 * 下面定义了框架中常用的目录常量
 */
define('WEB_PATH', realpath(__DIR__));
define('APP_PATH', dirname(__DIR__));
define('SRC_PATH', dirname(APP_PATH));
define('ROOT_PATH', dirname(SRC_PATH));

/**
 * 加载框架引擎
 */
require ROOT_PATH . '/vendor/framesoft/start.php';
