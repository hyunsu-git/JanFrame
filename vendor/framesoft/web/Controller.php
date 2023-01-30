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

namespace jan\web;

use Jan;
use jan\basic\Component;
use jan\components\response\ResponseTrait;
use jan\components\router\NotFoundRouteException;
use ReflectionException;
use ReflectionMethod;

/**
 * Web应用的控制器基类
 * 所有用户自定义的 controller 都必须继承此类
 *
 * 类主要功能是调用 `action` 方法，设置 `response` 组件的返回值
 * `controller` 组件只有在自动路由中才会用到，指定规则路由中的类，可以不继承 `controller`
 *
 * 执行某个 action 的时候，优先根据请求的方法，查找对应的 action 方法
 * 如果没有找到，再查找 action 的通用方法
 * 例如：
 *
 * GET /user/info 在自动路由时，会被解析到 UserController 类中
 * 优先 `actionGetInfo` 方法，如果没有找到，再执行 `actionInfo` 方法
 *
 * action方法可以带有参数，参数的值会从请求URL中获取同名参数的值
 * 例如：
 * <?php
 * class UserController extends Controller{
 *    public function actionInfo($id){
 *       // ...
 *   }
 * }
 * // GET /user/info?id=123
 * // $id 的值为 123
 * ?>
 *
 * action方法的返回值会被设置到 response 组件的 body 属性中
 */
class Controller extends Component
{
    use ResponseTrait;

    /**
     * 将要执行的 `action` 方法名
     * eg: 'actionIndex', 'actionPostCreate'
     *
     * @var string
     */
    protected $actionMethodName = '';

    /**
     * 将要执行的 `action` 名换
     * 并不是 `action` 方法名，而是 `action` 方法名去掉前缀 `action` 后的部分
     * 注意和 `actionMethodName` 的区别
     *
     * eg: 'index', 'create', 'update', 'delete'
     * Not: 'actionIndex', 'actionPostCreate'
     *
     * @var string
     */
    public $actionName = '';

    /**
     * 当 `$actionName` 是空值时，将使用此属性作为默认的 `action` 名称
     * 会出现 `$actionName` 是空值的情况：
     *      url 路径小于 2 个部分，如：http://127.0.0.1, http://127.0.0.1/user
     *
     * 注意：最终执行的方法会在该属性前加上 `action` 前缀
     *
     * @var string
     */
    protected $defaultActionName = 'index';

    /**
     * 当 `$actionName` 不为空，但是类中没有对应的 action 方法，将执行该属性指定的方法
     * 该属性可以为空，则没有 `action` 方法时候，直接返回404
     * 如果该属性有值，但是没有对应方法，将抛出 `NotFoundRouteException` 异常
     *
     * @var string
     */
    protected $emptyActionMethodName = '';

    /**
     * `action` 方法执行前会执行 `beforeAction` 方法
     * 仅在该属性中指定的方法，适用此规则
     * 如果属性为空，表示所有方法都适用
     * 当该属性和 `$exceptFilterActions` 同时指定时，该属性优先级高
     *
     * e.g. ['actionIndex', 'actionGetInfo']
     *
     * @var array
     */
    public $onlyFilterActions = [];

    /**
     * `action` 方法执行前会执行 `beforeAction` 方法
     * 在该属性中指定的方法，不适用此规则
     * 如果属性为空，表示所有方法都适用
     * 当该属性和 `$onlyFilterActions` 同时指定时，`$onlyFilterActions`优先级高
     *
     * e.g. ['actionIndex', 'actionGetInfo']
     *
     * @var array
     */
    public $exceptFilterActions = [];

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();
        if (empty($this->actionName)) {
            $this->actionName = $this->defaultActionName;
        }
    }

    /**
     * 执行 `action` 方法前，执行该方法
     * 具体是否执行由 `$onlyFilterActions` 和 `$exceptFilterActions` 属性决定
     *
     * 该方法返回 true，表示继续执行 `action` 方法
     * 该方法返回 false，表示不执行 `action` 方法
     * 该方法返回其他值，表示不执行 `action` 方法，并将该值返回给客户端
     *
     * @return bool|mixed
     */
    protected function beforeAction()
    {
        return true;
    }

    /**
     * 根据 `$onlyFilterActions` 和 `$exceptFilterActions` 属性，判断是否执行 `beforeAction` 方法
     *
     * 如果 `$onlyFilterActions` 和 `$exceptFilterActions`都是空值，表示所有方法都执行 `beforeAction` 方法
     * 如果 `$onlyFilterActions` 不为空，表示只有在 `$onlyFilterActions` 中指定的方法，才执行 `beforeAction` 方法
     * 如果 `$onlyFilterActions`为空，并且 `$exceptFilterActions` 不为空，表示只有不在 `$exceptFilterActions` 中指定的方法，才执行 `beforeAction` 方法
     *
     * @throws NotFoundRouteException
     * @throws ReflectionException
     */
    public function run()
    {
        if (!empty($this->onlyFilterActions)) {
            $this->callAction(in_array($this->actionName, $this->onlyFilterActions));
        } else if (!empty($this->exceptFilterActions)) {
            $this->callAction(!in_array($this->actionName, $this->exceptFilterActions));
        } else {
            $this->callAction();
        }
    }

    /**
     * 调用 action 方法
     *
     * @param bool $filter 是否调用 `beforeAction` 方法
     * @return void
     * @throws NotFoundRouteException
     * @throws ReflectionException
     */
    protected function callAction($filter = true)
    {
        if ($filter) {
            $result = $this->beforeAction();
            if ($result === false) {
                return;
            } else if ($result !== true) {
                Jan::$app->response->setData($result);
                return;
            }
        }

        if (!$this->actionMethodName) {
            $this->actionMethodName = $this->combineActionMethodName();
        }
        if ($this->actionMethodName && $this->hasMethod($this->actionMethodName)) {
            $result = $this->invokeActionMethod();
            Jan::$app->response->setData($result);
            return;
        }
        if ($this->emptyActionMethodName && $this->hasMethod($this->emptyActionMethodName)) {
            $result = call_user_func(array($this, $this->emptyActionMethodName));
            Jan::$app->response->setData($result);
            return;
        }
        throw new NotFoundRouteException("Action '{$this->actionName}' not found in controller '" . static::className() . "'");
    }

    /**
     * 按照规则，组合 `action` 方法名
     * 优先根据请求方式组合，如果类中不存在该方法，则组合通用方法名
     *
     * @return string
     */
    protected function combineActionMethodName()
    {
        $action_method_name = 'action' . ucfirst(strtolower(Jan::$app->request->requestMethod)) . ucfirst($this->actionName);
        if ($this->hasMethod($action_method_name)) {
            return $action_method_name;
        }
        $action_method_name = 'action' . ucfirst($this->actionName);
        if ($this->hasMethod($action_method_name)) {
            return $action_method_name;
        }
        return "";
    }

    /**
     * 实参调用 action 方法
     *
     * @return mixed
     * @throws ReflectionException
     */
    protected function invokeActionMethod()
    {
        $props = [];
        $method = new ReflectionMethod($this, $this->actionMethodName);
        $params = $method->getParameters();
        foreach ($params as $ref_param) {
            $name = $ref_param->getName();
            $props[] = Jan::$app->request->get($name);
        }
        return $method->invokeArgs($this, $props);
    }
}