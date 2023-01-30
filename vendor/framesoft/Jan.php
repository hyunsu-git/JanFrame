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

require __DIR__ . DIRECTORY_SEPARATOR . 'Jan_Base.php';

/**
 * 该类完全继承自[[\Jan_Base]] ,用户可以自定义该文件,但是不能更改类名或删除该文件
 * 该文件在所有用户逻辑执行前加载
 * 可以通过定制该文件实现一些特殊的功能
 *
 * 注意: 自动加载在这之前执行,所以类中可以使用命名空间
 */
class Jan extends Jan_Base
{

}