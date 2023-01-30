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

namespace jan\validators;


class UrlValidator extends Validator
{
    /**
     * @var string 正则表达式
     */
    public $pattern = '/^(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(?::\d{1,5})?(?:$|[?\/#])/i';

    /**
     * @var string[] 允许的协议
     */
    public $validSchemes = ['http', 'https'];

    /**
     * @var bool 是否必须包含协议部分
     */
    public $needSchemes = true;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = '{attribute} is not a valid URL.';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value,$attribute = '')
    {
        $schemes = '';
        // 限制长度避免DOS攻击
        if (is_string($value) && strlen($value) < 2000) {
            if ($pos = strpos($value, '://')) {
                $schemes = substr($value,0, $pos);
                $value = substr($value, $pos + 3);
            }
            if ($this->needSchemes && empty($schemes)) {
                return false;
            }
            if (!empty($schemes) && !empty($this->validSchemes) && !in_array($schemes,$this->validSchemes)) {
                return false;
            }

            return preg_match($this->pattern, $value);
        }

        return false;
    }
}
