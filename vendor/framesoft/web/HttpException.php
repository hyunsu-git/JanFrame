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

namespace jan\web;

use jan\basic\Exception;
use jan\components\response\Response;

/**
 * HTTP请求错误导致的异常
 */
class HttpException extends Exception
{
    /**
     * @var int 希望返回的HTTP状态码
     */
    public $statusCode;

    /**
     * Constructor.
     * @param int $status HTTP状态码
     * @param string $message 发送到客户端的错误信息
     * @param int $code 错误代码
     * @param \Exception $previous
     */
    public function __construct($status, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->statusCode = $status;
        parent::__construct($message, $code, $previous);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        if (isset(Response::$httpStatuses[$this->statusCode])) {
            return Response::$httpStatuses[$this->statusCode];
        }

        return 'Http Exception';
    }
}