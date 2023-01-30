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

namespace jan\components\response;

use Jan;
use jan\web\Model;

trait ResponseTrait
{
    /**
     * 将数据格式化成JSON输出
     *
     * @param $data
     */
    public function asJson($data)
    {
        Jan::$app->response->format = Response::FORMAT_JSON;
        Jan::$app->response->data = $data;
    }

    /**
     * 将数据格式化成xml输出
     *
     * @param $data
     */
    public function asXml($data)
    {
        Jan::$app->response->format = Response::FORMAT_XML;
        Jan::$app->response->data = $data;
    }


    /**
     *
     * 注意: 该方法随着项目不同,可以自行修改返回格式
     *
     * 检查并执行 Model
     * 该方法首先会对Model进行验证(即 `rules()` 方法所写的验证格式)
     * 验证不通过会返回 HTTP状态码 400
     * 验证通过后,会调用 `run()` 方法, 然后检查 `errors` 属性是否为空,不为空则返回 HTTP状态码400
     * Model类内部可以通过 `Model::addError()` 方法添加错误
     *
     * 当run()方法返回null,作为正确处理
     *
     * @param Model $model
     * @return array
     */
    public function runModel(Model $model)
    {
        if (!$model->validate()) {
            return $this->fail($model->getLastError());
        }
        $result = $model->run();
        if ($model->hasError()) {
            return $this->fail($model->getLastError());
        }

        if ($result === true || is_null($result)) {
            return $this->success('OK');
        } else if ($result === false) {
            return $this->fail('', 'FAIL');
        } else {
            return $this->success($result);
        }
    }

    /**
     * 成功返回的格式
     *
     * @param mixed $result
     * @return array
     */
    public function success($result)
    {
        return array(
            'state' => 1,
            'message' => '',
            'data'  => $result
        );
    }

    /**
     * 错误返回的格式
     *
     * @param string $error     错误信息
     * @param string $data
     * @param bool   $http_code 是否设置状态码
     * @return array
     */
    public function fail($error, $data = '', $http_code = true)
    {
        if ($http_code) {
            Jan::$app->response->setStatusCode(Jan::$app->response->userErrorCode);
        }
        return array(
            'state' => 0,
            'message' => $error,
            'data'  => $data,
        );
    }
}