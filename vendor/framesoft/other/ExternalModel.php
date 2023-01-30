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

namespace jan\other;

use jan\web\Model;

/**
 * Class ExternalModel
 *
 * 服务层扩展模块的 Model类 如果需要使用外部配置,应该继承该类
 * 继承后,可以让 `$external` 属性不适用于自动赋值,并且外部可以设置
 * 该类是对编辑器友好的
 *
 * @property External $external
 */
class ExternalModel extends Model
{
    /**
     * @var External
     */
    protected $external;

    /**
     * @return External
     */
    public function getExternal()
    {
        return $this->external;
    }

    /**
     * @param External $external
     */
    public function setExternal($external)
    {
        $this->external = $external;
    }
}