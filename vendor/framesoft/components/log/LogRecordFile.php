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

namespace jan\components\log;

use jan\helper\FileHelper;
use jan\helper\StringHelper;

/**
 * 文件日志记录组件
 */
class LogRecordFile extends ILogRecordBase
{
    /**
     * @var string 文件存储路径
     */
    public $path = APP_PATH . DS . 'runtime';

    /**
     * @var string 文件最大字节,超过以后会新建文件
     */
    public $maxSize = '10MB';

    /**
     * @var string 存储文件路径
     */
    private $_file;

    /**
     * @inheritDoc
     */
    protected function write($content, $level)
    {
        if (isset($content['name']) && in_array($content['name'], $this->ignoreException)) {
            return;
        }

        $this->createFile($level);

        $data = $this->formatToFile($content);

        file_put_contents($this->_file, $data, FILE_APPEND);
    }

    /**
     * 将信息格式化成字符串
     * @param $content
     * @return string
     */
    protected function formatToFile($content)
    {
        $data = "\r\n";
        $data .= date("Y-m-d H:i:s", $content['timestamp']);
        $data .= "      =========================>       ";
        $data .= print_r($content, true);
        return $data;
    }

    /**
     * 检查是否存在目录以及,文件是否过大
     * @param int $level
     */
    protected function createFile($level)
    {
        if (!is_dir($this->path)) {
            FileHelper::mkdir($this->path);
        }
        switch ($level) {
            case self::LEVEL_DEBUG:
                $filename = 'debug.log';
                break;
            case self::LEVEL_SUCCESS:
                $filename = 'success.log';
                break;
            case self::LEVEL_INFO:
                $filename = 'notice.log';
                break;
            case self::LEVEL_WARNING:
                $filename = 'warning.log';
                break;
            case self::LEVEL_ERROR:
                $filename = 'error.log';
                break;
            default:
                $filename = 'app.log';
        }
        $this->_file = StringHelper::combPath($this->path, $filename);
        if (is_file($this->_file) && filesize($this->_file) > FileHelper::convertSize($this->maxSize)) {
            $this->renameFile($this->path, $filename);
        }
    }

    /**
     * 对文件重命名
     * @param $dir
     * @param $filename
     */
    protected function renameFile($dir, $filename)
    {
        $ary_fn = explode('.', $filename);
        $new_fn = $ary_fn[0];
        $new_fn .= date("YmdH");
        for ($i = 1; $i < count($ary_fn); $i++) {
            $new_fn .= '.';
            $new_fn .= $ary_fn[$i];
        }
        rename(StringHelper::combPath($dir, $filename), StringHelper::combPath($dir, $new_fn));
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {

    }

    public function run()
    {
    }
}
