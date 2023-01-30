<?php
/**
 * 仅在线上环境生效的配置
 * 优先级最高，会覆盖默认配置和 [main.conf.php] 文件中的配置
 */

use jan\helper\ArrayHelper;

$common = require __DIR__ . '/../../../common/config/prod.conf.php';

return ArrayHelper::merge($common, array());
