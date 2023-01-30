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

namespace jan\components\session;

use Jan;
use jan\basic\Component;
use jan\basic\Exception;
use jan\basic\InvalidArgumentException;
use jan\basic\InvalidConfigException;
use jan\basic\Loader;
use jan\di\NotInstantiableException;

/**
 * Class Session
 * 可以重写 getUseCustomStorage() 返回true 用于实现自定义存储
 * 但是必须同时重写  [[openSession()]], [[closeSession()]], [[readSession()]], [[writeSession()]],
 * [[destroySession()]]  [[gcSession()]] 这些方法
 */
class Session extends Component implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var \SessionHandlerInterface|array 实现 SessionHandlerInterface 的对象或配置数组
     */
    public $handler;

    /**
     * @var array 键值对,用于覆盖使用 session_set_cookie_params() 函数设置的默认 cookie参数
     * 可能是以下键 'lifetime', 'path', 'domain', 'secure', 'httponly'
     */
    public $cookieParams = ['httponly' => true];


    /**
     * @var  array|null 在更新会话参数的使用,用于保存临时数据
     * @see freeze()
     */
    private $frozenSessionData;

    public function init()
    {
        parent::init();
        register_shutdown_function([$this, 'close']);
        if ($this->getIsActive()) {
            throw new NotInstantiableException('Session is already started!');
        }
    }

    /**
     * 是否使用自定义存储
     *
     * @return bool
     */
    public function getUseCustomStorage()
    {
        return false;
    }

    /**
     * 开启session
     * 在设置或者获取session之前,必须先调用该方法
     * 该方法可以重复调用,内部只会执行一次
     *
     * @return $this 返回自身用于链式调用
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function open()
    {
        if ($this->getIsActive()) {
            return $this;
        }

        $this->registerSessionHandler();

        $this->setCookieParamsInternal();

        ENV_DEBUG ? session_start() : @session_start();

        if (!$this->getIsActive()) {
            $error = error_get_last();
            $message = isset($error['message']) ? $error['message'] : 'Failed to start session.';
            throw new Exception($message);
        }

        return $this;
    }

    /**
     * 从session中获取值
     *
     * @param string $key
     * @param mixed  $defaultValue session没有设置时返回的默认值
     * @return mixed|null
     */
    public function get($key, $defaultValue = null)
    {
        $this->open();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
    }

    /**
     * 设置session
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        $this->open();
        $_SESSION[$key] = $value;
    }

    /**
     * 从session移除,返回移除的值
     *
     * @param string $key
     * @return mixed|null
     */
    public function remove($key)
    {
        $this->open();
        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);

            return $value;
        }

        return null;
    }

    /**
     * 清空session
     */
    public function removeAll()
    {
        $this->open();
        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * session是否设置了指定键
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $this->open();
        return isset($_SESSION[$key]);
    }

    /**
     * 关闭当前 session 并且保存数据
     */
    public function close()
    {
        if ($this->getIsActive()) {
            ENV_DEBUG ? session_write_close() : @session_write_close();
        }
    }

    /**
     * 释放 session 变量并销毁注册到 session 的所有数据
     *
     * @see open()
     * @see isActive
     */
    public function destroy()
    {
        if ($this->getIsActive()) {
            $sessionId = session_id();
            $this->close();
            $this->setId($sessionId);
            $this->open();
            session_unset();
            session_destroy();
            $this->setId($sessionId);
        }
    }

    /**
     * Gets the session ID.
     * This is a wrapper for [PHP session_id()](https://secure.php.net/manual/en/function.session-id.php).
     *
     * @return string the current session ID
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Sets the session ID.
     * This is a wrapper for [PHP session_id()](https://secure.php.net/manual/en/function.session-id.php).
     *
     * @param string $value the session ID for the current session
     */
    public function setId($value)
    {
        session_id($value);
    }

    /**
     * 使用新生成的 sessionid 更新当前 sessionid
     *
     * @param bool $deleteOldSession
     */
    public function regenerateID($deleteOldSession = false)
    {
        if ($this->getIsActive()) {
            if (ENV_DEBUG && !headers_sent()) {
                session_regenerate_id($deleteOldSession);
            } else {
                @session_regenerate_id($deleteOldSession);
            }
        }
    }

    public function getName()
    {
        return session_name();
    }

    public function setName($value)
    {
        $this->freeze();
        session_name($value);
        $this->unfreeze();
    }

    public function getSavePath()
    {
        return session_save_path();
    }

    public function setSavePath($value)
    {
        $path = Loader::getAlias($value);
        if (is_dir($path)) {
            session_save_path($path);
        } else {
            throw new InvalidArgumentException("Session save path is not a valid directory: $value");
        }
    }

    public function setCookieParams(array $value)
    {
        $this->cookieParams = $value;
    }

    /**
     * 返回一个值，该值指示是否应使用cookies存储会话ID。
     *
     * @return bool|null
     * @see setUseCookies()
     */
    public function getUseCookies()
    {
        if (ini_get('session.use_cookies') === '0') {
            return false;
        } elseif (ini_get('session.use_only_cookies') === '1') {
            return true;
        }

        return null;
    }


    /**
     * 设置是否应使用Cookie存储会话ID的值。
     * 有三种可能的状态：
     * - true 仅cookies将用于存储会话ID。
     * - false 不使用cookies存储会话ID。
     * - 如果可能，将使用cookies存储会话ID；
     *
     * @param $value
     */
    public function setUseCookies($value)
    {
        $this->freeze();
        if ($value === false) {
            ini_set('session.use_cookies', '0');
            ini_set('session.use_only_cookies', '0');
        } elseif ($value === true) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
        } else {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '0');
        }
        $this->unfreeze();
    }

    /**
     * 如果会话已启动，则无法编辑会话ini设置。在PHP7.2+中，它抛出异常。
     * 此函数用于将会话数据保存到临时变量并停止会话。
     *
     * @see unfreeze()
     */
    protected function freeze()
    {
        if ($this->getIsActive()) {
            if (isset($_SESSION)) {
                $this->frozenSessionData = $_SESSION;
            }
            $this->close();
        }
    }

    /**
     * 重新启动会话并从临时变量回复数据
     *
     * @see freeze()
     */
    protected function unfreeze()
    {
        if (null !== $this->frozenSessionData) {

            ENV_DEBUG ? session_start() : @session_start();

            if (!$this->getIsActive()) {
                $error = error_get_last();
                $message = isset($error['message']) ? $error['message'] : 'Failed to unfreeze session.';
                throw new Exception($message);
            }

            $_SESSION = $this->frozenSessionData;
            $this->frozenSessionData = null;
        }
    }


    protected function registerSessionHandler()
    {
        if ($this->handler !== null) {
            if (!is_object($this->handler)) {
                $this->handler = Jan::createObject($this->handler);
            }
            if (!$this->handler instanceof \SessionHandlerInterface) {
                throw new InvalidConfigException('"' . get_class($this) . '::handler" must implement the SessionHandlerInterface.');
            }
            ENV_DEBUG ? session_set_save_handler($this->handler, false) : @session_set_save_handler($this->handler, false);
        } elseif ($this->getUseCustomStorage()) {
            if (ENV_DEBUG) {
                session_set_save_handler(
                    [$this, 'openSession'],
                    [$this, 'closeSession'],
                    [$this, 'readSession'],
                    [$this, 'writeSession'],
                    [$this, 'destroySession'],
                    [$this, 'gcSession']
                );
            } else {
                @session_set_save_handler(
                    [$this, 'openSession'],
                    [$this, 'closeSession'],
                    [$this, 'readSession'],
                    [$this, 'writeSession'],
                    [$this, 'destroySession'],
                    [$this, 'gcSession']
                );
            }
        }
    }

    /**
     * 设置 session cookie 的参数
     * 参数必须完整,否则抛出 InvalidArgumentException 异常
     */
    private function setCookieParamsInternal()
    {
        $data = $this->getCookieParams();
        if (isset($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly'])) {
            if (PHP_VERSION_ID >= 70300) {
                session_set_cookie_params($data);
            } else {
                if (!empty($data['sameSite'])) {
                    throw new InvalidConfigException('sameSite cookie is not supported by PHP versions < 7.3.0 (set it to null in this environment)');
                }
                session_set_cookie_params($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly']);
            }

        } else {
            throw new InvalidArgumentException('Please make sure cookieParams contains these elements: lifetime, path, domain, secure and httponly.');
        }
    }

    /**
     * @return array  session cookie parameters.
     */
    public function getCookieParams()
    {
        return array_merge(session_get_cookie_params(), array_change_key_case($this->cookieParams));
    }

    /**
     * @return bool 检查session是否开启
     */
    public function getIsActive()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function getCount()
    {
        $this->open();
        return count($_SESSION);
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        $this->open();
        return new SessionIterator();
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        $this->open();

        return isset($_SESSION[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        $this->open();

        return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->open();
        $_SESSION[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        $this->open();
        unset($_SESSION[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return $this->getCount();
    }

    /**
     * 当 getUseCustomStorage() 返回 true时候,需要重写该方法
     *
     * @param $savePath
     * @param $sessionName
     * @return bool
     */
    public function openSession($savePath, $sessionName)
    {
        return true;
    }

    /**
     * 当 getUseCustomStorage() 返回 true时候,需要重写该方法
     *
     * @return bool
     */
    public function closeSession()
    {
        return true;
    }

    /**
     * 当 getUseCustomStorage() 返回 true时候,需要重写该方法
     *
     * @param $id
     * @return string
     */
    public function readSession($id)
    {
        return '';
    }

    /**
     * 当 getUseCustomStorage() 返回 true时候,需要重写该方法
     *
     * @param $id
     * @param $data
     * @return bool
     */
    public function writeSession($id, $data)
    {
        return true;
    }

    /**
     * 当 getUseCustomStorage() 返回 true时候,需要重写该方法
     *
     * @param $id
     * @return bool
     */
    public function destroySession($id)
    {
        return true;
    }

    /**
     * 当 getUseCustomStorage() 返回 true时候,需要重写该方法
     *
     * @param $maxLifetime
     * @return bool
     */
    public function gcSession($maxLifetime)
    {
        return true;
    }

    public function run() {}
}