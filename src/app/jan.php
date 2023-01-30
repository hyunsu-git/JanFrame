#!/usr/bin/env php
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

define('ENV', 'dev');
define('ENV_DEBUG', true);
define('APP_PATH', __DIR__);
define('COMMAND_MODE', true);
define('SRC_PATH', dirname(APP_PATH));
define('ROOT_PATH', dirname(SRC_PATH));

require __DIR__ . "/../../vendor/framesoft/start.php";
require ENGINE_PATH . DS . 'command' . DS . 'init.php';