<?php
/**
 * This file is part of jan-frame.
 *
 * Licensed under The MIT License
 * @author hyunsu<hyunsu@foxmail.com>
 * @link http://jan.hyunsu.cn
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version 1.0
 *
 * ============================= 重大版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace jan\basic;

/**
 * Singleton class
 * Responsible for the event scheduling of the framework engine
 *
 * the engine predefines seven events,which are triggered in different stages from initialization to termination
 * user can register event handlers to these events to perform some specific tasks
 * example:
 * ```php
 * Event::addEvent('APP_STATE_INIT_FINISH',function(){})
 * ```
 *
 * user can also customize events
 */
class Event extends BaseObject
{
    /**
     * @var mixed the singleton instance
     */
    private static $_instance = null;

    /**
     * Get the instance of the class
     * @param array $config Key value pair
     * @return static
     */
    public static function Inst($config = [])
    {
        if (self::$_instance === null) {
            self::$_instance = new static($config);
        }
        return self::$_instance;
    }

    /**
     * Prevent the class from being cloned
     */
    private function __clone()
    {

    }

    /**
     * Triggered when the engine starts execution. The state cannot register a custom handler
     * The engine handles the following tasks:
     *
     * 1. register the error handler
     * 2. register system event handlers
     * 3. load the configuration file
     */
    const STATE_BEGIN = 'APP_STATE_BEGIN';

    /**
     * application initialization
     * From this state, user can register a custom handler and get the configuration information
     * The engine handles the following tasks:
     *
     * 1. Initialize components
     */
    const STATE_INIT = 'APP_STATE_INIT';

    /**
     * Triggered before the application processes the request
     * This state is suitable for executing some global methods without knowing the specific route and parameters of the request
     * Here you can change the route and parameters of the original request
     * The engine handles the following tasks:
     *
     * 1. internationalization
     */
    const STATE_BEFORE_REQUEST = 'APP_STATE_BEFORE_REQUEST';

    /**
     * Request initialization
     * The engine handles the following tasks:
     *
     * 1. Parse the request data,
     * 2. Create a route
     */
    const STATE_INIT_REQUEST = 'APP_STATE_INIT_REQUEST';

    /**
     * Request initialization completed, start processing user logic
     * The engine handles the following tasks:
     *
     * 1. Call the controller
     */
    const STATE_PROCESS = 'APP_STATE_PROCESS';

    /**
     * Request processing completed
     */
    const STATE_AFTER_REQUEST = 'APP_STATE_AFTER_REQUEST';

    /**
     * Application end event, is the last event in the framework
     * The state cannot register a custom handler
     * The engine handles the following tasks:
     *
     * 1. Output the response
     * 2. Record log
     * 3. Execute the exit function
     */
    const STATE_END = 'APP_STATE_END';

    protected $events = [];

    /**
     * Add an event handle to the event
     * Events with the same name can have multiple handlers, call one by one in order
     * The handle can be a callback function, a method name, or a method in a class.
     * When the handle is a callback function or method name, the third parameter is invalid
     *
     * ```php
     * // callback function
     * Event::addEvent('APP_STATE_INIT_FINISH',function(){})
     *
     * // method name
     * function test(){}
     * Event::addEvent('APP_STATE_INIT_FINISH','test')
     * Event::addEvent('APP_STATE_INIT_FINISH','Class::method')
     *
     * // method in object
     * class Test{
     *    public function run(){}
     * }
     * $test = new Test();
     * Event::addEvent('APP_STATE_INIT_FINISH',$test,'run')
     * ```
     *
     * @param string $name event name
     * @param callable|string|object $obj event handle
     * @param string $handle `$obj` is object, this is the method name of the object
     */
    public function addEvent($name, $obj, $handle = '')
    {
        if (is_string($obj) || is_callable($obj)) {
            $this->events[$name][] = $obj;
        } else if (is_object($obj) && !empty($handle)) {
            $this->events[$name][] = ['class' => $obj, 'handle' => $handle];
        }
    }

    /**
     * Remove all event handle for the event
     * @param string $name event name
     * @return bool successfully return true. If the event does not exist, return false
     */
    public function removeEvent($name)
    {
        if (isset($this->events[$name])) {
            unset($this->events[$name]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Actively trigger an event, with unlimited number of parameters
     *
     * example:
     * ```php
     * Event::trigger('APP_STATE_INIT_FINISH');
     * Event::trigger('APP_STATE_INIT_FINISH',value1,value2,value3...);
     * ```
     *
     * @param string $name event name
     * @param mixed $param Parameters to be passed to listen to events
     */
    public function trigger($name, $param = null)
    {
        if (isset($this->events[$name])) {
            $args = func_get_args();
            array_shift($args);
            foreach ($this->events[$name] as $fun) {
                if (is_string($fun) || is_callable($fun)) {
                    call_user_func_array($fun, $args);
                } else {
                    call_user_func_array(array($fun['class'], $fun['handle']), $args);
                }
            }
        }
    }
}