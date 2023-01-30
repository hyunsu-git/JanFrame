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

namespace jan\helper;

use jan\basic\Exception;
use ZipArchive;

/**
 * FileHelper 文件助手类
 */
class FileHelper extends BaseHelper
{
    /**
     * 创建一个目录
     *
     * @param      $_path            string 全路径
     * @param bool $_throw_exception 是否抛出异常
     * @return bool|string true 文件创建成功或者已经存在并且可以写入
     *                               string 文件存在没有权限或者创建失败
     */
    public static function mkdir($_path, $_throw_exception = true)
    {
        if (is_dir($_path)) {
            if (is_writable($_path)) {
                return true;
            } else {
                if ($_throw_exception) {
                    throw new Exception('目录已经存在且没有写入权限');
                } else {
                    return false;
                }
            }
        } else {
            try {
                mkdir($_path, 0777, true);
                return true;
            } catch (\Exception $e) {
                if ($_throw_exception) {
                    throw new Exception($e);
                } else {
                    return false;
                }
            }
        }
    }


    /**
     * 文件大小转换
     *
     * @param        $_size     string|int|double 如果是带有标准的单位 则按当前单位计算，否则，认为传入的是以字节为单位
     * @param string $_new_unit 转换成的 单位，默认是字节
     *
     * @return float|int    转换出的大小（不带单位）
     */
    public static function convertSize($_size, $_new_unit = 'B')
    {
        $_size = strtoupper($_size);

        $size_b = doubleval($_size);

        if (stristr($_size, 'TB') || stristr($_size, 'T')) {

            $s = $size_b * 1024 * 1024 * 1024 * 1024;

        } else if (stristr($_size, 'GB') || stristr($_size, 'G')) {

            $s = $size_b * 1024 * 1024 * 1024;

        } else if (stristr($_size, 'MB') || stristr($_size, 'M')) {

            $s = $size_b * 1024 * 1024;

        } else if (stristr($_size, 'KB') || stristr($_size, 'K')) {

            $s = $size_b * 1024;

        } else {

            $s = $size_b;
        }

        $unit = strtoupper($_new_unit);

        switch ($unit) {

            case 'TB':
            case 'T':
                return $s / (1024 * 1024 * 1024 * 1024);

            case 'GB':
            case 'G':
                return $s / (1024 * 1024 * 1024);

            case 'MB':
            case 'M':
                return $s / (1024 * 1024);

            case 'KB':
            case 'K':
                return $s / 1024;

            default:
                return $s;
        }
    }

    /**
     * 转换成小于1024的合适单位
     *
     * @param $_size string|int 可以带有单位,可以不带(作为字节)
     *
     * @return string 转换结果（带有单位）
     */
    public static function convertSuitableUnit($_size)
    {

        if (is_string($_size)) {
            $_size = self::convertSize($_size);
        }

        if ($_size < 1024) {
            return round($_size, 2) . "B";
        }

        $_size /= 1024;

        if ($_size < 1024) {
            return round($_size, 2) . "KB";
        }

        $_size /= 1024;

        if ($_size < 1024) {
            return round($_size, 2) . "MB";
        }

        $_size /= 1024;

        if ($_size < 1024) {
            return round($_size, 2) . "GB";
        }

        $_size /= 1024;

        return round($_size, 2) . 'TB';
    }


    /**
     * 如果路径是文件，判断文件是否有写权限，如果不是文件，或者文件不存在，判断路径中的目录是否有写权限
     *
     * @param $_path
     *
     * @return bool
     */
    public static function isWritable($_path)
    {

        if (is_file($_path)) {

            if (!is_writable($_path)) {

                return false;
            }
        } else {

            $dir = pathinfo($_path, PATHINFO_DIRNAME);

            if (!is_writable($dir)) {

                return false;
            }
        }
        return true;
    }

    /**
     * 获取文件的 mime 类型
     *
     * @param string $file 文件路径
     * @return string
     */
    public static function getMime($file)
    {
        $finfo = finfo_open(FILEINFO_MIME);

        $mimetype = finfo_file($finfo, $file);

        finfo_close($finfo);

        $ary_mime = explode(';', $mimetype);

        return trim($ary_mime[0]);
    }


    /**
     * 根据提供的常见类型，返回该类型下 ，类型的mime和文件后缀
     *
     * @param $_type string 文件类型,支持的值 image|txt|html|audio|video|zip|rar|xml
     * @return array
     */
    public static function fileMime($_type)
    {

        $all_mime = [];
        $all_extension = [];

        $arr_type = explode('|', $_type);
        foreach ($arr_type as $type) {

            switch ($type) {
                case 'image':
                    $all_mime[] = 'image/gif';
                    $all_mime[] = 'image/jpeg';
                    $all_mime[] = 'image/png';
                    $all_mime[] = 'image/bmp';
                    $all_extension[] = 'gif';
                    $all_extension[] = 'jpg';
                    $all_extension[] = 'png';
                    $all_extension[] = 'bmp';
                    $all_extension[] = 'jpeg';
                    break;

                case 'txt':
                    $all_mime[] = 'text/plain';
                    $all_extension[] = 'txt';
                    break;

                case 'html':
                    $all_mime[] = 'text/html';
                    $all_extension[] = 'html';
                    break;

                case 'xml':
                    $all_mime[] = 'text/xml';
                    $all_extension[] = 'xml';
                    break;

                case 'audio':
                    $all_mime[] = 'audio/mpeg';
                    $all_mime[] = 'audio/wav';
                    $all_extension[] = 'mp3';
                    $all_extension[] = 'wav';
                    break;

                case 'video':
                    $all_mime[] = 'video/avi';
                    $all_mime[] = 'application/vnd.rn-realmedia-vbr';
                    $all_mime[] = 'application/octet-stream';
                    $all_extension[] = 'avi';
                    $all_extension[] = 'rmvb';
                    $all_extension[] = '3gp';
                    $all_extension[] = 'flv';
                    break;

                case 'rar':
                    $all_mime[] = 'application/octet-stream';
                    $all_extension[] = 'rar';
                    break;

                case 'zip':
                    $all_mime[] = 'application/x-zip-compressed';
                    $all_extension[] = 'zip';
                    break;

            }
        }

        return ['mime' => $all_mime, 'extension' => $all_extension];
    }


    /**
     * 压缩文件夹.
     * 使用方式:
     * FileHelper::zipDir('/path/to/sourceDir', '/path/to/out.zip');
     *
     * @param string $source_path  要压缩的目录
     * @param string $out_zip_path 压缩文件存储路径
     */
    public static function zipDir($source_path, $out_zip_path)
    {
        $pathInfo = pathInfo($source_path);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];

        $zip = new ZipArchive();
        $res = $zip->open($out_zip_path, ZIPARCHIVE::OVERWRITE);
        if ($res === ZipArchive::ER_NOENT) {
            @unlink($out_zip_path);
            $res = $zip->open($out_zip_path, ZipArchive::CREATE);
            if ($res !== true) {
                return false;
            }
        }
        $zip->addEmptyDir($dirName);
        self::folderToZip($source_path, $zip, strlen($parentPath . DIRECTORY_SEPARATOR));
        $zip->close();
        return true;
    }

    /**
     * 向压缩文件中添加文件和子目录
     *
     * @param string     $folder           要压缩的目录
     * @param ZipArchive $zip              句柄
     * @param int        $exclusive_length 从路径中排除的字符串长度
     */
    private static function folderToZip($folder, &$zip, $exclusive_length)
    {
        $handle = opendir($folder);
        while (false !== $file = readdir($handle)) {
            if ($file != '.' && $file != '..') {
                $filePath = StringHelper::combPath($folder, $file);
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusive_length);
                if (is_file($filePath)) {
                    $zip->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zip->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zip, $exclusive_length);
                }
            }
        }
        closedir($handle);
    }
}