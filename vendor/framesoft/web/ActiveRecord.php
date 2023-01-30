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
use jan\components\db\Connection;
use jan\components\db\models\QueryFactory;
use jan\components\db\models\QueryInterface;

/**
 * 数据模型类
 * 在数据库查询中,每行数据对应一个实例
 * 在数据库 增删改 操作中,将类属性转换成对应的字段
 * 注意: 这里的属性并非指public属性,而是保存在 `$_attributes` 属性中的数据
 *
 * 根据实际需要,应该重写以下属性和方法:
 *
 *  - $tableName - 数据库表名
 *  - $tableAlias - 数据库表别名
 *  - $dbType - 数据库类型
 *
 *  - primaryKey() - 主键
 *  - defaultValue() - 默认值
 *  - selectCols() - 查询字段
 *  - filter() - 筛选条件
 *  - relationTables() - 关联表
 *  - relationCols() - 关联字段
 *
 * @see BaseActiveRecord::$_attributes 所有外部设置的属性，都实际保存在这个属性中
 *
 * @see ActiveRecord::primaryKey() 重写设置主键
 * @see ActiveRecord::defaultValue() 重写设置属性默认值
 * @see ActiveRecord::selectCols() 重写设置查询字段
 * @see ActiveRecord::filter() 重写设置筛选条件
 * @see ActiveRecord::relationTables() 重写设置关联表
 * @see ActiveRecord::relationCols() 重写设置关联字段
 *
 */
class ActiveRecord extends BaseActiveRecord
{
    /**
     * 数据表的名称
     *
     * @var string
     */
    protected static $tableName = '';

    /**
     * 数据表的别名
     *
     * @var string
     */
    protected static $tableAlias = '';

    /**
     * 适用的数据库类型
     *
     * @var string
     */
    protected static $dbType = 'Mysql';

    /**
     * @return string
     */
    public static function getDbType()
    {
        return self::$dbType;
    }

    /**
     * @param string $dbType
     */
    public static function setDbType($dbType)
    {
        self::$dbType = $dbType;
    }

    /**
     * 获取数据表名称
     *
     * @param bool $use_alias
     * @return string
     */
    public static function getTableName($use_alias = false)
    {
        if ($use_alias && static::$tableAlias) {
            return static::$tableName . ' as ' . static::$tableAlias;
        } else {
            return static::$tableName;
        }
    }

    /**
     * 获取数据表别名
     *
     * @return string
     */
    public static function getTableAlias()
    {
        return static::$tableAlias;
    }

    /**
     * 属性的默认值, 仅在设置了 scenario 属性后有效
     * 可以针对每个场景单独设置, `*` 表示适用所有场景,单独场景设置会覆盖 '*' 的设置
     *
     * 例如:
     *  [
     *      '*' => ['name' => 'jack', 'password' => '123456'],
     *      'create' => ['name' => 'bom'],
     *      'update' => ['name' => 'tina', 'age' => 12],
     *  ]
     * 在 `create` 场景下得到的默认值为
     *  ['name' => 'bom', 'password' => '123456']
     *
     * @return array 返回键值对
     */
    public function defaultValue()
    {
        return array(
            '*' => array(),
        );
    }

    /**
     * 注意: 该方法需要重写
     *
     * 所有查询显示的字段,一维数组
     * eg:
     * return ['id','name', ... ]
     *
     * @return array
     */
    public static function selectCols()
    {
        return array('*');
    }

    /**
     * 注意: 该方法需要重写,筛选查询使用
     *
     * 查询筛选器, 设置某个字段使用的查询方式
     *
     * 支持以下几种类型的筛选方式:
     *    运算符筛选 =, > , < , <> , >= , >= 传入的参数必须是字符串或数字
     *    模糊筛选 like , %like , like% , %like% 传入的参数必须是字符串
     *    包含筛选 in 传入的参数必须是数组,如果传入字符串,会被转成 = 筛选
     *    时间范围筛选 date , time , datetime, timestamp  传入的参数需要是数组,第一个元素表示起始时间(null表示不限制),第二个元素表示结束时间(null表示不限制)
     *          如果传入字符串或者数字,会根据具体的格式尝试转换,转换失败会抛出错误
     *          转换成功,根据转换的结果,大于等于当前时间,作为第二个元素,第一个元素为null,小于当前时间,作为第一个元素,第二个元素为null
     *          对于每种参数的说明
     *          date 日期范围,YYYY-MM-DD 格式
     *          time 时间范围,HH:mm:ss 格式
     *          datetime 时间范围 , YYYY-MM-DD HH:mm:ss 格式
     *          timestamp 时间戳
     *
     * 筛选方式的格式可以是字符串或者数组
     * 设置为数组格式 type 表示筛选方式，table 表示字段所在的表，可以使用别名
     * eg:
     *   name='%like%'
     *   name=>['type'=>'%like%','table'=>'t1']
     *
     * @return string[]
     */
    public static function filter()
    {
        return array();
    }

    /**
     * 注意: 该方法需要重写,关联查询使用
     *
     * 关联表设置
     * 格式为 [表名，关联条件，别名,连接方式]
     *
     * 其中连接方式可以选择 inner, left 如果不设置，默认使用 left
     *
     * eg:
     * ['tbl_user', 'ta.user_id=tb.id', 'tb', 'inner'] 类似于
     *      select * from 主表 as ta inner join tbl_user as tb on ta.user_id=tb.id
     *
     * 如果不需要别名，还需要设置连接方式，应该将第三个元素设置为 null
     *
     * @return array 返回二维数组
     */
    public static function relationTables()
    {
        return array();
    }

    /**
     * 注意: 该方法需要重写,关联查询使用
     *
     * 设置关联查询的字段
     *
     * 格式为 [表名/别名=>[字段1，字段2]]
     *
     * eg :
     * ['tb'=>['name','age','create_time as ct']] 类似于
     *      select tb.name,tb.age,tb.create_time as ct
     *
     * @return array 返回关联数组
     */
    public static function relationCols()
    {
        return array();
    }

    /**
     * 获取格式化后的关联字段
     *
     * @return array 返回 `别名.字段` 的一维数组
     */
    public static function getRelationCols()
    {
        $cols = [];
        foreach (static::relationCols() as $alias => $row) {
            foreach ($row as $col) {
                $cols[] = "$alias.$col";
            }
        }
        return $cols;
    }

    /**
     * 获取默认值
     *
     * 应该使用这个方法而不是直接调用 defaultValue() 方法
     * 该方法会根据当前设置的场景自动合并值
     *
     * @return array
     */
    public function getDefaultValue()
    {
        $values = [];
        $source = $this->defaultValue();
        if (isset($source['*'])) {
            $values = $source['*'];
        }
        if (!empty($this->scenario) && isset($source[$this->scenario])) {
            foreach ($source[$this->scenario] as $key => $val) {
                $values[$key] = $val;
            }
        }
        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function setScenario($scenario)
    {
        parent::setScenario($scenario);

        $this->initDefaultValue($this->getDefaultValue());
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->initDefaultValue($this->getDefaultValue());
    }

    /**
     * 返回数据表的主键
     * 该方法需要根据自己的表设计重写
     *
     * @param bool $use_alias 是否使用别名
     * @return string
     */
    public static function primaryKey($use_alias = false)
    {
        if ($use_alias) {
            $alias = static::getTableAlias();
            return "$alias.id";
        } else {
            return 'id';
        }
    }

    /**
     * 使用模型进行查询操作
     * 调用该方法后,可以继续调用DB模块构造sql语句的方法和 `ActiveQuery` 类中的方法进行连续操作
     *
     * @param Connection $db 使用的Connection类,默认使用 Jan::$app->db
     * @return ActiveQuery
     */
    public static function find($db = null)
    {
        $query = new ActiveQuery([
            'ard_class_name' => get_called_class(),
            'db_type'        => static::getDbType(),
        ]);
        $query->setDb($db ?: Jan::$app->db);
        return $query;
    }

    /**
     * 返回一条记录
     *
     * @param string|array $condition 查询条件,详见 findByCondition()
     * @param bool         $array     结果以数组返回,默认返回对象
     * @param Connection   $db        使用的Connection类,默认使用 Jan::$app->db
     * @return array|static|null
     * @see findByCondition() 查询条件的使用
     */
    public static function findOne($condition = null, $array = false, $db = null)
    {
        return static::findByCondition(static::find($db), $condition)->asArray($array)->one();
    }

    /**
     * 返回一条记录,使用关联查询
     * 该方法和 findOne() 方法用法一致,区别仅在于是否使用关联表查询
     *
     * @param string|array $condition 查询条件,详见 findByCondition()
     * @param bool         $array     结果以数组返回,默认返回对象
     * @param Connection   $db        使用的Connection类,默认使用 Jan::$app->db
     * @return array|static|null
     * @see findByCondition() 查询条件的使用
     * @see findOne()
     */
    public static function findRelationOne($condition = null, $array = false, $db = null)
    {
        return static::findByCondition(static::find($db), $condition)->asArray($array)->relationOne();
    }

    /**
     * 批量更新数据
     *
     * @param array        $cols      更新的字段
     * @param string|array $condition 查询条件,详见 findByCondition()
     * @param Connection   $db        使用的Connection类,默认使用 Jan::$app->db
     * @return int
     * @see findByCondition() 查询条件的使用
     */
    public static function updateAll($cols, $condition = null, $db = null)
    {
        $factory = new QueryFactory();
        $query = $factory->update();
        $query->cols($cols);
        $query->table(self::getTableName(false));
        static::findByCondition($query, $condition);
        if (empty($db)) $db = Jan::$app->db;
        return $db->query($query);
    }

    /**
     * 批量删除数据
     *
     * @param string|array $condition 查询条件,详见 findByCondition()
     * @param Connection   $db        使用的Connection类,默认使用 Jan::$app->db
     * @return int
     * @see findByCondition() 查询条件的使用
     */
    public static function deleteAll($condition, $db = null)
    {
        $factory = new QueryFactory();
        $query = $factory->delete();
        $query->from(self::getTableName(false));
        static::findByCondition($query, $condition);
        if (empty($db)) $db = Jan::$app->db;
        return $db->query($query);
    }

    /**
     * 返回符合条件的所有记录
     *
     * @param string|array $condition 查询条件,详见 findByCondition()
     * @param bool         $array     结果以二维数组返回,默认返回对象数组
     * @param Connection   $db        使用的Connection类,默认使用 Jan::$app->db
     * @return array|static[]|null
     * @see findByCondition() 查询条件的使用
     */
    public static function findAll($condition = null, $array = false, $db = null)
    {
        return static::findByCondition(static::find($db), $condition)->asArray($array)->all();
    }

    /**
     * 返回符合条件的所有记录,使用关联查询
     * 该方法和 findAll() 方法用法一致,区别仅在于是否使用关联表查询
     *
     * @param string|array $condition 查询条件,详见 findByCondition()
     * @param bool         $array     结果以二维数组返回,默认返回对象数组
     * @param Connection   $db        使用的Connection类,默认使用 Jan::$app->db
     * @return array|static[]|null
     * @see findByCondition() 查询条件的使用
     * @see findAll()
     */
    public static function findRelationAll($condition = null, $array = false, $db = null)
    {
        return static::findByCondition(static::find($db), $condition)->asArray($array)->relationAll();
    }

    /**
     * 在查询对象上追加查询条件
     *
     * @param ActiveQuery|QueryInterface $query
     * @param array|string               $condition 作为where条件
     *                                              如果是不包含等于号的字符串,将作为主键值搜索
     *                                              如果是包含等于号的字符串,将拆分并转换成数组作为查询条件
     *                                              如果是数组将直接作为查询条件
     *
     * 可以处理以下的情况:
     *  - $condition 为 'id10001'
     *                  作为主键的值
     *  - $condition 为 'name="张三"'
     *                  单个where条件
     *  - $condition 为 'name="张三",gender=1'
     *                  两个where条件之间and
     *  - $condition 为 ['name'=>'张三','gender'=>1]
     *                  数组形式的where条件
     *
     * @return ActiveQuery|QueryInterface
     */
    protected static function findByCondition($query, $condition)
    {
        if (!empty($condition)) {
            if (is_array($condition)) {
                // 数组直接作为条件
                $query->where($condition);
            } else {
                if (strpos($condition, '=') === false) {
                    // 作为主键处理
                    $primary_key = static::primaryKey();
                    $query->where([$primary_key => $condition]);
                } else {
                    // 以逗号拆分
                    $conditions = preg_split('/\s*,\s*/', trim($condition), -1, PREG_SPLIT_NO_EMPTY);
                    $where = [];
                    foreach ($conditions as $item) {
                        $ary_cond = explode('=', $item, 2);
                        $field = trim($ary_cond[0]);
                        $where[$field] = trim($ary_cond[1]);
                    }
                    $query->where($where);
                }
            }
        }

        return $query;
    }

    /**
     * 插入一条新的记录
     * 进行该操作之前,推荐调用 validate() 方法进行验证
     *
     * @param Connection $db 使用的Connection类,默认使用 Jan::$app->db
     * @return array|false|int|null
     */
    public function insert($db = null)
    {
        if (empty($db)) $db = Jan::$app->db;

        $cols = $this->toArray();
        if (empty($cols)) return false;

        $factory = new QueryFactory();
        $sql = $factory->insert()
            ->into(static::getTableName(false))
            ->cols($cols);

        return $db->query($sql);
    }

    /**
     * 根据主键更新记录
     * 如果没有设置主键,返回false,操作成功返回更新的条数
     * 进行该操作之前,推荐调用 validate() 方法进行验证
     *
     * @param Connection $db 使用的Connection类,默认使用 Jan::$app->db
     * @return array|false|int|null
     */
    public function update($db = null)
    {
        if (empty($db)) $db = Jan::$app->db;

        $key = static::primaryKey(false);
        if (empty($key) || !isset($this->$key)) {
            return false;
        }

        $cols = $this->toArray(true);
        if (empty($cols)) return false;

        $factory = new QueryFactory();
        $sql = $factory->update()
            ->table(static::getTableName(false))
            ->cols($cols)
            ->where([$key => $this->$key]);

        return $db->query($sql);
    }

    /**
     * 根据主键删除记录
     * 如果主键未设置,则返回false,删除成功返回影响条目
     *
     * @param Connection $db 使用的Connection类,默认使用 Jan::$app->db
     * @return array|false|int|null
     */
    public function delete($db = null)
    {

        if (empty($db)) $db = Jan::$app->db;

        $key = static::primaryKey(false);
        if (empty($key) || !isset($this->$key)) {
            return false;
        }

        $factory = new QueryFactory();
        $sql = $factory->delete()
            ->from(static::getTableName(false))
            ->where([$key => $this->$key]);

        return $db->query($sql);
    }

    /**
     * 初始化默认值
     *
     * @param array $source
     * @see defaultValue()
     */
    protected function initDefaultValue($source)
    {
        $fields = array_keys($source);
//        $fields = $this->filterFieldsByScenario($fields);
        foreach ($source as $field => $val) {
//            if (!in_array($field, $fields)) {
//                continue;
//            }
            $this->$field = $val;
        }
    }
}