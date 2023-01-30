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

namespace jan\components\cache;

use Jan;
use jan\basic\Loader;
use jan\helper\FileHelper;
use jan\helper\StringHelper;

/**
 * 使用文件缓存 将设置的缓存以键为单独的文件进行存放
 */
class FileCache extends CacheBase
{
    /**
     * 缓存文件所在的目录,可以使用别名路径
     * @var string
     */
    public $cachePath = '@runtime/cache';

    /**
     * @var string 缓存文件后缀
     */
    public $fileSuffix = '.bin';

    /**
     * @var int 新建的缓存文件权限
     * 将作为 chmod() 的参数,默认debug模式下为 0777,非debug模式不设置
     */
    public $fileMode = 0777;

    /**
     * @var int 执行垃圾回收（GC）的概率（百万分之几）
     * 在缓存中存储一段数据时。默认为1000，表示0.1%的几率。
     * 此数字应介于0和1000000之间。值0表示根本不执行GC。
     * 减小该值能提高性能,但是会造成无效文件过多.
     */
    public $gcProbability = 1000;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();
        $this->cachePath = Loader::getAlias($this->cachePath);
        FileHelper::mkdir($this->cachePath);
    }

    /**
     * @inheritDoc
     * 将给定的key转成标准的key
     * 小于2位也进行md5摘要
     * 返回值不携带前缀
     * @param $key
     * @return string
     */
    public function buildKey($key)
    {
        if (is_string($key)) {
            if (!ctype_alnum($key) || strlen($key) >= 32 || strlen($key) < 2) {
                $key = md5($key);
            }
        } else {
            $key = md5(serialize($key));
        }

        return $key;
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        if(!$this->enableCache()) return null;

        try {
            $cache_file = $this->getCacheFile($this->buildKey($key));

            if (is_file($cache_file) && filemtime($cache_file) > time()) {
                $fp = fopen($cache_file, 'r');
                if ($fp !== false) {
                    flock($fp, LOCK_SH);
                    $cache_value = @stream_get_contents($fp);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    return $this->unserializeValue($cache_value);
                }
            }

            return null;

        } catch (\Exception $exception) {
            if (ENV_DEBUG) throw $exception;
            Jan::$app->log->warning($exception);
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $duration = 0)
    {
        if(!$this->enableCache()) return false;
        $this->gc();
        $cache_file = $this->getCacheFile($this->buildKey($key));
        try {
            if (is_file($cache_file) && function_exists('posix_geteuid') && fileowner($cache_file) !== posix_geteuid()) {
                @unlink($cache_file);
            }
            file_put_contents($cache_file, $this->serializeValue($value), LOCK_EX);
            if ($this->fileMode !== null) {
                chmod($cache_file, $this->fileMode);
                if ($duration <= 0) {
                    $duration = 31536000;
                }
                return touch($cache_file, $duration + time());
            }
            return true;
        } catch (\Exception $exception) {
            if (ENV_DEBUG) throw $exception;
            Jan::$app->log->warning($exception);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function exists($key)
    {
        if(!$this->enableCache()) return false;

        $cache_file = $this->getCacheFile($this->buildKey($key));

        return is_file($cache_file) && @filemtime($cache_file) > time();
    }

    /**
     * @inheritDoc
     */
    public function add($key, $value, $duration = 0)
    {
        if(!$this->enableCache()) return false;

        $cache_file = $this->getCacheFile($this->buildKey($key));
        if (@filemtime($cache_file) > time()) {
            return false;
        }
        return $this->set($key, $value, $duration);
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        if(!$this->enableCache()) return false;

        $cacheFile = $this->getCacheFile($this->buildKey($key));

        return @unlink($cacheFile);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        if(!$this->enableCache()) return;

        $this->gc(true, false);
    }


    /**
     * 获取 key 对应的缓存文件
     * @param string $key 经过[[buildKey()]]序列化后的 key
     * @return string 文件路径
     */
    protected function getCacheFile($key)
    {
        // 在ext3文件系统下,默认一级子目录的个数为31998个.另外,不建议在一个目录下有太多的文件或者目录，这会降低文件系统索引文件或目录的性能。
        // 建立前缀目录
        $parent_path = $this->cachePath;
        if ($this->keyPrefix) {
            $parent_path = $parent_path . DIRECTORY_SEPARATOR . $this->keyPrefix;
            FileHelper::mkdir($parent_path);
        }
        // 取键值的前2,建立目录,然后存放信息
        $bit2 = substr($key, 0, 2);
        $dir = $parent_path . DIRECTORY_SEPARATOR . $bit2;
        FileHelper::mkdir($dir);
        return StringHelper::combPath($dir, $key . $this->fileSuffix);
    }


    /**
     * 垃圾回收器,删除无用的缓存文件
     * @param bool $force 是否强制垃圾回收,而不考虑[[gcProbability]].默认false,也就是概率性执行
     * @param bool $only_expired 是否只删除过期缓存文件
     */
    public function gc($force = false, $only_expired = true)
    {
        if ($force || mt_rand(0, 1000000) < $this->gcProbability) {
            $this->gcRecursive($this->cachePath, $only_expired);
        }
    }


    /**
     * 递归删除目录下的缓存文件
     * @param string $path 缓存所在目录
     * @param bool $only_expired 是否只删除过期缓存文件
     */
    protected function gcRecursive($path, $only_expired)
    {
        try {
            if (($handle = opendir($path)) !== false) {
                while (($file = readdir($handle)) !== false) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $full_path = $path . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($full_path)) {
                        $this->gcRecursive($full_path, $only_expired);
                        if (!$only_expired) {
                            rmdir($full_path);
                        }
                    } elseif (!$only_expired || ($only_expired && @filemtime($full_path) < time())) {
                        unlink($full_path);
                    }
                }
                closedir($handle);
            }
        } catch (\Exception $exception) {
            if(ENV_DEBUG) throw $exception;
            Jan::$app->log->warning($exception);
        }
    }
}