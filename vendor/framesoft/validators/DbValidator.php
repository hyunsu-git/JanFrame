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


use jan\components\db\models\QueryFactory;

class DbValidator extends Validator
{
    /**
     * @var string 数据表的名称
     */
    public $table;

    /**
     * @var string 数据表中的字段名称,默认是属性名
     */
    public $field;

    /**
     * @var bool 是否校验数据唯一
     */
    public $unique = false;

    /**
     * @var bool 是否校验数据存在
     */
    public $exist = false;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            if ($this->unique) {
                $this->message = '{attribute} has already been taken.';
            }
            if ($this->exist) {
                $this->message = '{attribute} does not exist.';
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, $attribute = '')
    {
        if (empty($this->field)) {
            $this->field = $attribute;
        }
        if (empty($this->field)) {
            throw new InvalidValidationRule('The "field" property must be set.');
        }
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $factory = new QueryFactory();
        $query = $factory->select([$this->field])
            ->from($this->table)
            ->where("{$this->field}= :value")
            ->bindValue('value', $value);
        $result = \Jan::$app->db->row($query);

        if ($this->unique && $result) {
            return false;
        }

        if ($this->exist && !$result) {
            return false;
        }

        return true;
    }

}
