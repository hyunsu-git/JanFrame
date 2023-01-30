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

use Jan;

/**
 * Class UserException
 * The user throws the exception manually
 * The exception will not be recorded, but the error information will be sent to the client as a return value
 */
class UserException extends HttpException
{
    public function getName()
    {
        return 'User Exception';
    }

    public function __construct($message, $status = null)
    {
        if ($status === null) {
            $status = Jan::$app->response->userErrorCode;
        }
        parent::__construct($status, $message);
    }
}