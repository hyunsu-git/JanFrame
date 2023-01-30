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
 * Creates query statement objects.
 *
 * @package Aura.SqlQuery
 *
 */
class QueryFactory
{
    protected static $_instance = null;

    /**
     * @return QueryFactory
     */
    public static function i()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @return QueryFactory
     */
    public static function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Use the 'common' driver instead of a database-specific one.
     */
    const COMMON = 'common';

    /**
     *
     * What database are we building for?
     *
     * @param string
     *
     */
    protected $db;

    /**
     *
     * Build "common" query objects regardless of database type?
     *
     * @param bool
     *
     */
    protected $common = false;

    /**
     *
     * A map of `table.col` names to last-insert-id names.
     *
     * @var array
     *
     */
    protected $last_insert_id_names = array();

    /**
     *
     * A Quoter for identifiers.
     *
     */
    protected $quoter;

    /**
     *
     * Constructor.
     *
     * @param string $db The database type.
     *
     * @param string $common Pass the constant self::COMMON to force common
     * query objects instead of db-specific ones.
     *
     */
    public function __construct($db='Mysql', $common = null)
    {
        $this->db = ucfirst(strtolower($db));
        $this->common = ($common === self::COMMON);
    }

    /**
     *
     * Sets the last-insert-id names to be used for Insert queries..
     *
     * @param array $last_insert_id_names A map of `table.col` names to
     * last-insert-id names.
     *
     * @return null
     *
     */
    public function setLastInsertIdNames(array $last_insert_id_names)
    {
        $this->last_insert_id_names = $last_insert_id_names;
    }

    /**
     *
     * Returns a new SELECT object.
     * 
     * @param string|array $cols
     *                          
     * @return Common\SelectInterface
     *
     */
    public function select($cols = '*')
    {
        $select = $this->newInstance('Select');
        if (!empty($cols)) {
            if (is_array($cols)) {
                $select->cols($cols);
            } else {
                $select->cols(explode(',', $cols));
            }
        }
        return $select;
    }

    /**
     *
     * Returns a new INSERT object.
     *
     * @param string $into The table to insert into.
     * 
     * @return Common\InsertInterface
     *
     */
    public function insert($into = null)
    {
        $insert = $this->newInstance('Insert');
        $insert->setLastInsertIdNames($this->last_insert_id_names);
        if (!empty($into)) {
            $insert->into($into);
        }
        return $insert;
    }

    /**
     *
     * Returns a new UPDATE object.
     *
     * @param string $table The table to update.
     * 
     * @return Common\UpdateInterface
     *
     */
    public function update($table = null)
    {
        $update = $this->newInstance('Update');
        if (!empty($table)) {
            $update->table($table);
        }
        return $update;
    }

    /**
     *
     * Returns a new DELETE object.
     * 
     * @param string $table The table to delete from.
     *
     * @return Common\DeleteInterface
     *
     */
    public function delete($table = null)
    {
        $delete = $this->newInstance('Delete');
        if (!empty($table)) {
            $delete->from($table);
        }
        return $delete;
    }

    /**
     *
     * Returns a new query object.
     *
     * @param string $query The query object type.
     *
     * @return Common\SelectInterface|Common\InsertInterface|Common\UpdateInterface|Common\DeleteInterface
     *
     */
    protected function newInstance($query)
    {
        $queryClass = "jan\components\db\models\\{$this->db}\\{$query}";
        if ($this->common) {
            $queryClass = "jan\components\db\models\Common\\{$query}";
        }

        $builderClass = "jan\components\db\models\\{$this->db}\\{$query}Builder";
        if ($this->common || ! class_exists($builderClass)) {
            $builderClass = "jan\components\db\models\Common\\{$query}Builder";
        }

        return new $queryClass(
            $this->getQuoter(),
            $this->newBuilder($query)
        );
    }

    /**
     *
     * Returns a new Builder for the database driver.
     *
     * @param string $query The query type.
     *
     * @return Common\AbstractBuilder
     *
     */
    protected function newBuilder($query)
    {
        $builderClass = "jan\components\db\models\\{$this->db}\\{$query}Builder";
        if ($this->common || ! class_exists($builderClass)) {
            $builderClass = "jan\components\db\models\Common\\{$query}Builder";
        }
        return new $builderClass();
    }

    /**
     *
     * Returns the Quoter object for queries; creates one if needed.
     *
     * @return Common\Quoter
     *
     */
    protected function getQuoter()
    {
        if (! $this->quoter) {
            $this->quoter = $this->newQuoter();
        }
        return $this->quoter;
    }

    /**
     *
     * Returns a new Quoter for the database driver.
     *
     * @return Common\QuoterInterface
     *
     */
    protected function newQuoter()
    {
        $quoterClass = "jan\components\db\models\\{$this->db}\Quoter";
        if (! class_exists($quoterClass)) {
            $quoterClass = "jan\components\db\models\Common\Quoter";
        }
        return new $quoterClass();
    }
}
