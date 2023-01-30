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

namespace jan\di;

/**
 * 依赖注入容器中作为命名对象的引用
 * 解析类的参数时，命名对象会暂时被实例化为该类暂存，方便后续实例化参数
 */
class Instance
{
    /**
     * @var string 原始类名
     */
    public $name;

    protected function __construct($class)
    {
        $this->name = $class;
    }

    /**
     * 创建一个对象实例
     * @param string $class
     * @return static
     */
    public static function of($class)
    {
        return new static($class);
    }
}