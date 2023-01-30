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


define('FRAME_NAME', 'JanFrame');

define('FRAME_VERSION', '1.0.0');

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

define('START_TIME', microtime(true));

defined('ENV_DEBUG') or define('ENV_DEBUG', false);

defined('ENV') or define('ENV', 'PROD');
define('ENV_DEV', strtoupper(ENV) == 'DEV');
define('ENV_PROD', strtoupper(ENV) == 'PROD');
define('ENV_TEST', strtoupper(ENV) == 'TEST');

defined('WEB_MODE') or define('WEB_MODE', true);
defined('CLI_MODE') or define('CLI_MODE', false);
defined('COMMAND_MODE') or define('COMMAND_MODE', false);

defined('VENDOR_PATH') or define('VENDOR_PATH', ROOT_PATH . DS . 'vendor');
defined('ENGINE_PATH') or define('ENGINE_PATH', VENDOR_PATH . DS . 'framesoft');

define('IS_CLI', PHP_SAPI == 'cli');

/**
 * 标记是否在Worker模式下运行
 * 即是否使用命令行启动start.php文件
 */
defined('WORKER_MODE') or define('WORKER_MODE', false);

/**
 * 某个子进程运行中引发异常是否结束所有进程,停止命令行的运行
 * 如果设置为false,则某个子进程引发异常后,会退出当前子进程并重启新的子进程,对其他子进程没有影响
 * 该设置对启动阶段不生效,启动阶段任何子进程引发异常都将终止所有进程
 *
 * 默认随着DEBUG模式设置
 * 建议在开发阶段设置为true,方便查找错误,在线上设置false,获得更高的兼容性
 * 该设置不影响错误输出和记录
 */
defined('EXCEPTION_END_ALL_PROCESS') or define('EXCEPTION_END_ALL_PROCESS', ENV_DEBUG);

defined('APP_CONF_PATH') or define('APP_CONF_PATH', APP_PATH . DS . 'config');
defined('APP_LIBS_PATH') or define('APP_LIBS_PATH', APP_PATH . DS . 'libs');

require ENGINE_PATH . DS . 'basic' . DS . 'Loader.php';

$classes = require ENGINE_PATH . DS . 'classes.php';
$user_classes_file = APP_PATH . DS . 'libs' . DS . 'classes.php';
if (is_file($user_classes_file)) {
    $user_classes = require $user_classes_file;
    if (is_array($user_classes)) {
        $classes = array_merge($classes, $user_classes);
    }
}

\jan\basic\Loader::register($classes);

require ENGINE_PATH . DS . 'bootstrap.php';

if (COMMAND_MODE) {
    // 命令模式下不执行其它操作
}else{
    require __DIR__ . DS . 'init.php';
    (new Init())->start();
}



