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

use jan\basic\Component;
use jan\basic\InvalidArgumentException;

/**
 * 将返回到客户端的信息以JSON格式返回
 */
class JsonResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function run() {}

    /**
     * JSON Content Type
     */
    const CONTENT_TYPE_JSONP = 'application/javascript';
    /**
     * JSONP Content Type
     */
    const CONTENT_TYPE_JSON = 'application/json';

    /**
     * 自定义响应头中的 Content-Type 字段的值
     * 当该值为 null 时，将根据 useJsonp 属性自动设置
     *
     * @var string|null
     */
    public $contentType;

    /**
     * 是否使用 JSONP 格式返回
     * 当该值为 true 时，响应数据必须是一个数组，包含 data 和 callback 两个元素
     * data 为要返回的数据，callback 为 JavaScript 函数名
     * 该函数将会被传入 data 作为参数
     *
     * @var bool
     */
    public $useJsonp = false;

    /**
     * 传递给 [[Json::encode()]] 的编码选项
     * 具体查看 <https://secure.php.net/manual/en/function.json-encode.php>
     * 默认值为 `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`
     * 当 [[useJsonp]] 为 true 时，该属性无效
     *
     * @var int
     */
    public $encodeOptions = 320;

    /**
     * 是否将输出格式化为更易于阅读的格式
     * 如果为true，`JSON_PRETTY_PRINT`将被添加到 [[encodeOptions]。
     * 当 [[useJsonp]] 为 true 时，该属性无效
     *
     * @var bool
     */
    public $prettyPrint = false;


    /**
     * Formats the specified response.
     *
     * @param Response $response the response to be formatted.
     */
    public function format($response)
    {
        if ($this->contentType === null) {
            $this->contentType = $this->useJsonp
                ? self::CONTENT_TYPE_JSONP
                : self::CONTENT_TYPE_JSON;
        }
        if (strpos($this->contentType, 'charset') === false) {
            $this->contentType .= '; charset=' . $response->charset;
        }

        $response->getHeaders()->set('Content-Type', $this->contentType);

        if ($this->useJsonp) {
            $this->formatJsonp($response);
        } else {
            $this->formatJson($response);
        }
    }

    /**
     * Formats response data in JSON format.
     *
     * @param Response $response
     */
    protected function formatJson($response)
    {
        if ($response->data !== null) {
            $options = $this->encodeOptions;
            if ($this->prettyPrint) {
                $options |= JSON_PRETTY_PRINT;
            }
            $response->content = json_encode($response->data, $options);
        }
    }

    /**
     * Formats response data in JSONP format.
     *
     * @param Response $response
     */
    protected function formatJsonp($response)
    {
        $data = $response->getData();
        if (is_array($data) && isset($data['data'], $data['callback'])) {
            $response->setContent(
                sprintf(
                    '%s(%s);',
                    $data['callback'],
                    self::htmlEncode($data['data'])
                )
            );
        } elseif ($data !== null) {
            $response->setContent("");
            throw new InvalidArgumentException("The 'jsonp' response requires that the data be an array consisting of both 'data' and 'callback' elements.");
        }
    }


    public static function htmlEncode($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }
}