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

namespace jan\components\cache;

/**
 * 缓存组件需要实现该接口
 */
interface ICache extends \ArrayAccess
{
    /**
     * 判断是否允许缓存
     */
    public function enableCache();

    /**
     * 将给定的key转成标准的key
     * 如果给定不含特殊字符的字符串,加上 [[keyPrefix]] 前缀,直接返回
     * 如果给定key过长,或者包含特殊字符,或者不是字符串,则进行序列化和md5摘要,然后加上前缀返回
     * @param $key
     * @return string
     */
    public function buildKey($key);

    /**
     * 从缓存获取一条记录
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * 设置一条缓存记录
     * 如果key不存在将加入
     * 如果key存在,值将被修改
     * @param string $key
     * @param $value
     * @param int $duration 缓存有效时间,单位秒,0表示一直有效
     */
    public function set($key, $value, $duration = 0);

    /**
     * 检查一个 key 是否存在
     * 该方法保证效率不会低于 [[get()]] 方法,大多数情况会比 [[get()]] 方法高效
     * @param $key
     * @return bool
     */
    public function exists($key);

    /**
     * 增加一条缓存记录
     * 如果存在相同的 key, 则忽略该操作
     * 不过不存在,则添加
     * @param string $key
     * @param mixed $value
     * @param int $duration 缓存有效时间,单位秒,0表示一直有效
     * @return bool 添加成功返回true,否则返回false
     */
    public function add($key, $value, $duration = 0);

    /**
     * 删除一条缓存记录
     * @param $key
     */
    public function delete($key);

    /**
     * 清空缓存,请谨慎使用该操作
     */
    public function clear();
}