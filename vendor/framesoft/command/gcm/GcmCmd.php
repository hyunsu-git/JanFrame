<?php
/**
 * File Name: GcmCmd.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/9/30 11:28
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
use jan\command\Command;
use jan\components\db\models\QueryFactory;
use jan\helper\ConfigHelper;
use jan\helper\StringHelper;

class GcmCmd extends Command
{

    /**
     * @var string 命令简写
     * 比如 list 命令,简写为 -l
     */
    public static $shortName = '-G';

    /**
     * @var string 命令的说明
     */
    public static $description = '生成数据库模型ActiveRecord文件';

    protected $namespace;
    protected $table;
    protected $alias;
    protected $class_name;
    protected $ctrl_namespace;
    protected $ctrl_classname;
    /**
     * @var array 保存所有表名,索引数组
     */
    protected $table_list;


    /**
     * 命令接收的参数和说明
     * 例如:
     * [
     *    '-f'=>'要操作的文件路径',
     *    'url'=>'访问的文件的url路径'
     * ]
     *
     * @return array 键值对
     */
    public function options()
    {
        return array(
            '-l' => '<tablename>列出全部数据库或根据名称列出部分数据库',
            '-t' => '设置要生成的数据表名称',
            '-a' => '设置要数据表名称的别名',
            '-n' => '设置生成文件的命名空间（文件位置）',
            '-c' => '设置生成文件的类名（文件名）',
            '-cc' => '设置生成Controller的类名（文件名）',
            '-cn' => '设置生成Controller的命名空间（文件位置）',
        );
    }


    /**
     * 实际执行的方法
     * @param array $args 命令行后面跟的参数
     */
    public function exec($args)
    {
        if (empty($this->table_list)) {
            $this->get_all_table();
        }
        if (isset($args['-l'])) {
            $this->option_list();
            return;
        }
        $this->option_table(isset($args['-t']) ? $args['-t'] : '');
        $this->option_alias(isset($args['-a']) ? $args['-a'] : null);
        $this->option_namespace(isset($args['-n']) ? $args['-n'] : '');
        $this->option_class(isset($args['-c']) ? $args['-c'] : '');

        $this->namespace = HpGcm::formatNamespace($this->namespace);

        // 通过namespace 新建目录
        $err = HpGcm::mkdirDirByNamespace($this->namespace, $path);
        if ($err !== true) {
            echo StringHelper::commandColor($err, COMMAND_COLOR_RED) . PHP_EOL;
            return;
        }

        // 文件路径
        if ($file = $this->getFileName($path)) {
            $model = new GenerateModel();
            $model->class_name = $this->class_name;
            $model->table = $this->table;
            $model->alias = $this->alias;
            $model->namespace = $this->namespace;
            $model->file = $file;
            $model->run();
        } else {
            echo "略过Model文件" . PHP_EOL;
        }

        fwrite(STDOUT, "是否要生成Controller文件？(Y/N)");
        $input = trim(fgets(STDIN));
        if (strtolower($input) === 'y' || strtolower($input) === 'yes') {
            $this->option_ctrl_namespace(isset($args['-cn']) ? $args['-cn'] : '');
            $this->option_ctrl_classname(isset($args['-cc']) ? $args['-cc'] : '');

            $this->ctrl_namespace = HpGcm::formatNamespace($this->ctrl_namespace);
            // 通过namespace 新建目录
            $err = HpGcm::mkdirDirByNamespace($this->ctrl_namespace, $ctrl_path);
            if ($err !== true) {
                echo StringHelper::commandColor($err, COMMAND_COLOR_RED) . PHP_EOL;
                return;
            }
            if ($file = $this->getCtrlFileName($ctrl_path)) {
                $model = new GenerateController();
                $model->namespace = $this->ctrl_namespace;
                $model->table = $this->table;
                $model->class_name = $this->ctrl_classname;
                $model->file = $file;
                $model->model_class = $this->class_name;
                $model->model_complete_class = $this->namespace . '\\' . $this->class_name;
                $model->run();
            } else {
                echo "略过Model文件" . PHP_EOL;
            }

        } else {
            return;
        }


        echo StringHelper::commandColor('SUCCESS', COMMAND_COLOR_GREEN) . PHP_EOL;
    }


    protected function getFileName($path)
    {
        // 文件路径
        $file = StringHelper::combPath($path, $this->class_name . '.php');
        if (file_exists($file)) {
            // 文件存在需要确认
            fwrite(STDOUT, "文件已经存在，是否要覆盖文件？(Y/N)");
            $input = trim(fgets(STDIN));
            if (strtolower($input) === 'y' || strtolower($input) === 'yes') {
                return $file;
            } else {
                return false;
            }
        }
        return $file;
    }

    protected function getCtrlFileName($path)
    {
        // 文件路径
        $file = StringHelper::combPath($path, $this->ctrl_classname . '.php');
        if (file_exists($file)) {
            // 文件存在需要确认
            fwrite(STDOUT, "文件已经存在，是否要覆盖文件？(Y/N)");
            $input = trim(fgets(STDIN));
            if (strtolower($input) === 'y' || strtolower($input) === 'yes') {
                return $file;
            } else {
                return false;
            }
        }
        return $file;
    }

    protected function option_ctrl_namespace($value)
    {
        if (empty($value)) {
            $this->read_ctrl_namespace();
            return;
        }
        $this->ctrl_namespace = $value;
    }

    protected function read_ctrl_namespace()
    {
        fwrite(STDOUT, "请输入namespace：");
        $input = trim(fgets(STDIN));
        $this->option_ctrl_namespace($input);
    }


    protected function option_ctrl_classname($value)
    {
        if (empty($value)) {
            $this->read_ctrl_classname();
            return;
        }
        $this->ctrl_classname = $value;
    }

    protected function read_ctrl_classname()
    {
        fwrite(STDOUT, "请输入类名：");
        $input = trim(fgets(STDIN));
        $this->option_ctrl_classname($input);
    }


    protected function option_alias($value)
    {
        if (is_null($value)) {
            $this->read_alias();
            return;
        }
        $this->alias = $value;
    }

    protected function read_alias()
    {
        fwrite(STDOUT, "请输入数据表别名(不输入表示不设置别名)：");
        $input = trim(fgets(STDIN));
        $this->option_alias($input);
    }


    /**
     * -c class选项的处理
     * @param $value
     */
    protected function option_class($value)
    {
        if (empty($value)) {
            $this->read_class();
            return;
        }
        $this->class_name = $value;
    }

    protected function read_class()
    {
        fwrite(STDOUT, "请输入类名：");
        $input = trim(fgets(STDIN));
        $this->option_class($input);
    }

    /**
     * -n namespace选项的处理
     * @param $value
     */
    protected function option_namespace($value)
    {
        if (empty($value)) {
            $this->read_namespace();
            return;
        }
        $this->namespace = $value;
    }

    protected function read_namespace()
    {
        fwrite(STDOUT, "请输入namespace：");
        $input = trim(fgets(STDIN));
        $this->option_namespace($input);
    }

    protected function read_table()
    {
        fwrite(STDOUT, "请输入数据表名称或序号：");
        $input = trim(fgets(STDIN));
        $this->option_table($input);
    }


    /**
     * -t table选项的处理
     * @param string $value
     */
    protected function option_table($value)
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            $this->option_list();
            $this->read_table();
            return;
        }
        if (is_numeric($value)) {
            if (isset($this->table_list[$value])) {
                $this->table = $this->table_list[$value];
                return;
            }
            echo StringHelper::commandColor("数据表名称无效或不存在，请输入数据表名称或序号：", COMMAND_COLOR_RED) . PHP_EOL;
            $this->option_list();
            $this->read_table();
            return;
        }
        if (!in_array($value, $this->table_list)) {
            echo StringHelper::commandColor("数据表名称无效或不存在，请输入数据表名称或序号：", COMMAND_COLOR_RED) . PHP_EOL;
            $this->option_list();
            $this->read_table();
            return;
        }
        $this->table = $value;
    }

    /**
     * list 选项的处理
     */
    protected function option_list()
    {
        if (empty($this->table_list)) {
            $this->get_all_table();
        }
        $str = '';
        foreach ($this->table_list as $key => $item) {
            $str .= ' ' . sprintf('%-5s', "({$key})") . " {$item}" . PHP_EOL;
        }
        echo $str;
    }

    /**
     * 获取所有数据表
     */
    protected function get_all_table()
    {
        $query = new QueryFactory();
        $sql = $query->select('*')
            ->from('information_schema.TABLES')
            ->where('table_schema= :ts')
            ->bindValue('ts', ConfigHelper::getDbName());
        if ($this->table) {
            $sql->where('TABLE_NAME like :tn')
                ->bindValue('tn', "%{$this->table}%");
        }

        $result = Jan::$app->db->query($sql);
        $this->table_list = array_column($result, 'TABLE_NAME');
    }

}
