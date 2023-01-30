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

namespace jan\components\router;

use Jan;
use jan\basic\Component;
use jan\basic\InvalidConfigException;
use jan\helper\StringHelper;
use ReflectionClass;
use ReflectionMethod;

/**
 * 路由组件
 * `Jan`框架提供了强大的路由功能，可以支持指定规则路由和自动路由两种方式
 * 指定规则路由是指写在 `routes.php` 文件中的路由规则
 * 自动路由是指不需要指定路由规则，路由组件根据当前请求的 url 自动解析到对应的类和方法。自动路由最多支持3层解析，分别对应 `module`，`controller`，`action` 三个部分。
 * 路由组件优先查询指定规则路由，如果没有匹配到规则路由，才会使用自动路由。
 */
class Router extends Component implements IRouter
{
    /**
     * @var bool 是否仅使用规则路由
     *           如果设置为 `true`，则只使用指定规则路由，不使用自动路由，如果没有匹配到规则路由，则直接返回 404
     */
    public $onlyMatchRule = false;

    /**
     * @var string 在自动路由模式下，使用的默认controller
     *             默认值是 `{当前应用}\controllers\IndexController`
     */
    public $defaultController = '';

    /**
     * @var string[] 所有的http请求方法
     */
    public static $ALL_METHOD = ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH', 'TRACE', 'CONNECT', 'CLI'];

    /**
     * @var array 序列化之后的路由规则
     */
    private $normalizedRoutes = [];

    /**
     * @return array
     */
    public function getNormalizedRoutes()
    {
        return $this->normalizedRoutes;
    }

    /**
     * Normalize routing rules
     * key is url
     * value is array, following elements:
     *      methods: array, supported request method
     *      handle: string or callback or array, first element is class name, second element is method name
     *      path: string, the path of url
     *      is_regex: boolean, Whether regular expression is required for this rule
     *      params_name: array, For fuzzy matching, the parsed parameter names are arranged in order
     *      regex: string, the regular expression of url path
     *
     * @param array $routes routing rules
     * @return void
     * @throws InvalidConfigException
     */
    protected function normalizeRoutes($routes)
    {
        $routes = $this->flattenArray($routes, '', 'ALL:');
        uksort($routes, function ($a, $b) {
            return count(explode('/', $a)) < count(explode('/', $b));
        });
        $routes = $this->parseMethod($routes);
        uasort($routes, function ($a, $b) {
            return count($a['methods']) > count($b['methods']);
        });

        foreach ($routes as $url => &$item) {
            $item['is_regex'] = false;
            $item['regex'] = '';
            $item['params_name'] = [];
            if (stripos($item['path'], ':') === false) {
                // 如果url中包含冒号，则表示需要作为正则表达式使用
                continue;
            }
            $item['is_regex'] = true;

            $ary_url = explode('/', $item['path']);
            foreach ($ary_url as $a) {
                // 对url的每一部分，如果已冒号开头，则进行正则转换
                if (stripos($a, ':') === false) {
                    $item['regex'] .= $a . '/';
                } else {
                    if (stripos($a, '<') && (stripos($a, '>'))) {
                        // 这一部分包含 <> ,表示用户自定义了正则表达式
                        $param_name = substr($a, 1, stripos($a, '<'));
                        $param_regex = substr($a, stripos($a, '<') + 1, stripos($a, '>') - stripos($a, '<') - 1);
                        $item['regex'] .= '(' . $param_regex . ')/';
                    } else {
                        $param_name = substr($a, 1);
                        $item['regex'] .= '([A-Za-z0-9_]+)/';
                    }
                    $item['params_name'][] = $param_name;
                }
            }
            $item['regex'] = substr($item['regex'], 0, -1);
        }

        $this->normalizedRoutes = $routes;
    }

    /**
     * Expand routing rule group into separate routing rules
     * The handler only specifies the class name, which is extended to multiple rules through reflection，if the same rule exists at the same level, it will not be generated
     *
     * ```php
     *  // app\controller\CommonController.php
     *  class CommonController{
     *      public function actionVersion(){
     *          return "1.0.0";
     *      }
     *      public function actionGetAppName(){
     *          return "app name";
     *      }
     *  }
     *
     *  // libs/routes.php
     *  return array(
     *      'GROUP:/v1' => array(
     *          '/user/info'=>function(){}
     *      ),
     *      '/common'=> array('app\controller\CommonController'),
     *  )
     *
     *  // will be expanded to
     *  return array(
     *      'v1/user/info'=>function(){},
     *      '/common/version'=> array('app\controller\CommonController', 'actionVersion'),
     *      'GET:/common/appName'=> array('app\controller\CommonController', 'actionGetAppName'),
     *  )
     * ```
     *
     * @param array  $routes routing rules
     * @param string $prefix use when Recursive loop
     * @param string $method use when Recursive loop
     * @return array
     * @throws InvalidConfigException
     */
    protected function flattenArray($routes, $prefix = '', $method = '')
    {
        $ary = [];
        foreach ($routes as $url => $handle) {
            if (strtoupper(substr($url, 0, 6)) === 'GROUP:') {
                $group_name = substr($url, 6);
                $ary_group_name = $this->splitByMethod($group_name);
                $ary = array_merge($ary, $this->flattenArray($handle, $prefix . $ary_group_name[1], $ary_group_name[0] ?: $method));
            } else {
                $ary_name = $this->splitByMethod($url);
                if ($ary_name[0]) {
                    $url = $ary_name[0] . $prefix . $ary_name[1];
                } else {
                    $url = $method . $prefix . $ary_name[1];
                }
                if (!is_array($handle) && !is_string($handle) && !is_numeric($handle) && !is_callable($handle)) {
                    throw new InvalidConfigException('Invalid route handle: ' . $url);
                }
                if (is_array($handle) && (count($handle) < 1 || count($handle) > 2)) {
                    throw new InvalidConfigException('Invalid route rule: ' . $url);
                }
                if (is_array($handle) && count($handle) == 1) {
                    try {
                        $reflection = new ReflectionClass($handle[0]);
                    } catch (\ReflectionException $e) {
                        throw new InvalidConfigException('Invalid route rule: ' . $url . ', ' . $e->getMessage());
                    }
                    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                    foreach ($methods as $method) {
                        if ($ary_ms = static::methodNameIsAction($method->name)) {
                            $url = $ary_ms[0] . ':' . $prefix . $ary_name[1] . '/' . lcfirst($ary_ms[1]);
                            if (!isset($ary[$url])) {
                                $ary[$url] = [$handle[0], $method->name];
                            }
                        }
                    }
                } else {
                    $ary[$url] = $handle;
                }
            }
        }
        return $ary;
    }

    /**
     * Determine whether the method name is an action
     *
     * @param string $name method name
     * @return array|false
     */
    public static function methodNameIsAction($name)
    {
        if (substr($name, 0, 6) !== 'action') {
            return false;
        }
        if (substr($name, 6, 3) === 'Get') {
            return ['GET', substr($name, 9)];
        }
        if (substr($name, 6, 4) === 'Post') {
            return ['POST', substr($name, 10)];
        }
        if (substr($name, 6, 3) === 'Put') {
            return ['PUT', substr($name, 9)];
        }
        if (substr($name, 6, 6) === 'Delete') {
            return ['DELETE', substr($name, 12)];
        }
        if (substr($name, 6, 4) === 'Head') {
            return ['HEAD', substr($name, 10)];
        }
        if (substr($name, 6, 5) === 'Patch') {
            return ['PATCH', substr($name, 11)];
        }
        if (substr($name, 6, 7) === 'Options') {
            return ['OPTIONS', substr($name, 13)];
        }
        if (substr($name, 6, 5) === 'Trace') {
            return ['TRACE', substr($name, 11)];
        }
        if (substr($name, 6, 7) === 'Connect') {
            return ['CONNECT', substr($name, 13)];
        }
        if (substr($name, 6, 3) === 'Cli') {
            return ['CLI', substr($name, 9)];
        }
        return ['ALL', substr($name, 6)];
    }

    /**
     * The url is divided into two parts according to the request method.
     * The first part is the request method (with a colon), and the second part is the path
     * The first part may be empty
     *
     * @param string $url
     * @return array
     */
    protected function splitByMethod($url)
    {
        if ($index = stripos($url, ':')) {
            $str_method = substr($url, 0, $index);
            $ary_method = explode('|', strtoupper($str_method));
            $tag = true;
            foreach ($ary_method as $method) {
                if (!in_array($method, static::$ALL_METHOD)) {
                    $tag = false;
                }
            }
            if ($tag) {
                return [strtoupper($str_method) . ':', substr($url, $index + 1)];
            }
        }
        return ['', $url];
    }

    /**
     * Parse request methods from rules as arrays
     * If the request method is 'ALL', all request methods are supported
     *
     * @param $routes
     * @return array
     */
    protected function parseMethod($routes)
    {
        $ary = [];
        foreach ($routes as $url => $handle) {
            $index = stripos($url, ':');
            $str_method = substr($url, 0, $index);
            if ($str_method === 'ALL') {
                $ary[$url] = [
                    'methods' => static::$ALL_METHOD,
                    'handle'  => $handle,
                    'path'    => substr($url, $index + 1),
                ];
            } else {
                $ary[$url] = [
                    'methods' => explode('|', $str_method),
                    'handle'  => $handle,
                    'path'    => substr($url, $index + 1),
                ];
            }
        }
        return $ary;
    }


    /**
     * {@inheritdoc}
     */
    public function setRoutes($routes)
    {
        $this->normalizeRoutes($routes);
    }


    /**
     * {@inheritdoc}
     */
    public function parseUrl()
    {
        $uri = "";
        $method = "";

        if (IS_CLI) {
            $method = 'CLI';
            if ($_SERVER['argc'] >= 2) {
                $uri = $_SERVER['argv'][1];
            }

            // todo cli 需要补充参数部分  php index.php /test a=1 b 2

        } else {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);
        }

        $uri = explode('?', $uri)[0];
        // 删除url后缀
        if ($ext = pathinfo($uri, PATHINFO_EXTENSION)) {
            $uri = preg_replace('/\.' . $ext . '$/i', '', $uri);
        }

        foreach ($this->normalizedRoutes as $url => $item) {
            if ($item['path'] == $uri && in_array($method, $item['methods'])) {
                Jan::$app->request->matchRule = true;
                Jan::$app->request->requestMethod = $method;
                Jan::$app->request->handle = $item['handle'];
                Jan::$app->request->routeRule = $url;
                return;
            }

            if ($item['is_regex'] && in_array($method, $item['methods'])) {
                if (preg_match('#^' . $item['regex'] . '$#', $uri, $matches)) {
                    Jan::$app->request->matchRule = true;
                    Jan::$app->request->requestMethod = $method;
                    Jan::$app->request->handle = $item['handle'];
                    Jan::$app->request->routeRule = $url;
                    $params = [];
                    foreach ($item['params_name'] as $k => $v) {
                        $params[$v] = $matches[$k + 1];
                    }
                    Jan::$app->request->params = $params;
                    return;
                }
            }
        }

        if ($this->onlyMatchRule) {
            Jan::$app->response->setStatusCode(404);
        } else {
            Jan::$app->request->matchRule = false;
            Jan::$app->request->requestMethod = $method;
            if ($this->urlConvertMca($uri)) {
                return;
            } else {
                // 转换失败，返回404
                Jan::$app->response->setStatusCode(404);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();
        if (!$this->defaultController) {
            $namespace = Jan::getConfig('rootNamespace', 'app');
            $this->defaultController = "{$namespace}\\controllers\\IndexController";
        }
    }

    /**
     * 将请求的url的路径部分转成 `module` `controller` `action` 并且设置请求组件
     *
     * 拆解规则：
     * 1. 路径为空的，使用默认 controller，即`$defaultController`参数
     * 2. 将路径以 `/` 分割成数组
     * 3. 如果数组长度为1，作为 controller 的名称
     * 4. 如果数组长度为2，作为 controller 和 action 的名称
     * 5. 如果数组长度为3，作为 module、controller 和 action 的名称
     *
     * @param $uri
     * @return bool
     */
    protected function urlConvertMca($uri)
    {
        $uri = trim($uri, '/');
        $namespace = Jan::getConfig('rootNamespace', 'app');

        if (!$uri) {
            Jan::$app->request->module = '';
            Jan::$app->request->controller = StringHelper::controllerClass2Name($this->defaultController);
            Jan::$app->request->action = 'index';
            Jan::$app->request->controllerClass = $this->defaultController;
        } else {
            $ary_uri = explode('/', $uri);
            if (count($ary_uri) > 3) {
                return false;
            }
            if (count($ary_uri) === 1) {
                Jan::$app->request->module = '';
                Jan::$app->request->controller = $ary_uri[0];
                Jan::$app->request->action = 'index';
                Jan::$app->request->controllerClass = "{$namespace}\\controllers\\" . ucfirst($ary_uri[0]) . "Controller";
            } else if (count($ary_uri) === 2) {
                Jan::$app->request->module = '';
                Jan::$app->request->controller = $ary_uri[0];
                Jan::$app->request->action = $ary_uri[1];
                Jan::$app->request->controllerClass = "{$namespace}\\controllers\\" . ucfirst($ary_uri[0]) . 'Controller';
            } else {
                Jan::$app->request->module = $ary_uri[0];
                Jan::$app->request->controller = $ary_uri[1];
                Jan::$app->request->action = $ary_uri[2];
                Jan::$app->request->controllerClass = "{$namespace}\\modules\\{$ary_uri[0]}\\controllers\\" . ucfirst($ary_uri[1]) . 'Controller';
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
    }
}