<?php
/**
 * redis配置文件
 * 仅在开发环境下生效
 */
return array(
    // 是否开启集群
    // 不开启集群使用 master 的配置信息,直连redis
    // 开启集群使用 sentinels 的配置信息,通过连接哨兵获取redis配置信息
    'redisCluster' => false,
    // 不开启集群必须配置该项
    'master' => array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 0,
    ),
    // 主Redis服务器配置项的名称,该数据需要咨询运维
    'masterName' => 'mymaster',
    // redis密码
    'auth' => '123456',
    // redis哨兵列表
    'sentinels' => array(
        'sentinel1' => array(
            'host' => '192.168.0.1',
            'port' => 26379
        ),
        'sentinel2' => array(
            'host' => '192.168.0.2',
            'port' => 26379
        ),
        'sentinel3' => array(
            'host' => '192.168.0.3',
            'port' => 26379
        ),
    )
);
