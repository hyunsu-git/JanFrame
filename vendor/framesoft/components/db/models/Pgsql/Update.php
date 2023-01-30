<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 */
namespace jan\components\db\models\Pgsql;

use jan\components\db\models\Common;

/**
 *
 * An object for PgSQL UPDATE queries.
 *
 * @package Aura.SqlQuery
 *
 */
class Update extends Common\Update implements ReturningInterface
{
    use ReturningTrait;

    /**
     *
     * Builds the statement.
     *
     * @return string
     *
     */
    protected function build()
    {
        return parent::build()
            . $this->builder->buildReturning($this->returning);
    }
}
