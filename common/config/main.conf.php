<?php
/**
 * 通用配置文件
 * 在全部环境下生效，优先级高于默认配置，低于环境单独配置，低于项目单独配置
 */

return array(
    // 项目名称
    'appName'  => 'JanFrame',

    // 多语言配置,语言配置不区分大小写,都会转换成小写字符.
    // zh_CN和zh_cn一样
    'language' => 'en-us',

    'components' => array(
        'request'   => array(
            'class'   => '\jan\components\request\Request',
            'parsers' => array(
                // 解析请求的方式，配置格式为：[请求头部=>解析器]
                'application/json' => '\jan\components\request\JsonParser',
                'text/json'        => '\jan\components\request\JsonParser',
            ),
        ),
        'response'  => array(
            'class'      => '\jan\components\response\Response',
            'formatters' => array(
                // 可以配置自定义的格式化方式 eg:
                'json' => '\jan\components\response\JsonResponseFormatter'
            ),
            'format'     => \jan\components\response\Response::FORMAT_JSON, // 设置要使用的格式化方法
        ),
        'snowflake' => array(
            'class'        => '\jan\components\Snowflake',
            'synchronized' => true,
        ),
        'cache'     => array(
            'class'     => '\jan\components\cache\FileCache',
            'cachePath' => '@runtime/cache',
        ),
        'session'   => array(
            'class' => '\jan\components\session\Session',
        )
    ),
);
