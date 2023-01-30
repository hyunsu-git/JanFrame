<?php
/**
 * 仅在开发环境生效的配置
 * 会被项目单独配置的 [dev.conf.php] 文件覆盖
 */

use jan\helper\ReplaceArrayValue;

return array(
    'components' => array(
        // 将日志直接输出
        'log' => new ReplaceArrayValue([
            'class' => '\jan\components\log\LogPrintScreen',
            // debug追溯层数
            'debugStackLimit' => 15,
            // 要记录的日志级别
            'levels' => [1, 2, 3, 4, 5],
        ]),
        'db' => new ReplaceArrayValue([
            'class' => '\jan\components\db\Connection',
            'configs' => require __DIR__ . '/db.dev.conf.php',
        ]),
        'redis' => new ReplaceArrayValue([
            'class' => '\jan\components\redis\Redis',
            'config' => require __DIR__ . '/redis.dev.conf.php',
        ])
    ),
);
