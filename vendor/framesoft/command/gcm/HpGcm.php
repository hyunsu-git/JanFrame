<?php
/**
 * File Name: HpGcm.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/10/7 10:04
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */


namespace jan\command\gcm;


use Jan;
use jan\basic\Loader;
use jan\components\db\models\QueryFactory;
use jan\helper\ConfigHelper;
use jan\helper\FileHelper;

class HpGcm
{
    /**
     * 格式化命名空间,去掉两边的斜线
     * @param $namespace
     * @return string
     */
    public static function formatNamespace($namespace)
    {
        $namespace = str_replace('/', '\\', $namespace);
        $namespace = trim($namespace, '\\');
        return $namespace;
    }

    /**
     * 根据命名空间新建目录
     * @param $namespace
     * @param $path
     * @return bool|string
     */
    public static function mkdirDirByNamespace($namespace, &$path)
    {
        // 根据命名空间计算写入目录
        $class = trim(str_replace('\\', '/', $namespace), '/');
        if (empty($class)) {
            return '命名空间无效';
        }

        $root = Loader::getAlias('@' . $class, false);

        if (!$root) {
            return '命名空间无效';
        }

        if (!FileHelper::mkdir($root, false)) {
            return '新建目录失败';
        }

        $path = $root;

        return true;
    }

    /**
     * 载入模板,将模板中的双大括号包裹的变量进行替换
     * @param $template
     * @param $ary
     * @return string|string[]
     */
    public static function loadTemplate($template, $ary)
    {
        $search = [];
        $replace = [];

        foreach ($ary as $k => $v) {
            $search[] = '{{' . $k . '}}';
            $replace[] = $v;
        }

        return str_replace($search, $replace, $template);
    }


    /**
     * 获取几种格式的表名
     * @param $tablename
     * @return array
     */
    public static function getTableName($tablename)
    {
        $ary['table_name'] = $tablename;
        $prefix = ConfigHelper::getTablePrefix();
        if ($prefix) {
            $ary['table_base_name'] = str_replace($prefix, '', $tablename);
        } else {
            $ary['table_base_name'] = $tablename;
        }
        $ary['table_mask_name'] = '{{%' . $ary['table_base_name'] . '}}';

        /*
         {
            table_name: tbl_user
            table_base_name: user
            table_mask_name: {{%user}}
         }
         */
        return $ary;
    }

    /**
     * 获取主键
     * @param $columns
     * @return mixed
     */
    public static function getPrimaryKey($columns)
    {
        foreach ($columns as $item) {
            if ($item['COLUMN_KEY'] == 'PRI') {
                return $item['COLUMN_NAME'];
            }
        }

        return $columns[0]['COLUMN_NAME'];
    }

    public static function columnType()
    {
        $int = ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT'];
        $double = ['FLOAT', 'DOUBLE', 'DECIMAL'];

        return array($int, $double);
    }

    public static function getColumns($tablename)
    {
        $dbname = ConfigHelper::getDbName();

        $query = new QueryFactory();
        $sql = $query
            ->select('*')
            ->from('information_schema.COLUMNS')
            ->where([
                'TABLE_SCHEMA' => $dbname,
                'TABLE_NAME' => $tablename
            ])
            ->orderBy(['ORDINAL_POSITION'=>'asc']);
        return Jan::$app->db->query($sql);

        /*
         array(21) {
    ["TABLE_CATALOG"]=>
    string(3) "def"
    ["TABLE_SCHEMA"]=>
    string(5) "frame"
    ["TABLE_NAME"]=>
    string(16) "tbl_bk_privilege"
    ["COLUMN_NAME"]=>
    string(2) "id"
    ["ORDINAL_POSITION"]=>
    int(1)
    ["COLUMN_DEFAULT"]=>
    NULL
    ["IS_NULLABLE"]=>
    string(2) "NO"
    ["DATA_TYPE"]=>
    string(7) "varchar"
    ["CHARACTER_MAXIMUM_LENGTH"]=>
    int(64)
    ["CHARACTER_OCTET_LENGTH"]=>
    int(256)
    ["NUMERIC_PRECISION"]=>
    NULL
    ["NUMERIC_SCALE"]=>
    NULL
    ["DATETIME_PRECISION"]=>
    NULL
    ["CHARACTER_SET_NAME"]=>
    string(7) "utf8mb4"
    ["COLLATION_NAME"]=>
    string(18) "utf8mb4_general_ci"
    ["COLUMN_TYPE"]=>
    string(11) "varchar(64)"
    ["COLUMN_KEY"]=>
    string(3) "PRI"
    ["EXTRA"]=>
    string(0) ""
    ["PRIVILEGES"]=>
    string(31) "select,insert,update,references"
    ["COLUMN_COMMENT"]=>
    string(0) ""
    ["GENERATION_EXPRESSION"]=>
    string(0) ""
  }
         */
    }


    public static function dataTypeRange(&$col)
    {
        $type = strtoupper($col['DATA_TYPE']);
        switch ($type) {
            case 'TINYINT':
                $col['validateType'] = 'integer';
                if (stripos($col['COLUMN_TYPE'], 'unsigned')) {
                    $col['numMin'] = 0;
                    $col['numMax'] = 255;
                } else {
                    $col['numMin'] = -128;
                    $col['numMax'] = 127;
                }
                break;
            case 'SMALLINT':
                $col['validateType'] = 'integer';
                if (stripos($col['COLUMN_TYPE'], 'unsigned')) {
                    $col['numMin'] = 0;
                    $col['numMax'] = 65535;
                } else {
                    $col['numMin'] = -32768;
                    $col['numMax'] = 32767;
                }
                break;
            case 'MEDIUMINT':
                $col['validateType'] = 'integer';
                if (stripos($col['COLUMN_TYPE'], 'unsigned')) {
                    $col['numMin'] = 0;
                    $col['numMax'] = 16777215;
                } else {
                    $col['numMin'] = -8388608;
                    $col['numMax'] = 8388607;
                }
                break;
            case 'INT':
            case 'INTEGER':
                $col['validateType'] = 'integer';
                if (stripos($col['COLUMN_TYPE'], 'unsigned')) {
                    $col['numMin'] = 0;
                    $col['numMax'] = 4294967295;
                } else {
                    $col['numMin'] = -2147483648;
                    $col['numMax'] = 2147483647;
                }
                break;
            case 'BIGINT':
                $col['validateType'] = 'integer';
//                if (stripos($col['COLUMN_TYPE'], 'unsigned')) {
//                    $col['numMin'] = 0;
//                    $col['numMax'] = 18446744073709551615;
//                } else {
//                    $col['numMin'] = -9223372036854775808;
//                    $col['numMax'] = 9223372036854775807;
//                }
                break;
            case 'YEAR':
                $col['validateType'] = 'integer';
                $col['numMin'] = 1901;
                $col['numMax'] = 2155;
                break;
            case 'TIMESTAMP':
                $col['validateType'] = 'integer';
                $col['numMin'] = 0;
                $col['numMax'] = 2147483647;
                break;
            case 'FLOAT':
            case 'DOUBLE':
            case 'DECIMAL':
                $col['validateType'] = 'number';
                break;
            case 'CHAR':
            case 'VARCHAR':
            case 'TINYBLOB':
            case 'TINYTEXT':
            case 'BLOB':
            case 'TEXT':
            case 'MEDIUMBLOB':
            case 'MEDIUMTEXT':
            case 'LONGBLOB':
            case 'LONGTEXT':
                $col['validateType'] = 'string';
                $col['strMaxLen'] = $col['CHARACTER_MAXIMUM_LENGTH'];
                break;
            case 'DATE':
            case 'TIME':
            case 'DATETIME':
                $col['validateType'] = 'string';
                break;
            default:
                $col['validateType'] = 'string';
        }
    }

}
