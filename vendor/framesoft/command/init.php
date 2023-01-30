<?php
Jan::$config = \jan\helper\ConfigHelper::loadConfigFile();
Jan::$app = Jan::createObject('\jan\basic\Application');
$components = Jan::getConfig('components', null);
if (is_array($components)) {
    foreach ($components as $name => $conf) {
        $component = Jan::createObject($conf, [], is_string($name) ? $name : null);
        Jan::$app->$name = $component;
        $component->run();
    }
}

$config = Jan::getConfig('command');
$cmd_list = [];
foreach ($config as $key => $val) {
    $cmd_list[$key] = $val;
    $short = $val::$shortName;
    if (!empty($short)) {
        $cmd_list[$short] = $val;
    }
}
$command = isset($argv[1]) ? $argv[1] : '';
if (empty($command) || !isset($cmd_list[$command])) {
    echo \jan\helper\StringHelper::commandColor("Unrecognized option: {$command}",COMMAND_COLOR_RED) . PHP_EOL;
    echo "You can use <help> to view all available options and commands." . PHP_EOL;
    exit(1);
}
// 解析参数
$args = [];
if (count($argv) >= 3) {
    for ($i = 2; $i < count($argv); $i++) {
        if (strncmp($argv[$i], '-', 1) === 0) {
            $args[$argv[$i]] = isset($argv[$i + 1]) ? $argv[$i + 1] : '';
            $i++;
        }else{
            $ary = explode('=',$argv[$i]);
            $args[$ary[0]] = isset($ary[1]) ? $ary[1] : '';
        }
    }
}

/**
 * @var $obj \jan\command\Command
 */
$obj = Jan::createObject($cmd_list[$argv[1]]);
$obj->exec($args);

