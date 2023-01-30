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

namespace app\controllers;

use Jan;
use jan\web\Controller;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers:token,content-type,authorization,x-requested-with,accept-language');

/**
 * Class CorsController
 * 需要跨域访问的控制器继承该类
 * 需要注意的是:如果子类自定义重写 `beforeAction()` 方法,需要先调用父类方法
 * header中的 `x-requested-with` 参数,为上传时候使用,如果没有上传需求,可以删除
 */
class CorsController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function beforeAction()
    {
        if (Jan::$app->request->isOptions) {
            return false;
        }
        return true;
    }
}