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

namespace jan\components\request;

use Jan;
use jan\basic\Component;
use jan\basic\InvalidConfigException;
use jan\web\Controller;
use jan\web\HeaderCollection;
use jan\web\UploadedFile;
use ReflectionMethod;

/**
 * 请求组件
 * 保存了本次请求路由部分信息
 * 提供用于获取请求各种信息的方法
 * @property HeaderCollection $headers
 */
class Request extends Component
{
    /**
     * 是否匹配到路由规则
     * 关于路由请参考路由组件
     *
     * @var bool
     * @see \jan\components\router\Router
     */
    public $matchRule = false;

    /**
     * @var string `$matchRule`为true时候有值，本次请求匹配的路由规则
     */
    public $routeRule = '';

    /**
     * @var mixed `$matchRule`为true时候有值，路由的处理方法
     */
    public $handle = null;

    /**
     * @var string `$matchRule`为false时候有值
     */
    public $module = '';

    /**
     * @var string `$matchRule`为false时候有值
     */
    public $controller = '';

    /**
     * @var string `$matchRule`为false时候有值
     */
    public $action = '';

    /**
     * @var string `$matchRule`为false时候有值，controller的全限定类名
     */
    public $controllerClass = '';

    /**
     * @var array 模糊匹配时候可用，保存路由规则中参数的值
     *            例如：路由规则为：/user/<id:\d+>，则保存的值为：['id' => 1]
     */
    public $params = [];

    /**
     * @var array 保存请求方式对应的解析类
     * eg:['application/json' => '\jan\components\request\JsonParser']
     */
    public $parsers = [];

    /**
     * @var string 请求的客户端IP地址
     */
    public $clientIp = '';

    /**
     * @var string 请求方式，例如： GET, POST, HEAD, PUT, PATCH, DELETE.
     */
    public $requestMethod = '';

    /**
     * @var bool 当前请求是否是ajax请求
     */
    public $isAjax = false;

    /**
     * @var bool 当前请求是否是post请求
     */
    public $isPost = false;

    /**
     * @var bool 当前请求是否是get请求
     */
    public $isGet = false;

    /**
     * @var bool 当前请求是否是put请求
     */
    public $isPut = false;

    /**
     * @var bool 当前请求是否是delete请求
     */
    public $isDelete = false;

    /**
     * @var bool 当前请求是否是options请求
     */
    public $isOptions = false;

    /**
     * @var bool 当前是否是命令行模式
     */
    public $isCli = false;

    /**
     * @var HeaderCollection 请求头信息
     * @see getHeaders()
     */
    protected $headers;

    /**
     * @var array 保存所有上传文件的信息
     * 可以通过这些信息实例化 `UploadedFile` 类
     */
    protected $files;

    /**
     * @var mixed 保存http请求原始数据
     */
    private $rawBody;

    /**
     * @var array 保存body体中的数据
     */
    private $bodyParams;

    /**
     * @var bool 和i18n模块联动
     * 如果请求头部带有 `Accept-Language` 属性，则会自动设置为全局语种
     */
    public $linkI18n = true;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();
        $this->initMethod();
        $this->clientIp = $this->getClientIp();
    }

    /**
     * {@inheritdoc}
     */
    public function run() {}

    /**
     * 在路由解析完成后调用此方法以开始执行用户的处理程序
     * 根据路由规则的匹配情况，执行不同的处理方法
     *
     * @return void
     * @throws InvalidConfigException
     * @throws \ReflectionException
     * @throws \jan\di\NotInstantiableException
     */
    public function execute()
    {
        if ($this->matchRule) {
            $this->execHandle();
        } else {
            $this->execMca();
        }
    }

    /**
     * 当匹配到路由规则时，执行路由规则的处理方法
     *
     * @return void
     * @throws InvalidConfigException
     * @throws \ReflectionException
     * @throws \jan\di\NotInstantiableException
     */
    protected function execHandle()
    {
        if (is_string($this->handle)) {
            // 字符串，直接作为结果
            Jan::$app->response->setData($this->handle);
        } else if (is_array($this->handle)) {
            // 数组，第1个元素为类名，第2个元素为方法名
            if (sizeof($this->handle) != 2) {
                throw new InvalidConfigException('Invalid route handle: ' . $this->routeRule . ', Must be an array containing 2 elements');
            }
            $class = $this->handle[0];
            $method = $this->handle[1];
            if (class_exists($class)) {
                $class = Jan::createObject($class, [], $class, false);
                if (method_exists($class, $method)) {
                    $props = [];
                    $method = new ReflectionMethod($class, $method);
                    $params = $method->getParameters();
                    foreach ($params as $ref_param) {
                        $name = $ref_param->getName();
                        $value = $this->param($name);
                        if ($value === null) {
                            $value = $this->get($name);
                        }
                        if ($value === null) {
                            if ($ref_param->isDefaultValueAvailable()) {
                                $value = $ref_param->getDefaultValue();
                            }
                        }
                        $props[] = $value;
                    }
                    $result = $method->invokeArgs($class, $props);
                    Jan::$app->response->setData($result);
                } else {
                    throw new InvalidConfigException('Invalid route handle: ' . $this->routeRule . ', Method not found: ' . $method);
                }
            } else {
                throw new InvalidConfigException('Invalid route handle: ' . $this->routeRule . ', Class not exists');
            }
        } else if (is_callable($this->handle)) {
            // 回调函数，直接执行
            $result = call_user_func($this->handle);
            Jan::$app->response->setData($result);
        } else {
            throw new InvalidConfigException('Invalid route handle: ' . $this->routeRule . ', Must be a callable or string or array');
        }
    }

    /**
     * 当没有匹配到路由规则时，对 url 进行解析，执行对应的控制器方法
     *
     * @return void
     * @throws InvalidConfigException
     * @throws \ReflectionException
     * @throws \jan\components\router\NotFoundRouteException
     * @throws \jan\di\NotInstantiableException
     */
    protected function execMCA()
    {
        if (!class_exists($this->controllerClass)) {
            Jan::$app->response->setStatusCode(404);
            return;
        }
        /**
         * @var $class Controller
         */
        $class = Jan::createObject($this->controllerClass, array(
            'actionName' => $this->action,
        ), $this->controllerClass, true);
        $class->run();
    }


    /**
     * 获取客户端的IP地址
     *
     * @return string
     */
    public static function getClientIp()
    {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_REAL_IP')) {
            $ip = getenv('HTTP_X_REAL_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
            $ips = explode(',', $ip);
            $ip = $ips[0];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '0.0.0.0';
        }

        return $ip;
    }

    /**
     * 对当前请求模式相关参数初始化
     */
    private function initMethod()
    {
        if ((isset($_SERVER['HTTP_CONTENT_TYPE'])
                && strtolower($_SERVER['HTTP_CONTENT_TYPE']) == 'application/json')
            ||
            (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
            $this->isAjax = true;
        }

        if (!IS_CLI) {
            $method = strtoupper($_SERVER['REQUEST_METHOD']);
            switch ($method) {
                case 'GET':
                    $this->isGet = true;
                    break;
                case 'POST':
                    $this->isPost = true;
                    break;
                case 'PUT':
                    $this->isPut = true;
                    break;
                case 'DELETE':
                    $this->isDelete = true;
                    break;
                case 'OPTIONS':
                    $this->isOptions = true;
                    break;
            }
        } else {
            $this->isCli = true;
        }
    }

    /**
     * 获取请求的header信息
     *
     * @return HeaderCollection
     */
    public function getHeaders()
    {
        if ($this->headers === null) {
            $this->headers = new HeaderCollection();
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                foreach ($headers as $name => $value) {
                    $this->headers->add($name, $value);
                }
            } elseif (function_exists('http_get_request_headers')) {
                $headers = http_get_request_headers();
                foreach ($headers as $name => $value) {
                    $this->headers->add($name, $value);
                }
            } else {
                $headers = $this->getAllRequestHeaders();
                foreach ($headers as $name => $value) {
                    $this->headers->add($name, $value);
                }
            }
        }

        return $this->headers;
    }

    /**
     * 获取请求的头部信息
     * `getallheaders()` 和 `http_get_request_headers()` 两个函数在 apache 下才可以使用
     * 该函数的作用和这两个函数类似
     * 就是找到$_SERVER变量中以HTTP_开头的属性，对属性做一个字符串替换这样。
     * $_SERVER变量中的HTTP_USER_ID被替换成User-Id
     *
     * 注意：
     * 关于自定义Http头， 需要注意头的命名规范，不能用下划线命名，否则在nginx服务器下可能读取不到，
     * 在查找命名规范的时候，有提到自定义属性用X-开头这个问题。后来查阅了一些资料，http协议不建议这样去做。
     *
     * @return array
     */
    function getAllRequestHeaders()
    {
        $headers = array();

        $copy_server = array(
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        );

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copy_server[$key])) {
                $headers[$copy_server[$key]] = $value;
            }
        }

        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }


    /**
     * 根据上传字段名称,获取上传的文件
     * 该方法不能获取多个文件
     * 同一个字段下多个上传文件,该方法会返回NUll
     *
     * @param string $name 上传文件的字段名
     * @return UploadedFile|null
     */
    public function getFileByName($name)
    {
        $files = $this->loadFiles();
        return isset($files[$name]) ? new UploadedFile($files[$name]) : null;
    }

    /**
     * 根据上传字段名获取多个上传文件
     * 同一个上传字段下有多个文件,应该使用此方法获取
     * 和 `getFileByName()` 不同,即使只有一个上传文件,该方法也是返回数组
     *
     * @param $name
     * @return array|UploadedFile[]
     */
    public function getFilesByName($name)
    {
        $files = $this->loadFiles();
        if (isset($files[$name])) {
            return [new UploadedFile($files[$name])];
        }
        $results = [];
        foreach ($files as $key => $file) {
            if (strpos($key, "{$name}[") === 0) {
                $results[] = new UploadedFile($file);
            }
        }

        return $results;
    }

    /**
     * 获取上传的文件信息
     */
    private function loadFiles()
    {
        if ($this->files === null) {
            $this->files = [];
            if (isset($_FILES) && is_array($_FILES)) {
                foreach ($_FILES as $class => $info) {
                    $this->loadFilesRecursive($class, $info['name'], $info['tmp_name'], $info['type'], $info['size'], $info['error']);
                }
            }
        }
        return $this->files;
    }

    /**
     * 循环获取文件信息
     *
     * @param $key
     * @param $names
     * @param $tempNames
     * @param $types
     * @param $sizes
     * @param $errors
     */
    private function loadFilesRecursive($key, $names, $tempNames, $types, $sizes, $errors)
    {
        if (is_array($names)) {
            foreach ($names as $i => $name) {
                self::loadFilesRecursive($key . '[' . $i . ']', $name, $tempNames[$i], $types[$i], $sizes[$i], $errors[$i]);
            }
        } elseif ((int)$errors !== UPLOAD_ERR_NO_FILE) {
            $this->files[$key] = [
                'name'     => $names,
                'tempName' => $tempNames,
                'type'     => $types,
                'size'     => $sizes,
                'error'    => $errors,
            ];
        }
    }

    /**
     * 返回原始HTTP请求的正文
     *
     * @return false|string
     */
    public function getRawBody()
    {
        if ($this->rawBody === null) {
            $this->rawBody = file_get_contents('php://input');
        }
        return $this->rawBody;
    }

    /**
     * 返回指定的GET请求参数,如果没有指定名称,返回全部GET参数
     *
     * @param string $name         字段名称
     * @param string $defaultValue 如果参数为空,返回默认值
     * @return mixed
     */
    public function get($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $_GET;
        }

        return isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
    }

    /**
     * 返回指定的POST请求参数,如果没有指定名称,返回全部POST参数
     *
     * @param string $name         字段名称
     * @param mixed  $defaultValue 默认值
     * @return mixed
     */
    public function post($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $this->getBodyParams();
        }

        return $this->getBodyParam($name, $defaultValue);
    }

    /**
     * 在模糊匹配模式路由中，返回路由规则中的指定参数的值，如果没有指定名称，则返回所有参数
     *
     * @param string $name         参数名称
     * @param mixed  $defaultValue 默认值
     * @return mixed
     */
    public function param($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $this->params;
        }

        return isset($this->params[$name]) ? $this->params[$name] : $defaultValue;
    }

    /**
     * 从请求中获取指定参数的值
     * 相当于 `get()` 和 `post()` 和 `param()` 的合并
     * 当存在同名参数的时候，优先级为：
     * 1. 模糊匹配模式路由中的参数
     * 2. GET参数
     * 3. POST参数
     *
     * @param string $name         参数名称
     * @param mixed  $defaultValue 默认值
     * @return mixed
     */
    public function value($name, $defaultValue = null)
    {
        $value = $this->param($name);
        if ($value === null) {
            $value = $this->get($name);
        }
        if ($value === null) {
            $value = $this->post($name);
        }
        return $value === null ? $defaultValue : $value;
    }

    /**
     * 返回body体中指定的请求参数
     *
     * @param string $name         参数名称
     * @param mixed  $defaultValue 如果没有传入参数,使用的默认值
     * @return mixed|null
     */
    public function getBodyParam($name, $defaultValue = null)
    {
        $params = $this->getBodyParams();

        if (is_object($params)) {
            try {
                return $params->$name;
            } catch (\Exception $e) {
                return $defaultValue;
            }
        }
        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    /**
     * 获取body体所有的请求参数
     * 返回的参数经过了解析器处理，解析器可以通过配置文件进行配置
     *
     * @return array|mixed
     */
    public function getBodyParams()
    {
        if ($this->bodyParams === null) {
            $rawContentType = $this->getContentType();

            if ($rawContentType && isset($this->parsers[$rawContentType])) {
                // 使用对应配置解析数据
                /**
                 * @var $parser RequestParserInterface
                 */
                $parser = Jan::createObject($this->parsers[$rawContentType]);
                $this->bodyParams = $parser->parse($this->getRawBody());

            } elseif (isset($this->parsers['*'])) {
                // 使用通用配置解析数据
                /**
                 * @var $parser RequestParserInterface
                 */
                $parser = Jan::createObject($this->parsers['*']);
                $this->bodyParams = $parser->parse($this->getRawBody());

            } elseif ($this->isPost) {
                $this->bodyParams = $_POST;
            } else {
                $this->bodyParams = [];
                mb_parse_str($this->getRawBody(), $this->bodyParams);
            }
        }

        return $this->bodyParams;
    }

    /**
     * 获取请求MINE类型
     *
     * @return string
     */
    public function getContentType()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $rawContentType = $_SERVER['CONTENT_TYPE'];
        } else {
            $rawContentType = 'application/x-www-form-urlencoded; charset=' . Jan::$app->charset;
        }
        if (($pos = strpos($rawContentType, ';')) !== false) {
            // e.g. text/html; charset=UTF-8
            $contentType = substr($rawContentType, 0, $pos);
        } else {
            $contentType = $rawContentType;
        }

        return $contentType;
    }
}