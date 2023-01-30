<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 */
namespace jan\components\db\models\Common;

use jan\components\db\models\AbstractDmlQuery;

/**
 *
 * An object for DELETE queries.
 *
 * @package Aura.SqlQuery
 *
 */
class Delete extends AbstractDmlQuery implements DeleteInterface
{
    use WhereTrait;

    /**
     *
     * The table to delete from.
     *
     * @var string
     *
     */
    protected $from;

    /**
     *
     * Sets the table to delete from.
     *
     * @param string $from The table to delete from.
     *
     * @return $this
     *
     */
    public function from($from)
    {
        $this->from = $this->quoter->quoteName($from);
        return $this;
    }

    /**
     *
     * Builds this query object into a string.
     *
     * @return string
     *
     */
    protected function build()
    {
        return 'DELETE'
            . $this->builder->buildFlags($this->flags)
            . $this->builder->buildFrom($this->from)
            . $this->builder->buildWhere($this->where)
            . $this->builder->buildOrderBy($this->order_by);
    }
}
