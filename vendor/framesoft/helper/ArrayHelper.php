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

use jan\basic\InvalidArgumentException;

/**
 * ArrayHelper 数组助手类
 */
class ArrayHelper extends BaseHelper
{
    /**
     * 获取数组中的值
     * 获取不到结果根据第三个参数决定是否抛出异常异常
     *
     * @param array           $array
     * @param string|\Closure $key       数组的键,可以是 aa.bb.cc的形式,将取 $array['aa']['bb']['cc']的值
     * @return mixed
     */
    public static function getValue($array, $key)
    {
        if ($key instanceof \Closure) {
            return $key($array);
        }

        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            return $array[$key];
        }

        $ary_key = explode('.', $key);

        foreach ($ary_key as $k) {
            if (isset($array[$k])) {
                $array = $array[$k];
            } else {
                $array = null;
                break;
            }
        }

        return $array;
    }

    /**
     * 递归合并两个或多个数组
     * 相同的键,如果值都是数组则递归合并,任何一个不是数组,则后者覆盖前者
     * 对于整数键(索引数组),追加到前一个数组中
     * 这个方法不改变原来的数组
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    public static function merge($a, $b)
    {
        $args = func_get_args();
        $res = [];
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if ($v instanceof UnsetArrayValue) {
                    unset($res[$k]);
                } elseif ($v instanceof ReplaceArrayValue) {
                    $res[$k] = $v->value;
                } else if (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } else if (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    /**
     * 判定一个数组是否是关联数组
     * 默认有任何键是字符串则为关联数组
     * 空数组判定为非关联数组
     *
     * @param      $array
     * @param bool $allStrings 规定是否所有键都必须是字符串
     * @return bool
     */
    public static function isAssocArray($array, $allStrings = false)
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }

        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!is_string($key)) {
                    return false;
                }
            }
            return true;
        } else {
            foreach ($array as $key => $value) {
                if (is_string($key)) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * 判定一个数组是否是索引数组
     * 空数组判定为索引数组
     *
     * @param       $array
     * @param false $consecutive 规定是否必须从0连续索引
     * @return bool
     */
    public static function isIndexArray($array, $consecutive = false)
    {
        if (!is_array($array)) {
            return false;
        }

        if (empty($array)) {
            return true;
        }

        if ($consecutive) {
            return array_keys($array) === range(0, count($array) - 1);
        }

        foreach ($array as $key => $value) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }


    /**
     * 返回数组中指定列的值组成的数组。
     * 数组应该是一个多维数组
     *
     * 例子,
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc'],
     *     ['id' => '345', 'data' => 'def'],
     * ];
     * $result = ArrayHelper::getColumn($array, 'id');
     * // the result is: ['123', '345']
     *
     * // using anonymous function
     * $result = ArrayHelper::getColumn($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * @param array               $array
     * @param int|string|\Closure $name
     * @param bool                $keepKeys 是否保持数组的键,如果为false,则结果为索引数组
     * @return array
     */
    public static function getColumn($array, $name, $keepKeys = true)
    {
        $result = [];
        if ($keepKeys) {
            foreach ($array as $k => $element) {
                $result[$k] = static::getValue($element, $name);
            }
        } else {
            foreach ($array as $element) {
                $result[] = static::getValue($element, $name);
            }
        }

        return $result;
    }


    /**
     * 将多维数组进行重新映射
     * `$from` 和 `$to` 指定新数组的键和值
     * 如果使用 `$group` ,将进一步分组(多加一维)
     *
     * 例子
     * ```php
     * $array = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     *
     * $result = ArrayHelper::map($array, 'id', 'name');
     * // the result is:
     * // [
     * //     '123' => 'aaa',
     * //     '124' => 'bbb',
     * //     '345' => 'ccc',
     * // ]
     *
     * $result = ArrayHelper::map($array, 'id', 'name', 'class');
     * // the result is:
     * // [
     * //     'x' => [
     * //         '123' => 'aaa',
     * //         '124' => 'bbb',
     * //     ],
     * //     'y' => [
     * //         '345' => 'ccc',
     * //     ],
     * // ]
     * ```
     *
     * @param array           $array
     * @param string|\Closure $from
     * @param string|\Closure $to
     * @param string|\Closure $group
     * @return array
     */
    public static function map($array, $from, $to, $group = null)
    {
        $result = [];
        foreach ($array as $element) {
            $key = static::getValue($element, $from);
            $value = static::getValue($element, $to);
            if ($group !== null) {
                $result[static::getValue($element, $group)][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }


    /**
     * 检查数组中是否包含元素,该方法和原生 in_array 类似,但是对于实现了 [[\Traversable]] 接口的对象也适用
     *
     * @param mixed              $needle   要查找的值
     * @param array|\Traversable $haystack 要检查的数组
     * @param bool               $strict   是否使用全等比较
     * @return bool
     */
    public static function isIn($needle, $haystack, $strict = false)
    {
        if ($haystack instanceof \Traversable) {
            foreach ($haystack as $value) {
                if ($needle == $value && (!$strict || $needle === $value)) {
                    return true;
                }
            }
        } elseif (is_array($haystack)) {
            return in_array($needle, $haystack, $strict);
        } else {
            throw new InvalidArgumentException('Argument $haystack must be an array or implement Traversable');
        }

        return false;
    }

    /**
     * 将平铺(二维)数组递归格式化成 tree 样式
     * 多用于级联选择
     *
     * @param array  $array         要格式化的数组
     * @param mixed  $first_pid     第一层的pid
     * @param string $pid_name      记录父级id字段
     * @param string $id_name       记录本身id的字段名
     * @param string $children_name 格式化后 children 字段名
     * @return array
     */
    public static function formatTree($array, $first_pid, $pid_name = 'parent_id', $id_name = 'id', $children_name = 'children')
    {
        $tree = array();
        $temp = array();

        foreach ($array as $item) {
            $temp[$item[$id_name]] = $item;
        }

        foreach ($array as $item) {
            if (isset($temp[$item[$pid_name]]) && $item[$pid_name] !== $first_pid) {
                $temp[$item[$pid_name]][$children_name][] = &$temp[$item[$id_name]];
            } else {
                $tree[] = &$temp[$item[$id_name]];
            }
        }
        unset($temp);
        return $tree;
    }
}