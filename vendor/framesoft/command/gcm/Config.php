<?php
/**
 * File Name: Config.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/10/7 13:54
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */


namespace jan\command\gcm;


class Config
{
    /**
     * 设置默认值使用 用户id 的字段
     * @return string[]
     */
    public static function defaultUserIdField()
    {
        return [
            'user_id', 'uid', 'from_user', 'create_by', 'create_user', 'update_by', 'update_user'
        ];
    }

    /**
     * 设置默认值使用时间戳的字段
     * @return string[]
     */
    public static function defaultTimestampField()
    {
        return [
            'create_time', 'update_time', 'create_at', 'update_at',
        ];
    }
}
