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
 * Class GroupOrValidator
 * 验证器组,只要有一个验证器通过,则通过
 */
class GroupOrValidator extends GroupAndValidator
{
    /**
     * @inheritDoc
     */
    public function validateValue($value, $attribute = '')
    {
        foreach ($this->items as $row) {
            array_unshift($row, $attribute);
            $inst = self::createValidatorByRule($row, $this->model);
            if (!$inst->validate($attribute, $value)) {
                $this->message = $inst->getError();
            }else{
                return true;
            }
        }
        return false;
    }
}
