<?php
/**
 * This file is part of jan-frame.
 *
 * 引导文件,主要用于设置别名,这里设置的别名可以作为自动加载的类名使用
 *
 * 这个文件是框架用的,请不要修改,在项目的`libs`目录下有一个同名文件,请在那里修改
 *
 * Licensed under The MIT License
 *
 * @author    hyunsu<hyunsu@foxmail.com>
 * @link      http://sun.hyunsu.cn
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version   1.0
 *
 */

\jan\basic\Loader::setAlias('@jan', ENGINE_PATH);
\jan\basic\Loader::setAlias('@engine', ENGINE_PATH);
\jan\basic\Loader::setAlias('@src', SRC_PATH);

$user_bootstrap = APP_PATH . DS . 'libs' . DS . 'bootstrap.php';

if (is_file($user_bootstrap)) {
    require_once $user_bootstrap;
}

require ENGINE_PATH . DS . 'Jan.php';