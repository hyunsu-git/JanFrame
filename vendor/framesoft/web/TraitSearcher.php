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

use jan\basic\i18n;
use jan\helper\FileHelper;
use jan\helper\StringHelper;
use jan\validators\SorterValidator;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

/**
 * Trait TraitSearcher
 * 数据查询器扩展
 * 多用于管理后台的数据列表,用户快速处理分页,排序,筛选,导出
 * 使用该 trait ，必须保证类继承自 \jan\web\ActiveRecord
 * 包含的数据导出功能,可以导出Excel,依赖于 `phpoffice/phpspreadsheet` 库
 */
trait TraitSearcher
{
    /**
     * @var int 分页
     */
    public $page = 1;

    /**
     * @var int 每页显示数量
     */
    public $page_size = 10;

    /**
     * @var array 排序规则
     * 格式:['id'=>'asc','name'=>'desc']
     */
    public $sorter = [];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['page', 'page_size'], 'integer', ['min' => 1]],
            [['sorter'], 'sorter', ['mode' => SorterValidator::TYPE_MODE1]]
        ];
    }

    /**
     * 导出文件的存储目录
     * 一般来说,这个目录应该是客户端能访问到的目录,以便可以下载
     * 或者保存到任意位置,单独提供统一下载接口
     *
     * @return string
     */
    protected function exportDir()
    {
        $dir = StringHelper::combPath(WEB_PATH, 'download', 'export');
        FileHelper::mkdir($dir);
        return $dir;
    }

    /**
     * 导出数据
     *
     * 需要注意，如果数据量非常大，并且没有任何筛选条件，将导出全部数据
     * 这会造成内存溢出或者请求超时，需要根据实际情况更改配置
     * 调用该方法，必须安装 `phpoffice/phpspreadsheet` 库
     *
     * @param array  $result      要导出的数据
     * @param string $filename    自定义文件名
     * @param string $writer_type 导出的文件类型
     * @param string $file_suffix 导出的文件后缀,需要和文件类型匹配
     * @return string 返回文件相对于项目根目录的路径
     */
    public function export($result, $filename = null, $writer_type = 'Xlsx', $file_suffix = 'xlsx', $memory_limit = null)
    {
        // 更改内存限制
        if ($memory_limit) {
//            ini_set('memory_limit', '1024M');
            ini_set('memory_limit', $memory_limit);
        }

        // 组合导出的路径
        $dir = $this->exportDir();
        if (empty($filename)) {
            $filename = '导出数据' . '_' . date('YmdHis');
        }
        $file = StringHelper::combPath($dir, $filename . '.' . $file_suffix);

        // 新建excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 这是最多导出26列，如果超过26列，该数组请自行添加后面的列号
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        // 取结果集第一条的键作为列名称
        $fields = array_keys($result[0]);
        // 第一行写入字段名称
        foreach ($fields as $index => $field) {
            $c = $cols[$index];
            $sheet->setCellValue("{$c}1", i18n::t($field));
        }
        foreach ($result as $line => $row) {
            // 从第2行开始填数据
            $line = $line + 2;
            $i = 0;
            foreach ($row as $k => $v) {
                $c = $cols[$i];
                $sheet->setCellValueExplicit("{$c}{$line}", $v, DataType::TYPE_STRING);
                $i++;
            }
        }
        // 回写文件
        $writer = IOFactory::createWriter($spreadsheet, $writer_type);
        $writer->save($file);

        // 返回文件路径
        return str_replace(WEB_PATH, '', $file);
    }
}