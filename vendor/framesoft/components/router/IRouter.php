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

/**
 * Interface IRouter
 * The user can customize the route component and only need to implement the interface
 */
interface IRouter
{
    /**
     * Parse the request url and set the relevant properties of the request component
     * engine will call this method on `APP_STATE_INIT_REQUEST` event
     * must set the `matchRule`,`handle`,`module`,`controller`,`action` property of the request component
     */
    public function parseUrl();

    /**
     * Set routing rules
     * engine will call this method on `APP_STATE_BEFORE_REQUEST` event, and read the routing rules from the configuration file
     * @param array $routes
     */
    public function setRoutes($routes);
}