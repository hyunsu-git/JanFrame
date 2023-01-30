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

namespace jan\helper;

/**
 * ReplaceArrayValue 执行[[ArrayHelper::merge()]]的时候替换数组的值
 * 例如:
 *
 * ```php
 * $array1 = [
 *     'ids' => [
 *         1,
 *     ],
 *     'validDomains' => [
 *         'example.com',
 *         'www.example.com',
 *     ],
 * ];
 *
 * $array2 = [
 *     'ids' => [
 *         2,
 *     ],
 *     'validDomains' => new \jan\helpers\ReplaceArrayValue([
 *         'baidu.com',
 *         'www.baidu.com',
 *     ]),
 * ];
 *
 * $result = \jan\helpers\ArrayHelper::merge($array1, $array2);
 * ```
 *
 * The result will be
 *
 * ```php
 * [
 *     'ids' => [
 *         1,
 *         2,
 *     ],
 *     'validDomains' => [
 *         'baidu.com',
 *         'www.baidu.com',
 *     ],
 * ]
 * ```
 */
class ReplaceArrayValue
{
    /**
     * @var mixed value used as replacement.
     */
    public $value;


    /**
     * Constructor.
     *
     * @param mixed $value value used as replacement.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}