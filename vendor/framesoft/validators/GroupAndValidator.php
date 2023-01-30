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
 * Class GroupAndValidator
 * 针对一个字段，多组验证规则，全部通过才算通过
 */
class GroupAndValidator extends Validator
{
    /**
     * 多组验证规则
     * [
     *  item=>[
     *      ['string',max=>100],
     *      ['account'],
     *  ]
     * ]
     * @var array
     */
    public $items;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = '{attribute} is an invalid value.';
        }
    }


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
                return false;
            }
        }
        return true;
    }
}
