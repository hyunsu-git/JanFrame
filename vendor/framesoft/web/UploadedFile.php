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


use jan\basic\Component;
use jan\helper\FileHelper;

/**
 * Class UploadedFile
 * 每个类的实例都表示一个上传的文件
 *
 * @property string $baseName
 * @property string $extension
 */
class UploadedFile extends Component
{
    /**
     * @var string 上传文件的原始名称
     */
    public $name;

    /**
     * @var string 上传文件存在服务器上的临时文件名
     */
    public $tempName;

    /**
     * @var string 上传文件的MIME类型
     */
    public $type;

    /**
     * @var int 上传文件的大小,单位字节
     */
    public $size;

    /**
     * @var int 上传文件的错误代码
     */
    public $error;

    /**
     * @var string 文件的MIME类型
     * 正常情况下和 type 的值一致
     * 遇到强制更改后缀的文件,和type会不一致
     * 应该以该参数为主
     */
    public $mime;

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->error === 0) {
            $this->mime = FileHelper::getMime($this->tempName);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
    }

    /**
     * 判断文件是否是图片
     * @return bool
     */
    public function isImage()
    {
        $image_mime = FileHelper::fileMime('image');
        return in_array($this->extension, $image_mime['extension']) &&
            in_array($this->mime, $image_mime['mime']);
    }

    /**
     * 保存上传的文件
     * @param string $file 文件的保存路径
     * @param bool $deleteTempFile 是否删除临时文件
     * @return bool 是否保存成功
     */
    public function saveAs($file, $deleteTempFile = true)
    {
        if ($this->error == UPLOAD_ERR_OK) {
            if ($deleteTempFile) {
                return move_uploaded_file($this->tempName, $file);
            } elseif (is_uploaded_file($this->tempName)) {
                return copy($this->tempName, $file);
            }
        }

        return false;
    }

    /**
     * 对 jpg,png,bmp 的图片安全的保存,对其他文件常规保存
     * @param string $file 文件的保存路径
     * @param bool $deleteTempFile 是否删除临时文件
     */
    public function safeSaveAs($file, $deleteTempFile = true)
    {
        switch ($this->mime) {
            case 'image/jpeg':
                $img = imagecreatefromjpeg($this->tempName);
                imagejpeg($img, $file, 100);
                imagedestroy($img);
                if ($deleteTempFile) {
                    @unlink($this->tempName);
                }
                break;
            case 'image/png':
                $img = imagecreatefrompng($this->tempName);
                /**
                 * 保存png图片必须保留完整的 alpha 通道信息。
                 * 修复了保存透明背景的png图片会出现杂色的问题
                 */
                imagesavealpha($img, true);
                imagepng($img, $file, 0);
                imagedestroy($img);
                if ($deleteTempFile) {
                    @unlink($this->tempName);
                }
                break;
            case 'image/bmp':   // bmp文件需要7.2以上版本才支持
                if (version_compare(PHP_VERSION, "7.2.0", ">=")) {
                    $img = imagecreatefrombmp($this->tempName);
                    imagebmp($img, $file);
                    imagedestroy($img);
                    if ($deleteTempFile) {
                        @unlink($this->tempName);
                    }
                }else{
                    $this->saveAs($file, $deleteTempFile);
                }
                break;
            default:
                $this->saveAs($file, $deleteTempFile);
        }
    }

    /**
     * 获取源文件名称的 base name
     * @return string
     */
    public function getBaseName()
    {
        $pathInfo = pathinfo('_' . $this->name, PATHINFO_FILENAME);
        return mb_substr($pathInfo, 1, mb_strlen($pathInfo, '8bit'), '8bit');
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    public function getExtension()
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    /**
     * 上传文件是否有错误
     * @return bool
     */
    public function getHasError()
    {
        return $this->error != UPLOAD_ERR_OK;
    }
}
