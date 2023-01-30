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

namespace jan\basic;

/**
 * 加载器，用于实现框架的自动加载
 */
class Loader
{
    /**
     * @var array 保存所有别名映射关系
     */
    public static $aliases = [];

    /**
     * @var array 保存手动设置的类加载关系
     */
    protected static $class_map = [];


    public static function getClassMap()
    {
        return self::$class_map;
    }

    /**
     * 注册自动加载
     *
     * @param $class_map array 手动设置的类加载关系
     * @return void
     */
    public static function register(array $class_map)
    {
        foreach ($class_map as $cls => $file) {
            $cls = str_replace("\\", "/", $cls);
            self::$class_map[$cls] = $file;
        }
        $composer_autoload = VENDOR_PATH . DS . "autoload.php";
        if (is_file($composer_autoload)) {
            require $composer_autoload;
        }
        spl_autoload_register('\jan\basic\Loader::autoload', true, true);
    }

    /**
     * 框架自动加载的主要处理函数
     *
     * @param string $class
     */
    public static function autoload($class)
    {
        $file = self::_autoload($class);
        if ($file) {
            require_once $file;
        }
    }

    /**
     * 将需要自动加载的类尝试转成文件,转换失败返回false
     *
     * @param string $cls
     * @return bool|string
     */
    protected static function _autoload($cls)
    {
        if (isset(self::$class_map[$cls])) {
            $class_file = self::$class_map[$cls];
            if (strncmp($class_file, "@", 1) === 0) {
                $class_file = self::getAlias($class_file);
            }
            return $class_file;
        }

        $cls = str_replace("\\", "/", $cls);

        //转换成别名查找
        $alias = "@" . $cls . ".php";
        $class_file = self::getAlias($alias);
        if ($class_file && is_file($class_file)) {
            return $class_file;
        }

        // 认为是 app 目录下的文件
        $class_file = APP_PATH . DS . $cls . ".php";
        if (is_file($class_file)) {
            return $class_file;
        }

        return false;
    }

    /**
     * 注册路径别名
     * 路径别名是表示长路径（文件路径、URL等）的短名称
     * 路径别名必须以字符“@”开头，以便易于区分
     * 请注意，此方法不会检查给定路径是否存在。它所做的就是将别名与路径相关联。
     * 将去除给定路径中任何尾随的“/”和“\”字符。
     *
     * @param string $alias 别名
     * @param string $path  别名对应的路径,如果为null，则别名将被移除
     *                      可以是一个目录或者文件路径
     *                      可以是一个URL,例如'http://www.baidu.com'
     *                      可以是另一个别名,这样会先调用 getAlias 转换成实际地址
     * @see getAlias()
     */
    public static function setAlias($alias, $path)
    {
        // 如果不是@开头,则追加
        if (strncmp($alias, "@", 1)) {
            $alias = "@" . $alias;
        }
        // 查找第一个 / 前的部分
        $pos = strpos($alias, "/");
        if ($pos === false) {
            $root = $alias;
        } else {
            $root = substr($alias, 0, $pos);
        }

        if ($path !== null) {
            if (strncmp($path, "@", 1)) {
                $path = rtrim($path, '/');
            } else {
                $path = self::getAlias($path);
            }
            if (!isset(self::$aliases[$root])) {
                if ($pos === false) {
                    self::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [$alias => $path];
                }
            } elseif (isset(self::$aliases[$root]) && is_string(self::$aliases[$root])) {
                if ($pos === false) {
                    self::$aliases[$root] = $path;
                } else {
                    self::$aliases[$root] = [
                        $alias => $path,
                        $root  => self::$aliases[$root],
                    ];
                }
            } else {
                self::$aliases[$root][$alias] = $path;
                krsort(self::$aliases[$root]);
            }
        } elseif (isset(self::$aliases[$root])) {
            if (is_array(self::$aliases[$root])) {
                unset(self::$aliases[$root][$alias]);
            } elseif ($pos === false) {
                unset(self::$aliases[$root]);
            }
        }
    }


    /**
     * 将路径别名转换为实际路径。
     * 转换过程如下:
     * 如果给定的别名不是以'@'开头，直接返回；
     * 否则，查找与开始部分匹配的最长注册别名,如果存在，请将给定别名的匹配部分替换为对应的注册路径;
     * 抛出异常或返回false，具体取决于 $throweexception 参数。
     *
     * 例如:
     * 如果您注册了两个别名'@frame'='dir1'和'@frame/lib'='dir2';
     * '@frame/lib/index' 会转换成 dir/index
     * 这是因为最长的别名优先。
     *
     * @param string $alias          要转换的别名
     * @param bool   $throwException 如果给定的别名不存在是否抛出异常
     * @return bool|string
     * @see setAlias()
     */
    public static function getAlias($alias, $throwException = true)
    {
        if (strncmp($alias, "@", 1)) {
            // not an $alias
            if ($throwException) {
                throw new InvalidArgumentException("Get invalid alias: $alias");
            } else {
                return $alias;
            }
        }

        $pos = strpos($alias, '/');
        if ($pos === false) {
            $root = $alias;
        } else {
            $root = substr($alias, 0, $pos);
        }

        if (isset(self::$aliases[$root])) {
            if (is_string(self::$aliases[$root])) {
                if ($pos === false) {
                    return self::$aliases[$root];
                } else {
                    return self::$aliases[$root] . substr($alias, $pos);
                }
            }
            foreach (self::$aliases[$root] as $name => $path) {
                if (strpos($alias . '/', $name . '/') === 0) {
                    return $path . substr($alias, strlen($name));
                }
            }

            if ($throwException) {
                throw new InvalidArgumentException("Get unknown alias：$alias");
            }
        }

        return false;
    }
}