<?php
/**
 * This file is part of jan-frame.
 *
 * 引导文件,主要用于设置别名，这里设置的别名可以作为自动加载的类名使用
 *
 * 这个文件用户可以自定义该文件，实现自己的加载逻辑
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

\jan\basic\Loader::setAlias('@app', '@src/app');
\jan\basic\Loader::setAlias('@runtime', '@app/runtime');
\jan\basic\Loader::setAlias('@common', ROOT_PATH . '/common');
