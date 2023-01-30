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

namespace jan\components\db;

use jan\basic\Component;
use jan\components\db\models\Exception;
use jan\components\db\models\QueryInterface;
use PDO;
use PDOException;
use PDOStatement;

/**
 * 数据库组件
 * 该类只负责数据库的连接和执行语句,并不处理具体的sql语句构造
 */
class Connection extends Component
{
    const MySQL = 'Mysql';
    const PgSQL = 'Pgsql';
    const SQLite = 'Sqlite';
    const SQLServer = 'Sqlsrv';

    /**
     * @var array 主从库的所有配置
     * eg:
     * [
     *  masters=>[
     *      'db1'=>[db_type=>mysql, db_host=192.168.0.1, db_user=root, ...],
     *      '名称2'=>[db_type=>mysql, db_host=192.168.0.2, db_user=root, ...],
     *  ],
     *  slaves=>[
     *      '从库名称1'=>[],
     *      ...
     *  ]
     * ]
     */
    public $configs;

    /**
     * @var PDO PDO实例
     */
    public $pdo;

    /**
     * @var PDOStatement
     */
    protected $sQuery;

    /**
     * @var string 最后一次执行的sql语句
     */
    protected $lastSql;

    /**
     * @var bool 是否开启了事务
     */
    protected $is_begin_trans = false;

    /**
     * @var bool 使用的主库的连接配置
     * 经过一致性hash计算得到
     * 每个实例中,只会获取一次
     */
    protected $master_config;

    /**
     * @var bool 使用的从库的连接配置
     * 经过一致性hash计算得到
     * 每个实例中,只会获取一次
     */
    protected $slave_config;

    /**
     * @var array 保存所有的连接
     * 键值对存储
     * 键是连接使用的配置进行 hash 得到
     * 值是连接句柄
     */
    protected $connections = [];

    /**
     * @var array PDO 连接的配置项
     */
    public $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    );

    /**
     * Connection constructor.
     * 重写构造方法,对 db.options 配置项进行单独的处理
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        // options 配置需要单独处理
        if (isset($config['options'])) {
            if (is_array($config['options'])) {
                foreach ($config['options'] as $index => $opt) {
                    $this->options[$index] = $opt;
                }
            }
            unset($config['options']);
        }
        parent::__construct($config);
    }

    /**
     * 连接数据库
     * @param array $config 使用的配置
     * @return PDO
     */
    public function connect($config = [])
    {
        if (empty($config)) {
            $config = $this->getConfig('master');
        }
        $hash = $this->hashArray($config);
        if (isset($this->connections[$hash]) && !empty($this->connections[$hash])) {
            $this->pdo = $this->connections[$hash];
        } else {
            if (isset($config['charset']) && !empty($config['charset'])) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '" . $config['charset'] . "'";
            }
            $pdo = new PDO($config['dsn'], $config['user'], $config['password'], $this->options);
            $this->connections[$hash] = $pdo;
            $this->pdo = $pdo;
        }

        return $this->pdo;
    }

    /**
     * 关闭连接
     */
    public function closeConnection()
    {
        $this->sQuery = null;
        $this->pdo = null;
    }

    /**
     * 执行查询并获取结果
     * @param string|QueryInterface $query
     * @param mixed $params
     * @param int $fetch_style
     * @return array|int|null
     */
    public function query($query, $params = null, $fetch_style = PDO::FETCH_ASSOC)
    {
        $type = $this->executeQuery($query, $params);

        // 获取执行结果
        switch ($type) {
            case 'select':
            case 'show':
                // 没有结果返回空数组
                return $this->sQuery->fetchAll($fetch_style);
            case 'update':
            case 'delete':
            case 'replace':
                return $this->sQuery->rowCount();
            case 'insert':
                if ($this->sQuery->rowCount() > 0) {
                    return $this->pdo->lastInsertId();
                } else {
                    return null;
                }
            default:
                return null;
        }
    }

    /**
     * 获取一行
     * @param string|QueryInterface $query
     * @param mixed $params
     * @param int $fetch_style
     * @return mixed 在所有情况下，失败都返回 FALSE
     */
    public function row($query, $params = null, $fetch_style = PDO::FETCH_ASSOC)
    {
        $type = $this->executeQuery($query, $params);
        if ($type !== 'select' && $type !== 'show') {
            throw new Exception('The row() function can only be used in query statements');
        }
        return $this->sQuery->fetch($fetch_style);
    }

    /**
     * 获取一列
     * @param string|QueryInterface $query
     * @param mixed $params
     * @return array 没有结果返回空数组
     */
    public function column($query, $params = null)
    {
        $type = $this->executeQuery($query, $params);
        if ($type !== 'select' && $type !== 'show') {
            throw new Exception('The row() function can only be used in query statements');
        }
        $columns = $this->sQuery->fetchAll(PDO::FETCH_NUM);
        $column = [];
        foreach ($columns as $cells) {
            $column[] = $cells[0];
        }
        return $column;
    }

    /**
     * 获取单个值
     * @param string|QueryInterface $query
     * @param mixed $params
     * @return mixed
     */
    public function single($query, $params = null)
    {
        $type = $this->executeQuery($query, $params);
        if ($type !== 'select' && $type !== 'show') {
            throw new Exception('The row() function can only be used in query statements');
        }
        return $this->sQuery->fetchColumn();
    }

    /**
     * 格式化sql语句
     * @param string $query
     * @param array $config
     * @return string
     */
    protected function formatStatement($query, $config)
    {
        // 追加表前缀
        $prefix = "";
        if (isset($config['table_prefix']) && $config['table_prefix']) {
            $prefix = $config['table_prefix'];
        }
        $query = preg_replace_callback('/\\{\\{%?([\w\-\.]+)\\}\\}/', function ($match) use ($prefix) {
            return $prefix . $match[1];
        }, $query);

        // 格式化成单行
        if (ENV_DEBUG) {
            $query = preg_replace("/\s+/", ' ', $query);
        }

        return $query;
    }

    /**
     * 执行查询
     * @param string|QueryInterface $query
     * @param mixed $params
     * @return string
     */
    protected function executeQuery($query, $params = null)
    {
        // 判断是原生语句还是构造对象
        if ($query instanceof QueryInterface) {
            $sql = $query->getStatement();
            if (is_array($params) && !empty($params)) {
                $params = array_merge($query->getBindValues(), $params);
            } else {
                $params = $query->getBindValues();
            }
        } else if (is_string($query)) {
            $sql = trim($query);
        } else {
            throw new Exception('Invalid database query statement');
        }
        // 获取语句类型
        $type = $this->getStatementType($sql);
        // 根据语句类型使用不同配置
        if ($type === 'select' || $type === 'show') {
            $config = $this->getConfig('slave');
        } else {
            $config = $this->getConfig('master');
        }
        $sql = $this->formatStatement($sql, $config);
        // 记录最后执行的语句
        $this->lastSql = $sql;
        // 执行语句
        $this->execute($sql, $params, $config);
        return $type;
    }

    /**
     * 获取语句类型
     * @param string $sql
     * @return string select|update|delete|insert|replace|show
     */
    protected function getStatementType($sql)
    {
        $rawStatement = explode(" ", $sql);
        return strtolower(trim($rawStatement[0]));
    }

    /**
     * 获取数组的hash值
     * @param $ary
     * @return string
     */
    protected function hashArray($ary)
    {
        return md5(serialize($ary));
    }

    /**
     * 执行
     * @param $query
     * @param array $parameters
     * @param array $config
     */
    protected function execute($query, $parameters = [], $config = [])
    {
        try {
            $pdo = $this->connect($config);
            $this->sQuery = $pdo->prepare($query);
            $this->sQuery->execute($parameters);
        } catch (PDOException $e) {
            // 服务端断开时重连一次
            if (isset($e->errorInfo[1]) && ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013)) {
                $this->sQuery = null;
                $this->closeConnection();

                try {
                    $pdo = $this->connect();
                    $this->sQuery = $pdo->prepare($query);
                    $this->sQuery->execute($parameters);
                } catch (PDOException $ex) {
                    $this->rollBackTrans();
                    throw $ex;
                }
            } else {
                $this->rollBackTrans();
                $msg = $e->getMessage();
                $err_msg = "SQL:" . $this->lastSQL() . " " . $msg;
                throw new PDOException($err_msg, (int)$e->getCode());
            }
        }
    }

    /**
     * 开始事务
     */
    public function beginTrans()
    {
        try {
            $this->connect();
            $tag = $this->pdo->beginTransaction();
            $this->is_begin_trans = true;
            return $tag;
        } catch (PDOException $e) {
            // 服务端断开时重连一次
            if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
                $this->closeConnection();
                $this->connect();
                $tag = $this->pdo->beginTransaction();
                $this->is_begin_trans = true;
                return $tag;
            } else {
                throw $e;
            }
        }
    }

    /**
     * 事务回滚
     */
    public function rollBackTrans()
    {
        $this->is_begin_trans = false;
        if ($this->pdo && $this->pdo->inTransaction()) {
            return $this->pdo->rollBack();
        }
        return true;
    }

    /**
     * 提交事务
     */
    public function commitTrans()
    {
        $this->is_begin_trans = false;
        return $this->pdo->commit();
    }

    /**
     * @return string
     */
    public function lastSQL()
    {
        return $this->lastSql;
    }

    /**
     * 获取数据库连接配置
     * @param string $client
     * @return array
     */
    protected function getConfig($client)
    {
        $client = strtolower($client);
        $db_cluster = isset($this->configs['dbCluster']) ? $this->configs['dbCluster'] : false;
        if ($client == 'slave' && $db_cluster && isset($this->configs['slaves']) && !$this->is_begin_trans) {
            if (empty($this->slave_config)) {
                $this->slave_config = self::getConfigByHash($this->configs['slaves']);
            }
            return $this->slave_config;
        } else {
            if (empty($this->master_config)) {
                $this->master_config = $this->getConfigByHash($this->configs['masters']);
            }
            return $this->master_config;
        }
    }

    /**
     * 使用hash从多个配置中选择一个
     * @param array $configs 多个配置的二维数组
     * eg:
     * [
     *      'db1'=>[db_type=>mysql, db_host=192.168.0.1, db_user=root, ...],
     *      '名称2'=>[db_type=>mysql, db_host=192.168.0.2, db_user=root, ...],
     * ]
     * @return array
     */
    protected static function getConfigByHash($configs)
    {
        $hash = new ConsistentHash();
        foreach ($configs as $server => $item) {
            $hash->addServer($server);
        }

        // 使用当前微秒数做为key,随机获取一个配置
        $key = microtime(true);
        $key = $hash->lookup($key);
//        var_dump("使用db" . $key);

        if (array_key_exists($key, $configs)) {
            $config = $configs[$key];
            $db_type = strtolower($config['db_type']);
            $dsn = "{$db_type}:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
            if (isset($config['db_charset'])) {
                $dsn .= ";charset={$config['db_charset']}";
            }
            return [
                'type' => $config,
                'user' => $config['db_user'],
                'password' => $config['db_pass'],
                'dsn' => $dsn,
                'charset' => isset($config['db_charset']) ? $config['db_charset'] : null,
                'table_prefix' => isset($config['table_prefix']) ? $config['table_prefix'] : null
            ];
        }
        return [];
    }

    public function run()
    {
    }
}