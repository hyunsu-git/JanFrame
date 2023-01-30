<?php
/**
 * File Name: GenerateModel.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/10/7 11:23
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */


namespace jan\command\gcm;

use jan\exceptions\UserException;
use jan\helper\StringHelper;

class GenerateModel
{
    public $namespace;
    public $table;
    public $alias;
    public $class_name;
    public $file;

    protected $columns = [];
    protected $use_class = [];

    public function run()
    {
        $this->columns = HpGcm::getColumns($this->table);
        $ary_tb_name = HpGcm::getTableName($this->table);
        $temp = file_get_contents(__DIR__ . '/searcher_temp.txt');

        $rules = self::getRules($this->columns);
        $rules[] = ['space'=>3,['page', 'page_size'], 'integer',['min'=>1]];
        $rules[] = ['space'=>3,['sorter'], 'sorter'];
        $rules = $this->serializeRules($rules);

        $langs = self::getLangs($this->columns);
        $langs['page'] = '页码';
        $langs['page_size'] = '每页数量';
        $langs['sorter'] = '排序规则';
        $langs = $this->serializeLangs($langs);

        $content = HpGcm::loadTemplate($temp, [
            'classname' => $this->class_name,
            'time' => date('Y-m-d H:i:s'),
            'namespace' => $this->namespace,
            'table_mask_name' => $ary_tb_name['table_mask_name'],
            'table_name' => $ary_tb_name['table_name'],
            'alias' => $this->alias,
            'primary_key' => HpGcm::getPrimaryKey($this->columns),
            'property' => $this->getProperty(),
            'version' => FRAME_VERSION,
            'all_field' => self::getAllField($this->columns, null, false,false),
            'all_field_default_value'=>$this->getAllFieldDefaultValue(),
            'all_field_filter'=>$this->getAllFieldFilter(),
            'rules' => $rules,
            'langs' => $langs,
            'select_field' => self::getAllField($this->columns, null, false,true),
            'use_class' => self::formatUseClass($this->use_class),
        ]);

        file_put_contents($this->file, $content);
    }


    protected function getAllFieldFilter()
    {
        $temp = "
            '{{field}}' => '=',";
        $str = '';
        foreach ($this->columns as $col) {
            $str .= HpGcm::loadTemplate($temp, [
                'field' => $col['COLUMN_NAME'],
            ]);
        }
        return $str;
    }


    protected function getAllFieldDefaultValue()
    {
        $temp = "
                '{{field}}' => {{value}},";
        $str = '';
        foreach ($this->columns as $col) {
            if ($col['COLUMN_DEFAULT'] || is_numeric($col['COLUMN_DEFAULT'])) {
                $value = $col['COLUMN_DEFAULT'];
            }else{
                $value = $this->getDefaultValue($col);
            }

            $str .= HpGcm::loadTemplate($temp, [
                'field' => $col['COLUMN_NAME'],
                'value' => $value
            ]);
        }
        return $str;
    }


    protected function getDefaultValue($col)
    {
        if (in_array($col['COLUMN_NAME'], Config::defaultUserIdField())) {
            if (!in_array('common\modules\frame\token\TokenFilter', $this->use_class)) {
                $this->use_class[] = 'common\modules\frame\token\TokenFilter';
            }
            return 'TokenFilter::getPayload(\'user_id\')';
        }else if (in_array($col['COLUMN_NAME'], Config::defaultTimestampField())) {
            return 'time()';
        }


        $source = [
            'integer' => ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT'],
            'number' => ['FLOAT', 'DOUBLE', 'DECIMAL'],
            'date' => ['DATE', 'TIME', 'YEAR', 'DATETIME', 'TIMESTAMP'],
            'string' => ['CHAR', 'VARCHAR', 'TINYBLOB', 'TINYTEXT', 'BLOB', 'TEXT', 'MEDIUMBLOB', 'MEDIUMTEXT', 'LONGBLOB', 'LONGTEXT']
        ];

        $type = strtoupper($col['DATA_TYPE']);

        if (in_array($type, $source['integer'])) {
            return 0;
        } else if (in_array($type, $source['number'])) {
            return 0;
        } else if (in_array($type, $source['string'])) {
            return "''";
        } else if ($type == 'DATE') {
            return 'date("Y-m-d")';
        } else if ($type == 'TIME') {
            return 'date("H:i:s")';
        } else if ($type == 'YEAR') {
            return 'date("Y")';
        } else if ($type == 'DATETIME') {
            return 'date("Y-m-d H:i:s")';
        } else if ($type == 'TIMESTAMP') {
            return 'time()';
        } else {
            return "''";
        }
    }


    public static function getAllField($columns, $alias, $only_list = true, $backquote = true)
    {
        $ary = [];
        foreach ($columns as $item) {
            if ($only_list) {
                if (!isset($item['show_list']) || !$item['show_list']) {
                    continue;
                }
            }

            if ($alias) {
                if ($backquote) {
                    $ary[] = "'`{$alias}`.`{$item['COLUMN_NAME']}`'";
                }else{
                    $ary[] = "'{$alias}.{$item['COLUMN_NAME']}'";
                }
            } else {
                if ($backquote) {
                    $ary[] = "'`{$item['COLUMN_NAME']}`'";
                }else{
                    $ary[] = "'{$item['COLUMN_NAME']}'";
                }
            }
        }
        return implode(', ', $ary);
    }

    /**
     * 注释中的参数声明部分
     * @return string
     */
    private function getProperty()
    {
        list($int, $double) = HpGcm::columnType();

        $temp = ' * @property {{type}} ${{field}} {{comment}}';

        $str = '';
        foreach ($this->columns as $item) {
            $data_type = strtoupper($item['DATA_TYPE']);

            if (in_array($data_type, $int)) {
                $type = 'int';
            } else if (in_array($data_type, $double)) {
                $type = 'double';
            } else {
                if ($item['IS_NULLABLE'] == 'NO') {
                    $type = 'string';
                } else {
                    $type = 'string|null';
                }
            }
            if ($str) $str .= PHP_EOL;
            $str .= HpGcm::loadTemplate($temp, [
                'type' => $type,
                'field' => $item['COLUMN_NAME'],
                'comment' => $item['COLUMN_COMMENT'],
            ]);
        }
        return $str;
    }

    /**
     * 拼接国际化
     * @param $columns
     * @return array
     */
    public static function getLangs($columns)
    {
        $langs = [];
        foreach ($columns as $row) {
            if (isset($row['COLUMN_COMMENT']) && $row['COLUMN_COMMENT']) {
                $langs[$row['COLUMN_NAME']] = $row['COLUMN_COMMENT'];
            }else{
                $langs[$row['COLUMN_NAME']] = lcfirst(StringHelper::caseCamel($row['COLUMN_NAME']));
            }
        }
        return $langs;
    }

    /**
     * 拼接校验规则
     * @param $columns
     * @return array
     */
    public static function getRules($columns)
    {
        $rules = [];

        // 必须的校验数组
        $require = [[], 'required'];
        foreach ($columns as $row) {

            // 字段是必须的
//            if ($row['required']) {
//                $require[0][] = $row['name'];
//            }

            HpGcm::dataTypeRange($row);

            // 判断类型
            switch ($row['validateType']) {
                case 'string':
                    $rules[] = self::stringRule($row);
                    break;
                case 'integer':
                    $rules[] = self::numberRule($row, 'integer');
                    break;
                case 'number':
                    $rules[] = self::numberRule($row, 'number');
                    break;
                case 'boolean':
                    $rules[] = self::boolRule($row);
                    break;
                case 'in':
                    $rules[] = self::rangeRule($row);
                    break;
                case 'match':
                    $rules[] = self::matchRule($row);
                    break;
                case 'url':
                    $rules[] = self::urlRule($row);
                    break;
                case 'idcard':
                    $rules[] = self::singleRule($row, 'idcard');
                    break;
                case 'account':
                    $rules[] = self::accountRule($row);
                    break;
                case 'mobile':
                    $rules[] = self::singleRule($row, 'mobile');
                    break;
                case 'custom':
                    $rules[] = self::customerRule($row);
                    break;
            }
        }

        $rules = self::mergeRule($rules);

        if (!empty($require[0])) {
            array_unshift($rules, $require);
        }

        return $rules;
    }

    /**
     * 计算单行校验,第二和第三元素的序列值,用于合并校验
     * @param $rule
     * @return false|string
     */
    public static function serializeRow($rule)
    {
        $ary = [];
        if (isset($rule[1])) {
            $ary[] = $rule[1];
        }
        if (isset($rule[2])) {
            $ary[] = $rule[2];
        }
        return json_encode($ary);
    }

    /**
     * 合并重复的验证
     * @param $rules
     * @return array
     */
    public static function mergeRule($rules)
    {
        // 保存第23位的校验值,校验值为键,值是第一次出现的单条验证数组
        $rows = [];
        foreach ($rules as $rule) {
            $serial = self::serializeRow($rule);
            if (array_key_exists($serial, $rows)) {
                $rows[$serial][0][] = $rule[0][0];
            } else {
                $rows[$serial] = $rule;
            }
        }
        return array_values($rows);
    }


    public static function customerRule($row)
    {
        $rule = [];
        $rule['space'] = 3;
        $rule[] = [$row['COLUMN_NAME']];
        if (!$row['userFun']) {
            throw new UserException('自定义验证必须填写函数名称', 400);
        }
        $rule[] = $row['userFun'];
        return $rule;
    }


    public static function accountRule($row)
    {
        $rule = [];
        $rule['space'] = 3;
        $rule[] = [$row['COLUMN_NAME']];
        $rule[] = 'account';
        $ary = [];
        if (isset($row['accountMode']) && $row['accountMode']) {
            $ary['mode'] = $row['accountMode'];
        }
        if (isset($row['message']) && $row['message']) {
            $ary['message'] = $row['message'];
        }
        if (!empty($ary)) {
            $rule[] = $ary;
        }
        return $rule;
    }


    public static function singleRule($row, $type)
    {
        $rule = [];
        $rule['space'] = 3;
        $rule[] = [$row['COLUMN_NAME']];
        $rule[] = $type;
        $ary = [];
        if (isset($row['message']) && $row['message']) {
            $ary['message'] = $row['message'];
        }
        if (!empty($ary)) {
            $rule[] = $ary;
        }
        return $rule;
    }

    public static function urlRule($row)
    {
        $rule = [];
        $rule['space'] = 3;
        $rule[] = [$row['COLUMN_NAME']];
        $rule[] = 'url';
        $ary = [];
        if (isset($row['urlNeedScheme']) && $row['urlNeedScheme']) {
            $ary['needSchemes'] = true;
        }
        if ($row['urlValidScheme']) {
            $ary['validSchemes'] = explode(',', $row['urlValidScheme']);
        }
        if (isset($row['message']) && $row['message']) {
            $ary['message'] = $row['message'];
        }
        if (!empty($ary)) {
            $rule[] = $ary;
        }
        return $rule;
    }

    public static function matchRule($row)
    {
        $rule = [];
        $rule['space'] = 3;
        $rule[] = [$row['COLUMN_NAME']];
        $rule[] = 'match';
        $ary = [];
        if (isset($row['matchNot']) && $row['matchNot']) {
            $ary['not'] = true;
        }
        $ary['pattern'] = $row['pattern'];
        if (isset($row['message']) && $row['message']) {
            $ary['message'] = $row['message'];
        }
        if (!empty($ary)) {
            $rule[] = $ary;
        }
        return $rule;
    }


    public static function rangeRule($row)
    {
        $rule = [];
        $rule['space'] = 3;
        $rule[] = [$row['COLUMN_NAME']];
        $rule[] = 'in';
        $ary = [];
        if (isset($row['rangeNot']) && $row['rangeNot']) {
            $ary['not'] = true;
        }
        if (isset($row['rangeStrict']) && $row['rangeStrict']) {
            $ary['strict'] = true;
        }
        $ary['range'] = explode(',', $row['range']);
        if (isset($row['message']) && $row['message']) {
            $ary['message'] = $row['message'];
        }
        if (!empty($ary)) {
            $rule[] = $ary;
        }
        return $rule;
    }


    public static function boolRule($row)
    {
        $rule = [];
        $rule['space'] = 3;
        $rule[] = [$row['COLUMN_NAME']];
        $rule[] = 'boolean';
        $ary = [];
        if (isset($row['boolStrict']) && $row['boolStrict']) {
            $ary['strict'] = true;
        }
        if (isset($row['message']) && $row['message']) {
            $ary['message'] = $row['message'];
        }
        if (!empty($ary)) {
            $rule[] = $ary;
        }
        return $rule;
    }

    public static function numberRule($row, $type = 'number')
    {
        $rule = [];
        $rule['space'] = 3;
        $rule[] = [$row['COLUMN_NAME']];
        $rule[] = $type;
        $ary = [];
        if (isset($row['numMax'])) {
            $ary['max'] = $row['numMax'];
        }
        if (isset($row['numMin'])) {
            $ary['min'] = $row['numMin'];
        }
        if (isset($row['message']) && $row['message']) {
            $ary['message'] = $row['message'];
        }
        if (!empty($ary)) {
            $rule[] = $ary;
        }
        return $rule;
    }

    public static function stringRule($row)
    {
        $rule = [];
        $rule['space'] = 3;
        $rule[] = [$row['COLUMN_NAME']];
        $rule[] = 'string';
        if (isset($row['strLen']) && $row['strLen'] > 0) {
            $ary = ['length' => $row['strLen']];
            if (isset($row['message']) && $row['message']) {
                $ary['message'] = $row['message'];
            }
            $rule[] = $ary;
        } else {
            $ary = [];
            if (isset($row['strMaxLen']) && $row['strMaxLen'] > 0) {
                $ary['max'] = $row['strMaxLen'];
            }
            if (isset($row['strMinLen']) && $row['strMinLen'] > 0) {
                $ary['min'] = $row['strMinLen'];
            }
            if (isset($row['message']) && $row['message']) {
                $ary['message'] = $row['message'];
            }
            if (!empty($ary)) {
                $rule[] = $ary;
            }
        }
        return $rule;
    }

    private function serializeLangs($ary)
    {
        $str = '';
        $str .= '[';

        foreach ($ary as $key => $value) {
            if (trim($str) != '[') $str .= ', ';
            $str .= PHP_EOL;
            $str .= "\t\t\t";
            // 键不是数字,需要加上
            if (!is_numeric($key)) {
                $str .= "'{$key}' => ";
            }
            $str .= "'{$value}'";
        }
        $str .= PHP_EOL;
        $str .= "\t\t]";
        return $str;
    }

    private function serializeRules($ary, $recursion = 0)
    {
        $str = '';
        if (isset($ary['space'])) {
            for ($i = 0; $i < $ary['space']; $i++) {
                $str .= "\t";
            }
            unset($ary['space']);
        }
        $str .= '[';

        foreach ($ary as $key => $value) {
            if (trim($str) != '[') $str .= ', ';
            if ($recursion == 0) {
                $str .= PHP_EOL;
            }
            // 键不是数字,需要加上
            if (!is_numeric($key)) {
                $str .= "'{$key}' => ";
            }
            if (is_array($value)) {
                $str .= $this->serializeRules($value, $recursion + 1);
            } else {
                if (is_numeric($value)) {
                    $str .= $value;
                } else if (is_bool($value)) {
                    $str .= $value ? "true" : "false";
                } else {
                    $str .= "'{$value}'";
                }
            }
        }
        if ($recursion == 0) {
            $str .= PHP_EOL;
            $str .= "\t\t";
        }
        $str .= ']';
        return $str;
    }


    /**
     * 将引用的类格式化
     * @param $use array 要引用类的全路径一维数组
     * @return string
     */
    public static function formatUseClass($use)
    {
        $temp = 'use %s;';
        $use_str = '';
        foreach ($use as $item) {
            if($use_str) $use_str .= PHP_EOL;
            $use_str .= sprintf($temp, $item);
        }
        return $use_str;
    }

}
