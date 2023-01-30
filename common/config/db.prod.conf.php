<?php
/**
 * 数据库配置文件
 * 仅在生产环境下生效
 */

return array(
    // 是否开启主从模式, 如果不开启都是用则只使用主数据库配置
    // 不论主从是否开启,主库配置多个的时候,都是随机选择
    'dbCluster' => false,
    'masters' => array(
        'master1' => array(
            'db_type' => 'mysql',             // 数据库类型
            'db_host' => '127.0.0.1',       // 服务器地址
            'db_name' => '',              // 数据库名
            'db_user' => 'root',              // 数据库用户名
            'db_pass' => '',             // 数据库密码
            'db_port' => 3306,                // 数据库端口号
            'db_charset' => 'utf8',              // 数据库字符集
            'table_prefix' => 'tbl_',         // 数据表前缀,只在第一个主数据库配置上的有效
        ),
    ),
);
