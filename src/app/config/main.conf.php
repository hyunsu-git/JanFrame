<?php
/**
 * 通用配置文件
 * 在全部环境下生效，优先级高于默认配置，低于环境单独配置
 * 会被 [dev.conf.php] [prod.conf.php] 覆盖
 */

use jan\helper\ArrayHelper;

$common = require __DIR__ . '/../../../common/config/main.conf.php';

return ArrayHelper::merge($common, array());
