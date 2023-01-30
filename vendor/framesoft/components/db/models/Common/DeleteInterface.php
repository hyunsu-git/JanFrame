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
 * An interface for DELETE queries.
 *
 * @package Aura.SqlQuery
 *
 */
interface DeleteInterface extends QueryInterface, WhereInterface
{
    /**
     *
     * Sets the table to delete from.
     *
     * @param string $from The table to delete from.
     *
     * @return $this
     *
     */
    public function from($from);
}
