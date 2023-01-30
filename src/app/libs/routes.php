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
 */

/**
 * 配置路由规则
 *
 * 关于路由优先级：
 * 1. 层级越深的规则优先级越高；
 * 2. 精准匹配优先级高于模糊匹配；
 * 3. 层级相同的路由，按照ASCII倒序排列
 *
 * 注意：
 * 路由组件不会进行以下操作
 * 1.不会检测路由是否合法
 * 2.不会检测路由是否重复，比如 `/test/:id` 和 `/test/:name` 这两个路由是可以同时存在的，只会匹配到优先级更高的路由
 * 3.如果指定处理程序是一个类，在没有实际执行该路由前，不会检查类和方法是否存在
 *
 */

return array(
//    // 路由规则可以是确定的字符串
//    '/test'                 => function () {},
//    // 可以是动态路由，字段名必须以冒号开头，动态路由的字段值可以从 request 组件的 params 属性获取
//    '/test/:id'             => function () {},
//    // 可以自定义正则表达式，下面表示 :user 必须是数字
//    '/test/user/:user<\d+>' => function () {},
//    // 可以指定请求方式
//    'Get:/test/t1/:id'      => [\app\controllers\Test::class, "t1"],
);