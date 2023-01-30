<?php
/**
 * 用于设置类名对应的文件,这里的设置优先级高于框架的自动加载,低于composer的自动加载
 * 格式为: 完整类名=>文件路径 ,路径可以使用别名和常量
 * 例如 :
 * [
 *     test\map\Ac=> APP_PATH . '/app/test/map/Ac.php',
 *     test\map\Ac2=>'@app_root/app/test/map/Ac2.php',
 * ]
 */
return array(
    // e.g.
    // 'test\map\Ac'=> APP_PATH . '/app/test/map/Ac.php',
);
