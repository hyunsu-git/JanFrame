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

namespace jan\components\router;

use Jan;
use jan\basic\Exception;

/**
 * NotFoundRouteException represents an error when a route is not found.
 */
class NotFoundRouteException extends Exception
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Not Found Route';
    }

    public function __construct($route, $message = null, $code = 0, $previous = null)
    {
        if ($message === null) {
            $message = "Not find route: $route.";
        }
        Jan::$app->response->setStatusCode(404);
        parent::__construct($message, $code, $previous);
    }
}