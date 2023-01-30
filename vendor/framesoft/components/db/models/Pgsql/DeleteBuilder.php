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
 * DELETE builder for Postgres.
 *
 * @package Aura.SqlQuery
 *
 */
class DeleteBuilder extends Common\DeleteBuilder
{
    use BuildReturningTrait;
}
