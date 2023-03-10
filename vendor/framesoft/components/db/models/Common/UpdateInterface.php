<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 */
namespace jan\components\db\models\Common;

use jan\components\db\models\QueryInterface;

/**
 *
 * An interface for UPDATE queries.
 *
 * @package Aura.SqlQuery
 *
 */
interface UpdateInterface extends QueryInterface, WhereInterface, ValuesInterface
{
    /**
     *
     * Sets the table to update.
     *
     * @param string $table The table to update.
     *
     * @return $this
     *
     */
    public function table($table);
}
