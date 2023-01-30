<?php
/**
 * File Name: GenerateController.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/10/20 20:27
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */


namespace jan\command\gcm;


class GenerateController
{
    public $namespace;
    public $table;
    public $class_name;
    public $file;
    public $model_class;
    public $model_complete_class;

    protected $columns = [];
    protected $use_class = [];

    public function run()
    {
        $this->columns = HpGcm::getColumns($this->table);
        $temp = file_get_contents(__DIR__ . '/controller_temp.txt');

        $this->use_class[] = $this->model_complete_class;

        $content = HpGcm::loadTemplate($temp, [
            'classname' => $this->class_name,
            'time' => date('Y-m-d H:i:s'),
            'namespace' => $this->namespace,
            'table_name'=>$this->table,
            'primary' => HpGcm::getPrimaryKey($this->columns),
            'model_class' => $this->model_class,
            'use_class' => GenerateModel::formatUseClass($this->use_class),
        ]);

        file_put_contents($this->file, $content);
    }
}
