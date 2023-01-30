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

/**
 * Class EmailValidator
 * 邮箱验证器
 */
class EmailValidator extends Validator
{
    /**
     * @var string 用于验证邮箱的正则表达式
     */
    public $pattern = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = '{attribute} is not a valid email address.';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value,$attribute = '')
    {
        if (!is_string($value)) {
            return false;
        }elseif (!preg_match('/^(?P<name>(?:"?([^"]*)"?\s)?)(?:\s+)?(?:(?P<open><?)((?P<local>.+)@(?P<domain>[^>]+))(?P<close>>?))$/i', $value, $matches)) {
            return false;
        }else{
            if (strlen($matches['local']) > 64) {
                //用户名或其他本地部分的最大总长度为64个八位字节。RFC 5322第4.5.3.1.1节
                // http://tools.ietf.org/html/rfc5321#section-4.5.3.1.1
                return false;
            }elseif (strlen($matches['local'] . '@' . $matches['domain']) > 254) {
                //在rfc2821中，对MAIL和RCPT命令中的地址长度有限制
                //共254个字符。由于不适合这些字段的地址通常不有用，因此
                //地址长度的上限通常应视为254。

                // http://www.rfc-editor.org/errata_search.php?eid=1690
                return false;
            }else{
                return preg_match($this->pattern, $value);
            }
        }
    }
}
