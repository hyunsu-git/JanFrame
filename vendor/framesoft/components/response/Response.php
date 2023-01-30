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

namespace jan\components\response;

use Jan;
use jan\basic\Component;
use jan\basic\InvalidArgumentException;
use jan\basic\InvalidConfigException;
use jan\web\HeaderCollection;
use jan\web\HttpException;

/**
 * 将数据输出到客户端
 * 该组件收集了所有要输出的数据,并对数据进行格式化
 * @property mixed $data
 */
class Response extends Component
{
    const FORMAT_RAW   = 'raw';
    const FORMAT_HTML  = 'html';
    const FORMAT_JSON  = 'json';
    const FORMAT_JSONP = 'jsonp';
    const FORMAT_XML   = 'xml';

    public static $httpStatuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        118 => 'Connection timed out',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        210 => 'Content Different',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        310 => 'Too many Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested range unsatisfiable',
        417 => 'Expectation failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable entity',
        423 => 'Locked',
        424 => 'Method failure',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway or Proxy Error',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        507 => 'Insufficient storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @var int 默认返回的状态码
     */
    private $statusCode = 200;

    /**
     * @var string 返回状态码的相关信息
     */
    public $statusText = 'OK';

    /**
     * @var int 用户相关错误，默认返回的状态码
     */
    public $userErrorCode = 400;

    /**
     * @var array 格式化类型和对应类的数组
     * eg:['json'=>'\jan\components\response\JsonResponseFormatter']
     */
    public $formatters = [];

    /**
     * 对发送到客户端的数据进行统一格式化
     * 该参数决定格式化使用的类型
     * 格式化类型对应的类在$formatters中定义
     *
     * @var string
     */
    public $format = self::FORMAT_RAW;

    /**
     * @var string 请求响应使用的字符集
     * 如果不设置默认使用全局设置的字符集
     */
    public $charset;

    /**
     * @var string 使用的HTTP协议版本,如果没有设置则使用 `$_SERVER['SERVER_PROTOCOL']`
     */
    public $version;

    /**
     * @var bool 是否已经发送过信息
     */
    public $isSent = false;

    /**
     * @var mixed 要返回的原始数据,如果不为空,会格式化后赋值给 $content
     */
    protected $data;

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @var string 实际返回的数据,由$data格式化而来
     */
    protected $content;

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @var HeaderCollection
     */
    private $headers;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();
        if ($this->version === null) {
            if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0') {
                $this->version = '1.0';
            } else {
                $this->version = '1.1';
            }
        }
        if ($this->charset === null) {
            $this->charset = Jan::getConfig('charset', Jan::$app->charset);
        }
        $this->formatters = array_merge($this->defaultFormatters(), Jan::getConfig('response.formatters', []));
    }

    /**
     * @return int 返回当前的Http状态码
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * 设置Http状态码
     *
     * @param int    $value
     * @param string $text 为空时候使用默认状态码描述
     * @return $this 返回自身用于连续操作
     * @throws InvalidArgumentException
     */
    public function setStatusCode($value, $text = null)
    {
        if ($value === null) {
            $value = 200;
        }
        $this->statusCode = (int)$value;
        if ($this->getStatusCode() < 100 || $this->getStatusCode() >= 600) {
            throw new InvalidArgumentException("The HTTP status code is invalid: $value");
        }
        if ($text === null) {
            $this->statusText = isset(static::$httpStatuses[$this->statusCode]) ? static::$httpStatuses[$this->statusCode] : '';
        } else {
            $this->statusText = $text;
        }

        return $this;
    }

    /**
     * 通过异常设置状态码
     *
     * @param HttpException $exception
     * @return $this 返回自身,用于连续操作
     */
    public function setStatusCodeByException($exception)
    {
        if ($exception instanceof HttpException) {
            $this->setStatusCode($exception->statusCode);
        } else {
            $this->setStatusCode(500);
        }

        return $this;
    }

    /**
     * 设置返回Header
     *
     * @param HeaderCollection $headers
     */
    public function setHeaders(HeaderCollection $headers)
    {
        $this->headers = $headers;
    }

    /**
     * 获取返回的Header
     *
     * @return HeaderCollection
     */
    public function getHeaders()
    {
        if ($this->headers === null) {
            $this->headers = new HeaderCollection();
        }

        return $this->headers;
    }

    /**
     * 发送信息到客户端,只能发送一次
     */
    public function send()
    {
        if ($this->isSent) {
            return;
        }
        $this->prepare();
        if (!IS_CLI) {
            $this->sendHeaders();
        }
        if (!empty($this->content)) {
            echo $this->content;
        }
        $this->isSent = true;
    }

    /**
     * 清空所有返回相关的数据
     */
    public function clear()
    {
        $this->headers = null;
        $this->statusCode = 200;
        $this->statusText = 'OK';
        $this->data = null;
        $this->content = null;
        $this->isSent = false;
    }

    /**
     * 发送 header 到客户端
     *
     * @return void
     * @throws HttpException
     */
    protected function sendHeaders()
    {
        if (headers_sent($file, $line)) {
            throw new HttpException("Headers already sent in {$file} on line {$line}.");
        }
        if ($this->headers) {
            foreach ($this->getHeaders() as $name => $values) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                // set replace for first occurrence of header but false afterwards to allow multiple
                $replace = true;
                foreach ($values as $value) {
                    header("$name: $value", $replace);
                    $replace = false;
                }
            }
        }
        $statusCode = $this->getStatusCode();
        header("HTTP/{$this->version} {$statusCode} {$this->statusText}");
    }

    /**
     * 对要返回的信息进行预处理
     *
     * @return void
     * @throws InvalidConfigException
     * @throws \ReflectionException
     * @throws \jan\di\NotInstantiableException
     */
    protected function prepare()
    {
        if (!IS_CLI && $this->statusCode === 204) {
            $this->content = '';
            return;
        }

        if (isset($this->formatters[$this->format])) {
            $formatter = $this->formatters[$this->format];
            if (!is_object($formatter)) {
                $this->formatters[$this->format] = $formatter = Jan::createObject($formatter);
            }
            if ($formatter instanceof ResponseFormatterInterface) {
                $formatter->format($this);
            } else {
                throw new InvalidConfigException(null, "The '{$this->format}' response formatter is invalid. It must implement the ResponseFormatterInterface.");
            }
        } elseif ($this->format === self::FORMAT_RAW) {
            if ($this->data !== null) {
                $this->content = $this->data;
            }
        } else {
            throw new InvalidConfigException(null, "Unsupported response format: {$this->format}");
        }

        if (is_array($this->content)) {
            throw new InvalidArgumentException('Response content must not be an array.');
        } elseif (is_object($this->content)) {
            if (method_exists($this->content, '__toString')) {
                $this->content = $this->content->__toString();
            } else {
                throw new InvalidArgumentException('Response content must be a string or an object implementing __toString().');
            }
        }
    }

    /**
     * 默认的格式化器对应的类
     *
     * @return array
     */
    protected function defaultFormatters()
    {
        return [
            self::FORMAT_HTML  => [
                'class' => '\jan\components\response\HtmlResponseFormatter',
            ],
            self::FORMAT_XML   => [
                'class' => '\jan\components\response\XmlResponseFormatter',
            ],
            self::FORMAT_JSON  => [
                'class' => '\jan\components\response\JsonResponseFormatter',
            ],
            self::FORMAT_JSONP => [
                'class'    => '\jan\components\response\JsonResponseFormatter',
                'useJsonp' => true,
            ],
        ];
    }

    /**
     * 将文件以流的形式发送出去
     *
     * @param string $file 完整的文件路径
     */
    public static function sendFile($file, $headers = [])
    {
        if (!is_file($file)) return;
        $file_name = substr(strrchr($file, DS), 1);

        $default_headers = [
            //告诉浏览器这是一个文件流格式的文件
            'Content-Type'  => 'application/octet-stream',
            //请求范围的度量单位
            'Accept-Ranges' => 'bytes',
            //Content-Length是指定包含于请求或响应中数据的字节长度
            'Accept-Length' => filesize($file),
            //用来告诉浏览器，文件是可以当做附件被下载
            //            'Content-Disposition' => " attachment; filename=" . $file_name
        ];
        foreach ($headers as $name => $value) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            $default_headers[$name] = $value;
        }

        foreach ($default_headers as $name => $value) {
            header("$name: $value");
        }
        set_time_limit(0);
        readfile($file);
    }

    /**
     * {@inheritdoc}
     */
    public function run() {}
}