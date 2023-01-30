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

defined('COMMAND_COLOR_BLACK') or define('COMMAND_COLOR_BLACK', 0);
defined('COMMAND_COLOR_RED') or define('COMMAND_COLOR_RED', 1);
defined('COMMAND_COLOR_GREEN') or define('COMMAND_COLOR_GREEN', 2);
defined('COMMAND_COLOR_YELLOW') or define('COMMAND_COLOR_YELLOW', 3);
defined('COMMAND_COLOR_BLUE') or define('COMMAND_COLOR_BLUE', 4);
defined('COMMAND_COLOR_VIOLET') or define('COMMAND_COLOR_VIOLET', 5);
defined('COMMAND_COLOR_CYAN') or define('COMMAND_COLOR_CYAN', 6);
defined('COMMAND_COLOR_WHITE') or define('COMMAND_COLOR_WHITE', 7);

/**
 * StringHelper 字符串助手类
 */
class StringHelper extends BaseHelper
{
    /**
     * 将所有的参数,以系统目录分隔符拼接成完整路径
     * 该方法会将所有参数中的 '\\' 字符转成目录分隔符
     *
     * @param string $a
     * @param string $b
     * @return string
     */
    public static function combPath($a, $b)
    {
        $ds = DIRECTORY_SEPARATOR;
        $args = func_get_args();
        $path = rtrim(str_replace('\\', $ds, array_shift($args)), $ds);
        foreach ($args as $item) {
            if (empty($item)) continue;
            $path .= $ds . trim(str_replace('\\', $ds, $item), $ds);
        }
        return $path;
    }

    /**
     * 该方法和 自带的 basename() 作用基本一致
     * 只是可以处理 / 和 \ 分隔符,以便处理命名空间
     *
     * @param string $path
     * @param string $suffix 后缀,如果包含这个后缀会被切掉
     * @return string
     */
    public static function basename($path, $suffix = '')
    {
        if (($len = mb_strlen($suffix)) > 0 && mb_substr($path, -$len) === $suffix) {
            $path = mb_substr($path, 0, -$len);
        }
        $path = rtrim(str_replace('\\', '/', $path), '/\\');
        if (($pos = mb_strrpos($path, '/')) !== false) {
            return mb_substr($path, $pos + 1);
        }
        return $path;
    }

    /**
     * 生成一串随机字符串
     *
     * @param $_len integer 字符串长度
     * @return bool|string
     */
    public static function random($_len)
    {
        $len = intval($_len) <= 0 ? 32 : intval($_len);
        //mcrypt_create_iv 函数在7.1.0 版本中已经被废弃
        if (version_compare(PHP_VERSION, "7.1.0", ">=")) {
            $iv = random_bytes($len);
            $r = bin2hex($iv);
        } else {
            $r = '';
            $max = ceil($len / 32);
            for ($i = 0; $i < $max; $i++) {
                $size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
                $iv = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
                $r .= strval(bin2hex($iv));
            }
        }
        return substr($r, 0, $len);
    }

    /**
     * 在命令行中输入带有颜色的文字
     * 颜色定义为 COMMAND_COLOR_* 共8中颜色
     * @param $str string 要输出的文字
     * @param string|integer $front_color 文字颜色
     * @param string|integer $back_color 背景颜色
     * @param bool $bold 字体加粗
     * @param bool $half_light 亮度减半
     * @param bool $underline 带有下划线
     * @param bool $twinkle 字体闪烁
     * @return string
     * @author hyunsu
     * @time 2019-06-20 16:56
     */
    public static function commandColor($str, $front_color = '', $back_color = '', $bold = false, $half_light = false, $underline = false, $twinkle = false)
    {
        $command = "\033[3{$front_color};4{$back_color}";
        $command = "\033[";
        if (!empty($front_color)) {
            $command .= "3{$front_color};";
        }
        if (!empty($front_color)) {
            $command .= "4{$back_color};";
        }
        if ($bold) $command .= "1;";
        if ($half_light) $command .= "2;";
        if ($underline) $command .= "4;";
        else $command .= "24;";
        if ($twinkle) $command .= "5;";
        else $command .= '25;';
        $command = rtrim($command, ';');
        $command .= "m{$str}\033[0m";

        return $command;
    }

    /**
     * 输出内容换行
     * @param string $text
     * @param callable $callback 如果传入该参数,则不直接输出,而作为回调参数,然后输出返回值
     */
    public static function lineWrite($text, $callback = null)
    {
        if (is_object($text) || is_array($text)) {
            $text = print_r($text, true);
        }
        if (is_callable($callback)) {
            echo $callback($text) . PHP_EOL;
        } else {
            echo $text . PHP_EOL;
        }
    }

    /**
     * 将异常信息转成简单的字符串样式
     * @param \Exception $exception
     * @return string
     */
    public static function exception2simpleString(\Exception $exception)
    {
        return $exception->getMessage() . " in file " . str_replace(ROOT_PATH, '', "{$exception->getFile()} on line {$exception->getLine()}");
    }

    /**
     * 将字符串转换为大驼峰形式(首字母大写)
     * eg: hello_world => HelloWorld
     *
     * @param $string
     * @return string
     */
    public static function caseCamel($string)
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }

    /**
     * 将字符串转换为小驼峰形式(首字母小写)。
     * eg: hello_world => helloWorld
     *
     * @param $string
     * @return string
     */
    public static function caseCamelLower($string)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string))));
    }

    /**
     * 将字符串转换中的符号(下划线,空格,点,中横线)用下划线( _ )替换,并全部转换为小写字母。
     * eg: Hello World => hello_world
     *
     * @param $string
     * @return string
     */
    public static function caseSnake($string)
    {
        return strtolower(preg_replace('/\s+/', '_', preg_replace('/[\s\._-]+/', ' ', trim($string))));
    }

    /**
     * 把字符串中的符号(下划线,空格,点,中横线),全部替换为下划线'_',并将所有英文字母转为大写
     * eg: hello world => HELLO_WORLD
     *
     * @param $string
     * @return string
     */
    public static function caseSnakeScreaming($string)
    {
        return strtoupper(preg_replace('/\s+/', '_', preg_replace('/[\s\._-]+/', ' ', trim($string))));
    }

    /**
     * 将字符串中的字母为大写时,将大写字母转换为小写字母并在其前面增加一个下划线'_',首字母大写时,只转换为小写,前面不增加下划线'_'。
     * eg: HelloWorld => hello_world
     *
     * @param $string
     * @return string
     */
    public static function caseSnakeFirstUpper($string)
    {
        $string = preg_replace('/\s+/', '_', preg_replace('/[\s\._-]+/', ' ', trim($string)));
        $string = preg_replace_callback('/([A-Z])/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $string);
        return ltrim($string, '_');
    }

    /**
     * 将字符串转换中的符号(下划线,空格,点,)用中横线'-'替换,并全部转换为小写字母。
     * eg: Hello World => hello-world
     *
     * @param $string
     * @return string
     */
    public static function caseKebab($string)
    {
        return strtolower(preg_replace('/\s+/', '-', preg_replace('/[\s\._-]+/', ' ', trim($string))));
    }

    /**
     * 将字符串中的符号(空格,下划线,点,中横线)用第二个参数进行替换
     *
     * @param string $string    原始字符串
     * @param string $delimiter 替换成的字符
     * @param bool   $lower     false为全部转换大写字母,true为全部转为小写字母
     * @return string
     */
    public static function caseDelimited($string, $delimiter = '-', $lower = true)
    {
        $string = preg_replace('/\s+/', $delimiter, preg_replace('/[\s\._-]+/', ' ', trim($string)));
        return $lower ? strtolower($string) : strtoupper($string);
    }

    /**
     * 从 controller 的全类名中提取出 controller 的名称
     * eg: \App\Controller\IndexController 结果为 index
     *
     * @param string $class 类名
     * @return string
     */
    public static function controllerClass2Name($class) {
        $ary_class = explode('\\', $class);
        $controller = array_pop($ary_class);
        $name = str_replace('Controller', '', $controller);
        return lcfirst($name);
    }

    /**
     * 从 action 的方法名中提取出 action 的名称
     * eg: indexAction 结果为 index
     *
     * @param $method
     * @return string
     */
    public static function actionMethod2Name($method) {
        $name = str_replace('Action', '', $method);
        return lcfirst($name);
    }

}