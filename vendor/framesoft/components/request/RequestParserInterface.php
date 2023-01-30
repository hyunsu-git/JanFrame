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

namespace jan\components\request;

/**
 * 用户解析请求数据的组件需要实现的接口
 */
interface RequestParserInterface
{
    /**
     * 解析请求body中的数据
     *
     * @param string $rawBody 请求的原始数据
     * @return array 解析后的数据
     */
    public function parse($rawBody);
}