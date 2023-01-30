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

use DOMDocument;
use DOMElement;
use jan\basic\Component;
use DOMText;
use jan\helper\StringHelper;
use Traversable;

/**
 * 将返回到客户端的数据格式化为XML格式
 */
class XmlResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function run() {}

    /**
     * @var string 响应格式 header 中的 Content-Type 字段的值
     */
    public $contentType = 'application/xml';

    /**
     * @var string XML版本
     */
    public $version = '1.0';

    /**
     * @var string XML编码。如果未设置，则将使用[[Response::charset]]的值。
     */
    public $encoding;

    /**
     * @var string 根元素名称，如果设置为 null、false、空值，则不会生成根元素
     */
    public $rootTag = 'response';

    /**
     * @var string 对于索引数组，使用的元素名称
     */
    public $itemTag = 'item';

    /**
     * @var bool 是否将实现[[\Traversable]]接口的对象解释为数组。
     */
    public $useTraversableAsArray = true;

    /**
     * @var bool 是否应添加对象标记
     */
    public $useObjectTags = true;


    /**
     * {@inheritdoc}
     */
    public function format($response)
    {
        $charset = $this->encoding === null ? $response->charset : $this->encoding;
        if (stripos($this->contentType, 'charset') === false) {
            $this->contentType .= '; charset=' . $charset;
        }
        $response->getHeaders()->set('Content-Type', $this->contentType);
        if ($response->data !== null) {
            $dom = new DOMDocument($this->version, $charset);
            if (!empty($this->rootTag)) {
                $root = new DOMElement($this->rootTag);
                $dom->appendChild($root);
                $this->buildXml($root, $response->data);
            } else {
                $this->buildXml($dom, $response->data);
            }
            $response->content = $dom->saveXML();
        }
    }

    /**
     * @param DOMElement $element
     * @param mixed      $data
     */
    protected function buildXml($element, $data)
    {
        if (is_array($data) ||
            ($data instanceof Traversable && $this->useTraversableAsArray)
        ) {
            foreach ($data as $name => $value) {
                if (is_int($name) && is_object($value)) {
                    $this->buildXml($element, $value);
                } elseif (is_array($value) || is_object($value)) {
                    $child = new DOMElement($this->getValidXmlElementName($name));
                    $element->appendChild($child);
                    $this->buildXml($child, $value);
                } else {
                    $child = new DOMElement($this->getValidXmlElementName($name));
                    $element->appendChild($child);
                    $child->appendChild(new DOMText($this->formatScalarValue($value)));
                }
            }
        } elseif (is_object($data)) {
            if ($this->useObjectTags) {
                $child = new DOMElement(StringHelper::basename(get_class($data)));
                $element->appendChild($child);
            } else {
                $child = $element;
            }
            $array = [];
            foreach ($data as $name => $value) {
                $array[$name] = $value;
            }
            $this->buildXml($child, $array);
        } else {
            $element->appendChild(new DOMText($this->formatScalarValue($data)));
        }
    }

    /**
     * 格式化要在XML文本节点中使用的标量值。
     *
     * @param int|string|bool|float $value 要格式化的值。
     * @return string
     */
    protected function formatScalarValue($value)
    {
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        if (is_float($value)) {
            return self::floatToString($value);
        }
        return (string)$value;
    }

    /**
     * Safely casts a float to string independent of the current locale.
     * The decimal separator will always be `.`.
     *
     * @param float|int $number a floating point number or integer.
     * @return string|string[]
     */
    static function floatToString($number)
    {
        // . and , are the only decimal separators known in ICU data,
        // so its safe to call str_replace here
        return str_replace(',', '.', (string)$number);
    }

    /**
     * 如果名称不为空、不为int且有效，则返回 准备在DOMElement中使用的元素名称。
     * 否则返回 [[$itemTag]]
     *
     * @param mixed $name
     * @return string
     */
    protected function getValidXmlElementName($name)
    {
        if (empty($name) || is_int($name) || !$this->isValidXmlName($name)) {
            return $this->itemTag;
        }

        return $name;
    }

    /**
     * 检查名称是否可以在XML中使用。
     *
     * @param mixed $name
     * @return bool
     * @see http://stackoverflow.com/questions/2519845/how-to-check-if-string-is-a-valid-xml-element-name/2519943#2519943
     */
    protected function isValidXmlName($name)
    {
        try {
            new DOMElement($name);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
