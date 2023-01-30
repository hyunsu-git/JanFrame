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

namespace jan\basic;

use Jan;
use jan\helper\StringHelper;

/**
 * 框架引擎运行过程中很重要的一个类
 * 主要保存了脚本运行中动态生成的一级组件实例（不包含组件中设置的组件，比如 `request` 组件中的 `parse` 参数也是需要组件，这种子级组件不会保存在这里）
 *
 * 这个类不需要手动初始化，在框架初始化完成后，将作为 [[Jan::$app]] 属性提供
 *
 * 下面的注释主要用于 IDE 提示，在一次请求过程中，并不一定全部都会实例化或赋值，可能返回的是 null
 * @property integer $id
 * @property string $name
 * @property jan\components\router\Router $router
 * @property jan\components\request\Request $request
 * @property jan\components\response\Response $response
 * @property jan\components\log\ILogRecordBase $log
 * @property jan\components\db\Connection $db
 * @property jan\components\redis\Redis $redis
 * @property jan\components\Snowflake $snowflake
 * @property jan\components\cache\ICache $cache
 * @property jan\components\session\Session $session
 */
class Application extends Component
{
    /**
     * @var integer 进程号
     * 仅在 `WORKER_MODE` 为 `TRUE` 时有效
     */
    public $id;

    /**
     * @var string 进程名称
     * 仅在 `WORKER_MODE` 为 `TRUE` 时有效
     */
    public $name;

    /**
     * @var string 所有输入和输出使用的字符编码
     */
    public $charset = 'UTF-8';

    /**
     * @var string 当前应用的根目录（不带结尾斜线）
     */
    public $appPath;

    /**
     * @var string 当前应用的`libs`目录（不带结尾斜线）
     */
    public $libsPath;

    /**
     * @var string 当前应用的配置目录（不带结尾斜线）
     */
    public $configPath;

    /**
     * @var string 当前应用的`runtime`目录（不带结尾斜线）
     */
    public $runtimePath;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();
        $this->charset = Jan::getConfig('charset', 'UTF-8');
        $this->appPath = APP_PATH;
        $this->configPath = APP_CONF_PATH;
        $this->libsPath = APP_LIBS_PATH;
        $this->runtimePath = StringHelper::combPath($this->appPath, 'runtime');
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
    }

    /**
     * 重写魔术方法 __get
     * 在获取不存在的属性时，不会抛出异常，而是返回 null
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }else{
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * {@inheritdoc}
     */
    public function __unset($name)
    {
        $this->$name = null;
    }
}