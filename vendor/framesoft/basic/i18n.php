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

use jan\helper\StringHelper;

/**
 * Class i18n
 * 国际化相关类
 * 框架国际化不限制语种，框架本身使用了英文作为原始语句，提供了简体中文和繁体中文的翻译
 * 常用语言简写：
 * en_US    英文
 * zh_CN    中文
 * zh_HK    繁体中文（香港）
 * ja_JP    日语
 * fr_FR    法语
 * 对于语言的简写框架不做限制，但是会使用[[StringHelper::caseSnake()]]转成下划线小写的形式，需要提供的国际化文件名称保持一致
 *
 * 语种的初始化可以使用全局初始化，也可以使用动态初始化
 * 全局初始化
 *      在配置文件中加入一下配置，可以使用具体文件名称或者目录
 *      如果使用目录，则会遍历目录下所有文件，进行合并
 *      文件应该返回一个键值对数组
 *
 *          'defaultLoadLanguage'=>array(
 *                   'en_us'=>array(
 *                       // 文件路径
 *                       // 文件夹
 *                   ),
 *                   'zh_cn'=>array(
 *                       '@jan/languages/zh_cn.php',
 *                   )
 *               ),
 * 动态初始化
 *      可以在任意地方调用以下3个方法进行加载
 *      i18n::loadLang()
 *      i18n::loadLangFile()
 *      i18n::loadLangDir()
 *      对于组件的动态初始化，建议在组件的 `init()` 方法中加载语言
 *
 */
class i18n extends Component
{
    /**
     * 当前使用的语种，可以在运行中动态改变
     * 如果是字符串，则表示单一语种
     * 如果是数组，表示语种组合，下标越小，优先级越高
     *      翻译的时候，如果高优先级的不存在指定语句的翻译，会依次尝试低优先级的语种
     *
     * 为了方便，这里和请求模块做了联动，请求模块会自动设置为相对应的语种
     *
     * @see Request::$linkI18n
     *
     * @var string|array
     */
    protected static $lang = 'en_us';

    /**
     * @var array 保存所有的翻译源
     */
    protected static $source = [];

    /**
     * @var array 保存已经加载过的文件，防止重复加载
     */
    protected static $loaded_files = [];

    /**
     * 设置使用的语种
     *
     * @param string|array $lang
     */
    public static function setLang($lang)
    {
        if (is_array($lang)) {
            $ary = [];
            foreach ($lang as $la) {
                $ary[] = StringHelper::caseSnake($la);
            }
            self::$lang = $ary;
        } else {
            self::$lang = StringHelper::caseSnake($lang);
        }
    }

    /**
     * 获取当前的语种
     *
     * @return string
     */
    public static function getLang()
    {
        return self::$lang;
    }

    /**
     * 获取某种翻译源
     *
     * @param string $lang 语种，不设置则返回所有翻译源
     * @return array|mixed
     */
    public static function getSource($lang = null)
    {
        if (empty($lang)) {
            return self::$source;
        } else {
            $lang = strtolower($lang);
            return isset(self::$source[$lang]) ? self::$source[$lang] : self::$source;
        }
    }

    /**
     * 对语句进行国际化翻译，如果对应语言的翻译不存在，将返回原语句
     *
     * @param string       $msg   要翻译的语句
     * @param array        $props 要替换的变量，使用键值对
     *                            语句中可以使用大括号包裹一个变量进行替换，
     *                            例如：
     *                            $msg = '{field} cannot be empty';
     *                            $msg = i18n::t($msg,['field'=>'name']);
     *                            结果为： 'name cannot be empty'
     * @param string|array $lang  要翻译的语种，默认使用全局语种
     * @return string
     */
    public static function t($msg, $props = [], $lang = null)
    {
        $lang = static::formatLang($lang);

        $msg = static::transform($msg, $lang);
        if (is_array($props)) {
            foreach ($props as $attr => $value) {
                $search = '{' . $attr . '}';
                $msg = str_replace($search, $value, $msg);
            }
        }
        return $msg;
    }

    /**
     * 设置翻译源
     * 使用的是追加而不是替换
     *
     * @param array  $data 键值对数组
     * @param string $lang 语种，默认使用全局语种
     */
    public static function loadLang(array $data, $lang = null)
    {
        $lang = static::formatLang($lang, true);

        if (isset(self::$source[$lang])) {
            self::$source[$lang] = array_merge(self::$source[$lang], $data);
        } else {
            self::$source[$lang] = $data;
        }
    }

    /**
     * 加载翻译源文件
     *
     * @param string $file 文件名
     * @param string $lang 语种，默认使用全局语种
     */
    public static function loadLangFile($file, $lang = null)
    {
        if (strncmp($file, "@", 1) === 0) {
            $file = Loader::getAlias($file);
        }
        if (!isset(self::$loaded_files[$file])) {
            if (is_file($file)) {
                static::loadLang(require $file, $lang);
                self::$loaded_files[] = $file;
            }
        }
    }

    /**
     * 加载目录下所有文件所有翻译源
     * 支持递归目录，不验证目录的名称
     *
     * @param string $dir  路径
     * @param string $lang 语种，默认使用全局语种
     */
    public static function loadLangDir($dir, $lang = null)
    {
        $files = static::scanDirectory($dir);
        foreach ($files as $file) {
            static::loadLangFile($file, $lang);
        }
    }

    /**
     * 自动区分文件或者目录进行加载
     *
     * @param string $path 路径
     * @param string $lang 语种，默认使用全局语种
     * @see loadLangDir()
     * @see loadLangFile()
     */
    public static function loadAuto($path, $lang = null)
    {
        if (strncmp($path, "@", 1) === 0) {
            $path = Loader::getAlias($path);
        }
        if (is_dir($path)) {
            static::loadLangDir($path, $lang);
        } else {
            static::loadLangFile($path, $lang);
        }
    }


    /**
     * 遍历目录，获取所有文件
     *
     * @param $dir
     * @return array
     */
    public static function scanDirectory($dir)
    {
        if (strncmp($dir, "@", 1) === 0) {
            $dir = Loader::getAlias($dir);
        }
        if (!is_dir($dir)) return [];
        $files = scandir($dir);
        $resp = [];
        foreach ($files as $file) {
            // 当前目录，上级目录，隐藏文件全都跳过
            if ($file === '.' || $file === '..' || strncmp($file, ".", 1) === 0) {
                continue;
            }
            $file = $dir . DS . $file;
            if (is_file($file)) {
                $resp[] = $file;
            } else {
                $resp = array_merge($resp, static::scanDirectory($file));
            }
        }
        return $resp;
    }

    /**
     * 格式化语种
     *
     * @param string|array $lang
     * @param bool         $one 如果语种是数组，是否只返回第一个
     * @return array|string
     */
    protected static function formatLang($lang, $one = false)
    {
        if (empty($lang)) {
            if ($one && is_array(self::$lang)) {
                return current(self::$lang);
            } else {
                return self::$lang;
            }
        } else {
            if (is_array($lang)) {
                $ary = [];
                foreach ($lang as $item) {
                    $ary[] = strtolower($item);
                }
                return $ary;
            } else {
                return strtolower($lang);
            }
        }
    }

    /**
     * 将字符串转换对应语言
     *
     * @param string|array $msg  要转换的字符串
     * @param string       $lang 要转换的语言
     * @return mixed
     */
    protected static function transform($msg, $lang)
    {
        if (is_array($lang)) {
            foreach ($lang as $item) {
                if (isset(self::$source[$item][$msg])) {
                    return self::$source[$item][$msg];
                }
            }
            return $msg;
        } else {
            return isset(self::$source[$lang][$msg]) ? self::$source[$lang][$msg] : $msg;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run() {}
}