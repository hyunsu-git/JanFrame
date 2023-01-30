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

use jan\basic\Component;

/**
 * 将请求数据作为json格式进行解析
 */
class JsonParser extends Component implements RequestParserInterface
{
    /**
     * @var bool 是否将数据解析成数组
     */
    public $asArray = true;
    /**
     * @var bool 解析出错了是否抛出错误
     */
    public $throwException = true;

    /**
     * {@inheritdoc}
     */
    public function parse($rawBody)
    {
        try {
            $parameters = json_decode($rawBody, $this->asArray);
            return $parameters === null ? [] : $parameters;
        } catch (\Exception $e) {
            if ($this->throwException) {
                throw new \InvalidArgumentException('Invalid JSON data in request body: ' . $e->getMessage());
            }
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run() {}

}