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
 * Class IdcardValidator
 * 验证身份证号码
 */
class IdcardValidator extends Validator
{
    public $pattern = '/^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if ($this->message === null) {
            $this->message = '{attribute} is invalid.';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, $attribute = '')
    {
        if (!is_numeric($value) && !is_string($value)) {
            return false;
        }
        if (!preg_match($this->pattern, $value)) {
            return false;
        }

        return $this->checkId($value);
    }

    /**
     * 根据公式检查身份证号是否正确
     * @param $card
     * @return bool
     */
    private function checkId($card)
    {
        $card = strtoupper($card);
        $map = array(1, 0, 'X', 9, 8, 7, 6, 5, 4, 3, 2);
        $sum = 0;
        for ($i = 17; $i > 0; $i--) {
            $s = pow(2, $i) % 11;
            $sum += $s * $card[17 - $i];
        }
        $last = $map[$sum % 11];
        return substr($card, 17, 1) === strval($last);
    }
}
