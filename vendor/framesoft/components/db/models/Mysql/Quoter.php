<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 */
namespace jan\components\db\models\Mysql;

use jan\components\db\models\Common;

/**
 *
 * Quote for MySQL.
 *
 * @package Aura.SqlQuery
 *
 */
class Quoter extends Common\Quoter
{
    /**
     *
     * The prefix to use when quoting identifier names.
     *
     * @var string
     *
     */
    protected $quote_name_prefix = '`';

    /**
     *
     * The suffix to use when quoting identifier names.
     *
     * @var string
     *
     */
    protected $quote_name_suffix = '`';
}
