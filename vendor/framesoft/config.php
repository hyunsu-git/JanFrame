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
 * 数据库集群配置示例
 */
$db = array(
    // 是否开启主从模式, 如果不开启都是用则只使用主数据库配置
    // 不论主从是否开启,主库配置多个的时候,都是随机选择
    'dbCluster' => true,
    'masters'    => array(
        'master1' => array(
            'db_type'    => 'mysql',             // 数据库类型
            'db_host'    => 'localhost',       // 服务器地址
            'db_name'    => 'test',              // 数据库名
            'db_user'    => 'root',              // 数据库用户名
            'db_pass'    => '',             // 数据库密码
            'db_port'    => 3306,                // 数据库端口号
            'db_charset' => 'utf8',              // 数据库字符集
        ),
    ),
    'slaves'     => array(
        'slave1' => array(
            'db_type'    => 'mysql',
            'db_host'    => 'localhost',
            'db_name'    => 'test',
            'db_user'    => 'root',
            'db_pass'    => '',
            'db_port'    => 3306,
            'db_charset' => 'utf8',
        ),
    ),
);


/**
 * Redis集群配置示例
 */
$redis = array(
    // 是否开启集群
    // 不开启集群使用 master 的配置信息,直连redis
    // 开启集群使用 sentinels 的配置信息,通过连接哨兵获取redis配置信息
    'redisCluster' => false,
    // 不开启集群必须配置该项
    'master'        => array(
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 0,
    ),
    // 主Redis服务器配置项的名称,该数据需要咨询运维
    'masterName'   => 'mymaster',
    // redis密码
    'auth'          => '',
    // redis哨兵列表
    'sentinels'     => array(
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

return array(
    // 项目名称
    'appName'             => 'Jan Frame',

    // 项目密钥
    'appSecret'           => '',

    // 默认使用的字符集
    'charset'             => 'UTF-8',

    // 根命名空间
    'rootNamespace'       => 'app',

    // 多语言配置,语言配置不区分大小写,都会转换成小写字符.
    // zh_CN和zh_cn一样
    'language'            => 'en_us',

    // 多语言默认加载配置
    'defaultLoadLanguage' => array(
        'en_us' => array(),
        'zh_cn' => array(
            '@jan/languages/zh_cn',
        ),
        'zh_hk' => array(
            '@jan/languages/zh_hk'
        )
    ),

    // 命令
    'command'             => array(
        'help'    => '\jan\command\HelpCmd',
        'version' => '\jan\command\VersionCmd',
    ),

    'components' => array(
        'router'   => array(
            'class' => 'jan\components\router\Router',
        ),
        'request'  => array(
            'class'   => '\jan\components\request\Request',
//            'parsers' => array(
//                // 解析请求的方式，配置格式为：[请求头部=>解析器]
//                'application/json' => '\jan\components\request\JsonParser',
//                'text/json'        => '\jan\components\request\JsonParser',
//            ),
        ),
        'response' => array(
            'class'      => '\jan\components\response\Response',
//            'formatters' => array(
//                // 可以配置自定义的格式化方式 eg:
//                'json' => '\jan\components\response\JsonResponseFormatter'
//            ),
//            'format'     => \jan\components\response\Response::FORMAT_JSON, // 设置要使用的格式化方法
        ),
        'log'      => array(
            'class' => '\jan\components\log\LogPrintScreen',
        ),
//        'db'       => array(
//            'class'   => '\jan\components\db\Connection',
//            'configs' => $db,
//        ),
//        'redis'    => array(
//            'class' => '\jan\components\redis\Redis',
//        ),
//        'snowflake' => array(
//            'class' => '\jan\components\Snowflake',
//        ),
//        'cache'=>array(
//            'class'=>'\jan\components\cache\FileCache',
//            'cachePath'=>'@runtime/cache',
//        )
//        'session'=>array(
//            'class'=>'\jan\components\session\Session',
//        )
    ),
);
