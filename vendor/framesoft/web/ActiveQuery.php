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

namespace jan\web;

use Jan;
use jan\basic\Component;
use jan\basic\InvalidArgumentException;
use jan\basic\InvalidCallException;
use jan\components\db\Connection;
use jan\components\db\models\Common\Select;
use jan\components\db\models\QueryFactory;

class ActiveQuery extends Component
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var string
     */
    protected $ard_class_name;

    /**
     * @var Select
     */
    protected $select = null;

    /**
     * @var string 数据库类型
     */
    protected $db_type = 'Mysql';

    /**
     * @var bool 是否已数组格式返回结果
     */
    protected $as_array = false;

    /**
     * @var array 筛选数据源
     */
    protected $filter_source = [];

    /**
     * @inheritDoc
     */
    public function init()
    {
        $factory = new QueryFactory($this->db_type);
        $this->select = $factory->select(null);
        parent::init();
    }

    /**
     * @param Connection $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

    /**
     * 获取筛选数据源
     *
     * @return array
     */
    public function getFilterSource()
    {
        return $this->filter_source;
    }

    /**
     * 设置筛选数据源
     *
     * @param array $source
     * @return $this
     */
    public function setFilterSource($source)
    {
        $this->filter_source = $source;
        return $this;
    }

    /**
     * 将方法调用转成对 `SelectInterface` 类的调用
     *
     * @param $name
     * @param $arguments
     * @return $this
     * @see SelectInterface
     */
    public function __call($name, $arguments)
    {
        if (is_callable([$this->select, $name])) {
            call_user_func_array([$this->select, $name], $arguments);
            return $this;
        } else {
            $class = get_called_class();
            throw new InvalidCallException("Class '{$class}' does not have a method '{$name}'");
        }
    }

    /**
     * @inheritDoc
     */
    public function hasMethod($name)
    {
        if (parent::hasMethod($name)) {
            return true;
        } else if (method_exists($this->select, $name)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 将查询结果以数组的方式返回
     *
     * @param bool $value
     * @return $this
     */
    public function asArray($value = true)
    {
        $this->as_array = $value;
        return $this;
    }

    /**
     * 检查是否设置了字段
     */
    protected function checkHasCols()
    {
        if (empty($this->select->hasCols())) {
            $cols = call_user_func([$this->ard_class_name, 'selectCols']);
            $this->select->cols($cols);
        }
    }

    /**
     * 检查是否设置了表
     */
    protected function checkHasFrom()
    {
        if (empty($this->select->getFrom())) {
            $from = call_user_func([$this->ard_class_name, 'getTableName'], false);
            $this->select->from($from);
        }
    }

    /**
     * 追加关联查询相关
     *
     * @return $this
     */
    protected function addRelation()
    {
        $this->checkHasCols();
        /*
         * 获取已经设置的cols
         *
         * 返回格式为: 有别名的,别名为下标; 没有别名的数字为下标
         * eg: ['id','name'=>'nick_name','token'=>'t1.token']
         */
        $cols = $this->select->getCols();
        $this->select->resetCols();
        // 关联查询,需要将已经设置的字段追加别名
        $alias = call_user_func([$this->ard_class_name, 'getTableAlias']);
        if (empty($alias)) {
            throw new InvalidArgumentException("If you want to use an associated query, you must set an alias ($this->ard_class_name::\$tableAlias)");
        }
        foreach ($cols as $key => $col) {
            if (!stristr($col, '.')) {
                $col = "$alias.$col";
            }
            if (is_string($key)) {
                $this->select->cols(["$col as $key"]);
            } else {
                $this->select->cols([$col]);
            }
        }
        // 设置主表
        $from = call_user_func([$this->ard_class_name, 'getTableName'], true);
        $this->select->from($from);

        // 设置关联字段
        $rel_cols = call_user_func([$this->ard_class_name, 'getRelationCols']);
        $this->select->cols($rel_cols);

        // 设置关联表
        $rel_tables = call_user_func([$this->ard_class_name, 'relationTables']);
        foreach ($rel_tables as $row) {
            $table = isset($row[2]) ? "{$row[0]} as {$row[2]}" : $row[0];
            $type = isset($row[4]) ? strtoupper($row[4]) : 'LEFT';
            switch ($type) {
                case 'INNER':
                    $this->select->innerJoin($table, $row[1]);
                    break;
                case 'LEFT':
                    $this->select->leftJoin($table, $row[1]);
                    break;
                default:
                    $this->select->join($type, $table, $row[1]);
                    break;
            }
        }

        return $this;
    }

    /**
     * 根据类型将传入的参数整理成数组
     *
     * @param array|string|int $param
     * @param string           $type 'date', 'time', 'datetime', 'timestamp' 之一
     * @return array 根据type返回 字符串数组 或者 时间戳数组
     *                               [2021-01-01,2021-12-31]
     *                               [08:00,23:59]
     *                               [1609459200,1640908800]
     */
    protected function formatRangeType($param, $type)
    {
        $format_list = [
            'date'     => 'Y-m-d',
            'time'     => 'H:i:s',
            'datetime' => 'Y-m-d H:i:s',
        ];

        if ($type == 'date' || $type == 'time' || $type == 'datetime') {
            $format = $format_list[$type];
            if (is_array($param)) {
                array_walk($param, function (&$value) use ($format) {
                    if (is_numeric($value)) {
                        $value = date($format, $value);
                    }
                });
                return $param;
            } else if (is_numeric($param)) {
                $value = date($format, $param);
                return $value >= date($format) ? [null, $value] : [$value, null];
            } else {
                return $param >= date($format) ? [null, $param] : [$param, null];
            }
        } else {
            if (is_array($param)) {
                array_walk($param, function (&$value) {
                    if (!is_numeric($value) && is_string($value)) {
                        $value = strtotime($value);
                    }
                });
                return $param;
            } else if (is_numeric($param)) {
                return $param >= time() ? [null, $param] : [$param, null];
            } else {
                $value = strtotime($param);
                return $value >= time() ? [null, $value] : [$value, null];
            }
        }
    }


    /**
     * 根据 筛选器的配置 和 筛选数据源 在sql上追加where条件
     * 调用该方法之前,必须先调用 setFilterSource() 方法,设置数据源,否则该方法没有任何作用
     *
     * @param string $table 是否在查询字段上添加表名前缀，对筛选方式为数组的无效
     * @return $this
     * @see ActiveQuery::setFilterSource() 设置筛选数据源
     * @see ActiveRecord::filter() 设置字段筛选方式
     */
    protected function whereFilter($table = null)
    {
        if (empty($this->filter_source)) return $this;

        static $i = 0;
        $filter = call_user_func([$this->ard_class_name, 'filter']);
        foreach ($filter as $field => $item) {
            // 客户端参数
            if (!isset($this->filter_source[$field])) continue;
            $value = $this->filter_source[$field];

            // 筛选类型
            $type = is_array($item) ? strtolower($item['type']) : strtolower($item);
            if (empty($type)) continue;

            // 处理筛选字段追加别名
            if (is_array($item)) {
                $field = $item['table'] . '.' . $field;
            } else if ($table) {
                $field = $table . '.' . $field;
            }

            $bind = "fdp{$i}";
            if (in_array($type, ['=', '>', '<', '<>', '>=', '<='])) {
                // where(id=:fdp0)->bind('fdp0','1000'),
                $this->select->where("{$field}{$type}:{$bind}")
                    ->bindValue($bind, $value);
            } else if (in_array($type, ['like', '%like', 'like%', '%like%'])) {
                $this->select->where("{$field} like :{$bind}")
                    ->bindValue($bind, str_replace('like', $value, $type));
            } else if ($type == 'in') {
                $this->select->where([$field => $value]);
            } else if (in_array($type, ['date', 'time', 'datetime', 'timestamp'])) {
                $param = $this->formatRangeType($value, $type);
                if (isset($param[0]) && !empty($param[0])) {
                    $this->select->where("{$field}>=:{$bind}")
                        ->bindValue($bind, $param[0]);
                }
                $i++;
                $bind2 = "fdp{$i}";
                if (isset($param[1]) && !empty($param[1])) {
                    $this->select->where("{$field}<=:{$bind2}")
                        ->bindValue($bind2, $param[1]);
                }
            } else {
                throw new InvalidArgumentException("Invalid filter option: {$type}");
            }
            $i++;
        }
        return $this;
    }

    /**
     * @param string $table
     * @return mixed|ActiveRecord|null
     */
    protected function _one($table = null)
    {
        $this->checkHasCols();
        $this->checkHasFrom();
        $this->whereFilter($table);

        $result = $this->db->row($this->select);
        if (empty($result)) return null;

        $models = $this->populate([$result]);
        return reset($models) ?: null;
    }

    /**
     * 获取一行数据
     *
     * @return array|ActiveRecord|null
     */
    public function one()
    {
        return $this->_one();
    }

    /**
     * 获取一行数据,该方法会使用关联查询
     *
     * @return array|ActiveRecord|null
     * @see one() 两个方法功能类似
     * @see ActiveRecord::relationTables()  设置关联表
     * @see ActiveRecord::relationCols()  设置关联字段
     */
    public function relationOne()
    {
        $alias = call_user_func([$this->ard_class_name, 'getTableAlias']);
        return $this->addRelation()->_one($alias);
    }

    /**
     * @param string $table
     * @return array|ActiveRecord[]
     */
    protected function _all($table = null)
    {
        $this->checkHasCols();
        $this->checkHasFrom();
        $this->whereFilter($table);

        $result = $this->db->query($this->select);
        if (empty($result)) return [];

        return $this->populate($result);
    }

    /**
     * 获取所有记录
     *
     * @return array|ActiveRecord[]
     */
    public function all()
    {
        return $this->_all();
    }

    /**
     * 获取所有记录,该方法会使用关联查询
     *
     * @return array|ActiveRecord[]
     * @see one() 两个方法功能类似
     * @see ActiveRecord::relationTables()  设置关联表
     * @see ActiveRecord::relationCols()  设置关联字段
     */
    public function relationAll()
    {
        $alias = call_user_func([$this->ard_class_name, 'getTableAlias']);
        return $this->addRelation()->_all($alias);
    }

    /**
     * 获取结果数量
     *
     * @param string $field
     * @return string
     */
    public function count($field = '*')
    {
        $this->select->resetCols();
        $this->select->cols(["COUNT($field)"]);
        return $this->db->single($this->select);
    }

    /**
     * @param int  $page
     * @param int  $num
     * @param bool $need_total
     * @param bool $array
     * @param null $table
     * @return array|int|ActiveRecord[]|null
     */
    protected function _paging_list($page, $num, $need_total = true, $array = true, $table = null)
    {
        $this->checkHasCols();
        $this->checkHasFrom();
        $this->whereFilter($table);

        if (strtolower($this->db_type) == 'mysql') {
            $this->select->calcFoundRows();
            $this->select->setPaging($num)->page($page);
            $result = $this->db->query($this->select);
            if (!$array) {
                $result = $this->populate($result);
            }
            // 不许要总数,直接返回
            if (!$need_total) return $result;

            // 获取总数
            // 注意: FOUND_ROWS()得到的数字是临时的，执行下一条语句就会失效。
            $total = $this->db->single('SELECT FOUND_ROWS()');

            return array($total, $result);

        } else {
            $this->select->setPaging($num)->page($page);
            $result = $this->db->query($this->select);
            if (!$array) {
                $result = $this->populate($result);
            }
            // 不许要总数,直接返回
            if (!$need_total) return $result;

            // 获取总数
            $this->select->resetCols();
            $this->select->cols(['count(*)']);
            $this->select->setLimit(0);
            $this->select->setOffset(0);
            $total = $this->db->single($this->select);

            return array($total, $result);
        }
    }

    /**
     * 分页获取数据,仅推荐MySQL数据库使用该方法
     * 该方法通过设置 `SQL_CALC_FOUND_ROWS` 标记来获取总数,这种方式仅适用于MySQL
     * 其它数据库通过将查询字段重置为 'count(*)' 来查询总数,效率较为低下
     *
     * @param int  $page       页码数,从1开始
     * @param int  $num        每页的数量
     * @param bool $need_total 是否需要返回总数
     * @param bool $array      是否将结果集转成数组
     * @return array|ActiveRecord[]  在[$need_total]为true的情况下,返回值为2个元素的索引数组,应该使用list()接收
     *                         第一个元素为 如果没有LIMIT时返回的行数,也就是总数
     *                         第二个元素为实际的结果集
     *                         在 $need_total 为false的情况下,直接返回结果集
     *
     * 如果使用该方法,MySQL必须计算所有结果集的行数。尽管这样，总比再执行一次不使用LIMIT的查询要快多了，因为结果集不需要返回客户端。
     * 如果不需要总数的情况下,请务必将第三个参数置为false
     */
    public function pagingList($page, $num, $need_total = true, $array = true)
    {
        return $this->_paging_list($page, $num, $need_total, $array);
    }

    /**
     * 分页获取数据,该方法使用关联查询
     * 方法的更多相关信息,查看 pagingList() 方法
     *
     * @param int  $page       页码数,从1开始
     * @param int  $num        每页的数量
     * @param bool $need_total 是否需要返回总数
     * @param bool $array      是否将结果集转成数组
     * @return array|ActiveRecord[]
     * @see pagingList() 两个方法功能类似
     * @see ActiveRecord::relationTables()  设置关联表
     * @see ActiveRecord::relationCols()  设置关联字段
     */
    public function relationPagingList($page, $num, $need_total = true, $array = true)
    {
        $alias = call_user_func([$this->ard_class_name, 'getTableAlias']);
        return $this->addRelation()->_paging_list($page, $num, $need_total, $array, $alias);
    }


    /**
     * 将一行数据库结果赋值给ActiveRecord
     *
     * @param array $rows
     * @return ActiveRecord[]|array
     */
    public function populate($rows)
    {
        if ($this->as_array) {
            return $rows;
        } else {
            /**
             * @var $models ActiveRecord[]
             */
            $models = [];
            foreach ($rows as $row) {
                /**
                 * @var $model ActiveRecord
                 */
                $model = Jan::createObject($this->ard_class_name);
                $model->populateRecord($row);
                $models[] = $model;
            }
            return $models;
        }
    }

    public function run()
    {
    }
}