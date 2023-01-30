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

namespace models;


use jan\web\Model;

class TestModel extends Model
{
    public $id;

    public $name;

    protected $validateSkipError = true;


    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id'], 'id'],
            [['name'], 'string', ['max' => 10, 'min' => 3]],
        ];
    }

    public function attributeLangs()
    {
        return [
            'name' => '名字'
        ];
    }

    public function run()
    {
        return json_encode([
            'id' => $this->id,
            'name' => $this->name
        ]);
    }
}
