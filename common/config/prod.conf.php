<?php
/**
 * 仅在生产环境生效的配置
 * 会被项目单独配置的 [prod.conf.php] 文件覆盖
 */

use jan\helper\ReplaceArrayValue;

return array(

    'components' => array(
        // 将日志记录到文件
        'log' => new ReplaceArrayValue([
            'class' => '\jan\components\log\LogRecordFile',
            // debug追溯层数
            'debugStackLimit' => 15,
            // 日志文件大小,文件型日志独有参数,日志超过设定大小会将旧文件重命名,重新开启一个日志文件
            'maxSize' => '1M',
            // 日志存储目录
            'path' => APP_PATH . DS . 'runtime',
        ]),
        'db' => new ReplaceArrayValue([
            'class' => '\jan\components\db\Connection',
            'configs' => require __DIR__ . '/db.prod.conf.php',
        ]),
        'redis' => new ReplaceArrayValue([
            'class' => '\jan\components\redis\Redis',
            'config' => require __DIR__ . '/redis.prod.conf.php',
        ])
    ),
);

