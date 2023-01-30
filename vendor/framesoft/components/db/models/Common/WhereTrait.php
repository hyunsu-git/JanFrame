<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 */
namespace jan\components\db\models\Common;

/**
 *
 * Common code for WHERE clauses.
 *
 * @package Aura.SqlQuery
 *
 */
trait WhereTrait
{

    /**
     *
     * Format query condition
     * supporting key value pairs
     * and `IN` conditions by binding an array
     *
     * To avoid the field being `table.col` and quoted styles,
     * Placeholder usage order increment
     *
     * @param string|array $field Field to query
     *
     * @param string|array $value The value binding to the placeholder
     *
     * @return array
     *
     */
    protected function formatPlaceHolderAndValue($field, $value)
    {
        static $i = 0;
        $i++;
        if (is_array($value)) {
            $placeholders = [];   // 保存所有占位符
            $values = [];  // 保存占位符对应的值
            foreach ($value as $ci => $item) {
                $bind = 'ibp' . $i . $ci;
                $placeholders[] = ':' . $bind;
                $values[$bind] = $item;
            }
            return array(
                'placeholder' => sprintf('%s IN (%s)', $field, implode(',', $placeholders)),
                'value' => $values
            );
        } else {
            $bind = "dbp{$i}";
            return array(
                'placeholder' => "{$field}=:{$bind}",
                'value' => [$bind => $value]
            );
        }
    }

    /**
     *
     * Adds a WHERE condition to the query by AND.
     *
     * @param string|array $cond The WHERE condition.
     *
     * @param array $bind Values to be bound to placeholders
     *
     * @return $this
     *
     */
    public function where($cond, array $bind = [])
    {
        if (is_array($cond)) {
            foreach ($cond as $key => $value) {
                $ary = $this->formatPlaceHolderAndValue($key, $value);
                $this->addClauseCondWithBind('where', 'AND', $ary['placeholder'], $ary['value']);
            }
        } else {
            $this->addClauseCondWithBind('where', 'AND', $cond, $bind);
        }
        return $this;
    }

    /**
     *
     * Adds a WHERE condition to the query by OR. If the condition has
     * ?-placeholders, additional arguments to the method will be bound to
     * those placeholders sequentially.
     *
     * @param string $cond The WHERE condition.
     *
     * @param array $bind Values to be bound to placeholders
     *
     * @return $this
     *
     * @see where()
     *
     */
    public function orWhere($cond, array $bind = [])
    {
        if (is_array($cond)) {
            foreach ($cond as $key => $value) {
                $ary = $this->formatPlaceHolderAndValue($key, $value);
                $this->addClauseCondWithBind('where', 'OR', $ary['placeholder'], $ary['value']);
            }
        } else {
            $this->addClauseCondWithBind('where', 'OR', $cond, $bind);
        }
        return $this;
    }
}
