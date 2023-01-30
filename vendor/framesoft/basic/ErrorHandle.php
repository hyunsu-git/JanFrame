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

use Jan;
use jan\components\response\ResponseTrait;
use jan\helper\StringHelper;
use jan\helper\VarDumper;
use jan\web\UserException;

/**
 * 错误和异常处理类
 * 注意：
 * 在 `APP_STATE_BEGIN` 事件之前，框架引擎自身的错误无法捕获
 * 在 `APP_STATE_INIT` 事件之前，由于日志组件还未初始化，错误会以文件的形式记录到应用根目录下
 */
class ErrorHandle extends Component implements IErrorHandle
{
    use ResponseTrait;

    /**
     * @var Exception 当前正在处理的异常
     */
    public $exception;

    /**
     * @var bool 是否已经调用过 register() 方法，防止重复注册
     */
    protected $_registered = false;

    /**
     * 注册自定义错误处理和异常处理
     */
    public function register()
    {
        if (!$this->_registered) {
            ini_set('display_errors', false);
            set_exception_handler([$this, 'exceptionHandler']);
            set_error_handler([$this, 'errorHandler']);
            register_shutdown_function([$this, 'fatalErrorHandler']);
            $this->_registered = true;
        }
    }

    /**
     * 卸载自定义错误处理和异常处理
     */
    public function unregister()
    {
        if ($this->_registered) {
            restore_error_handler();
            restore_exception_handler();
            $this->_registered = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
    }

    /**
     * @param $exception
     * @return void
     */
    public function exceptionHandler($exception)
    {
        if ($exception instanceof ExitException) {
            Event::Inst()->trigger(Event::STATE_END, $exception->statusCode);
            return;
        }
        $this->exception = $exception;

        // 禁用错误捕获以避免在处理异常时出现递归错误
        $this->unregister();

        if (PHP_SAPI !== 'cli') {
            if (Jan::$app != null && Jan::$app->response !== null) {
                Jan::$app->response->setStatusCodeByException($exception);
            } else {
                http_response_code(500);
            }
        }

        // 对于 UserException 特殊处理
        if ($exception instanceof UserException) {
            if (Jan::$app != null && Jan::$app->response !== null) {
                Jan::$app->response->setData($this->fail($exception->getMessage()));
            }
            Event::Inst()->trigger(Event::STATE_END, $exception->statusCode);
            return;
        }

        try {
            self::recordException($exception);
            Event::Inst()->trigger(Event::STATE_END, 1);
        } catch (\Exception $e) {
            self::recordException($e);
            exit(1);
        } catch (\Throwable $e) {
            self::recordException($e);
            exit(1);
        }
    }

    /**
     * @return void
     */
    public function fatalErrorHandler() {
        // 当自动加载类发生错误时候将不起作用,需要手动加载
        if (!class_exists('jan\basic\ErrorException', false)) {
            require_once StringHelper::combPath(ENGINE_PATH, 'basic', 'ErrorException.php');
        }

        $error = error_get_last();

        if (ErrorException::isFatalError($error)) {
            $exception = new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            $this->exception = $exception;
            self::recordException($exception);
            if (WORKER_MODE && EXCEPTION_END_ALL_PROCESS) {
                $master_pid = \posix_getppid();
                \posix_kill($master_pid, SIGINT);
            }
            exit(1);
        }
    }

    /**
     * 错误处理程序
     *
     * 关于错误级别的说明，可以参考这篇博客 https://blog.csdn.net/u012830303/article/details/126758557
     *
     * @param int $error_level Error level
     * @param string $error_message Error message
     * @param string $error_file Error file
     * @param int $error_line Error line
     * @return void
     * @throws ErrorException
     */
    public function errorHandler($error_level, $error_message, $error_file, $error_line)
    {
        if (error_reporting() & $error_level) {
            // 当自动加载类发生错误时候将不起作用,需要手动加载
            if (!class_exists('jan\basic\ErrorException', false)) {
                require_once StringHelper::combPath(ENGINE_PATH, 'basic', 'ErrorException.php');
            }
            $exception = new ErrorException($error_message, $error_level, $error_level, $error_file, $error_line);

            if (PHP_VERSION_ID < 70400) {
                // php低版本中不能在 __toString() 抛出异常,会导致致命错误
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                array_shift($trace);
                foreach ($trace as $frame) {
                    if ($frame['function'] === '__toString') {
                        $this->exceptionHandler($exception);
                        exit(1);
                    }
                }
            }
            throw $exception;
        }
    }

    /**
     * 记录异常信息
     * 默认情况下，通过日志组件进行记录。如果日志组件未初始化，它将直接记录到根目录中的文件中
     *
     * @param \Exception $exception
     * @return void
     */
    public static function recordException($exception){
        if (Jan::$app !== null && Jan::$app->log !== null) {
            Jan::$app->log->error($exception);
            Jan::$app->log->flush();
        } else {
            $msg = "An Error occurred while handling another error:" . PHP_EOL;
            $msg .= (string)$exception . PHP_EOL;
            if (ENV_DEBUG) {
                if (PHP_SAPI === 'cli') {
                    echo StringHelper::commandColor($msg, COMMAND_COLOR_RED) . PHP_EOL;
                } else {
                    echo '<pre>' . htmlspecialchars($msg, ENT_QUOTES) . '</pre>';
                }
            } else {
                echo 'An internal server error occurred.';
            }
            $msg .= "\n\$_SERVER = " . VarDumper::export($_SERVER);
            file_put_contents(APP_PATH . DS . "error.log", $msg, FILE_APPEND);
        }
    }

}