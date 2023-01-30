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

namespace app\modules\demo\controllers;

use jan\web\Controller;

class TestController extends Controller
{
    /**
     * Get 请求  /demo/test/index
     * @return string
     */
    public function actionGetIndex()
    {
        return "Get demo test";
    }
}