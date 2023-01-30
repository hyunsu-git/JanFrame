<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 */
namespace jan\components\db\models;

/**
 *
 * Generic package-level exception.
 *
 * @package Aura.SqlQuery
 *
 */
class Exception extends \jan\basic\Exception
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Database Exception';
    }
}
