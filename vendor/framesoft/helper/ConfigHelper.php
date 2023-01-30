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
 * ============================= 重大版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace jan\helper;

use Jan;
use jan\basic\i18n;
use jan\basic\InvalidConfigException;

/**
 * ConfigHelper 配置助手类
 */
class ConfigHelper extends BaseHelper
{
    /**
     * 从配置项中获取类名
     * @param $conf
     * @return mixed|string
     */
    public static function getClassNameFromConfigItem($conf)
    {
        if (is_string($conf)) {
            return $conf;
        } else if (is_array($conf)) {
            if (isset($conf['class'])) {
                return $conf['class'];
            } else {
                throw new InvalidConfigException('Missing class parameter in configuration!');
            }
        } else {
            throw new InvalidConfigException('The configuration is invalid,should be a class name or an array with class attributes!');
        }
    }

    /**
     * 判断设置项是否是有效的对象设置
     * @param $conf
     * @return bool
     */
    public static function isObjectConfigItem($conf)
    {
        if (is_string($conf)) {
            return class_exists($conf);
        } else if (is_array($conf)) {
            if (!isset($conf['class'])) {
                return false;
            }
            return class_exists($conf['class']);
        } else {
            return false;
        }
    }

    /**
     * 合并加载配置文件
     * @return array|mixed
     */
    public static function loadConfigFile()
    {
        // 合并配置
        $frm_config = StringHelper::combPath(ENGINE_PATH, 'config.php');
        $main_config = StringHelper::combPath(APP_CONF_PATH, 'main.conf.php');
        $env_config = StringHelper::combPath(APP_CONF_PATH, ENV . '.conf.php');
        $config = require $frm_config;
        if (is_file($main_config)) {
            $config = ArrayHelper::merge($config, require $main_config);
        }
        if (is_file($env_config)) {
            $config = ArrayHelper::merge($config, require $env_config);
        }

        return $config;
    }

    /**
     * 自动递归加载目录下的国际化文件
     * 国际化文件需要存放在 `languages` 目录中
     * @param string $path 要开始搜索的目录
     */
    public static function autoLoadLangFile($path)
    {
        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (!is_dir(StringHelper::combPath($path,$file))) {
                continue;
            }
            if ($file == 'languages') {
                $lang_dir = StringHelper::combPath($path, 'languages');
                $lang_files = scandir($lang_dir);
                foreach ($lang_files as $lang_file) {
                    if ($lang_file === '.' || $lang_file === '..') {
                        continue;
                    }
                    // 删除后缀作为语种
                    $lang = str_replace(strrchr($lang_file, "."), "", $lang_file);
                    $file_path = StringHelper::combPath($lang_dir, $lang_file);
                    i18n::loadAuto($file_path, $lang);
                }
            } else {
                self::autoLoadLangFile(StringHelper::combPath($path, $file));
            }
        }
    }

    /**
     * 获取数据库配置中第一个主库的配置
     *
     */
    public static function getDbMasterConfig()
    {
        $config = Jan::getConfig('components.db.configs.masters', null);
        if ($config && is_array($config)) {
            return current($config);
        } else {
            $config = Jan::getConfig('db.masters');
            if ($config && is_array($config)) {
                return current($config);
            }
        }
        return null;
    }

    /**
     * 获取数据库名称
     * @return array|string|null
     */
    public static function getDbName()
    {
        $master = static::getDbMasterConfig();
        if (is_array($master) && isset($master['db_name'])) {
            return $master['db_name'];
        } else {
            return '';
        }
    }

    /**
     * 获取数据表前缀
     * @return mixed|string
     */
    public static function getTablePrefix()
    {
        $master = static::getDbMasterConfig();
        if (is_array($master) && isset($master['table_prefix'])) {
            return $master['table_prefix'];
        } else {
            return '';
        }
    }
}