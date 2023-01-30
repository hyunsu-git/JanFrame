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

/**
 * 将返回到客户端的信息以 HTML 格式输出
 */
class HtmlResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function run() {}

    /**
     * 响应格式
     * header 中的 Content-Type 字段的值
     *
     * @var string
     */
    public $contentType = 'text/html';

    /**
     * {@inheritdoc}
     */
    public function format($response)
    {
        if (stripos($this->contentType, 'charset') === false) {
            $this->contentType .= '; charset=' . $response->charset;
        }
        $response->getHeaders()->set('Content-Type', $this->contentType);
        if ($response->data !== null) {
            $response->content = $response->data;
        }
    }
}