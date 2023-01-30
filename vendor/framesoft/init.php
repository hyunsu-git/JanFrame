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

use jan\basic\Event;
use jan\basic\i18n;
use jan\helper\ConfigHelper;

/**
 * 框架的初始化函数
 * 负责一个请求的生命周期中调度各个模块
 * 这里开始正式执行框架的逻辑
 */
class Init
{
    /**
     * 初始化事件系统,注入程序运行时生命周期事件
     */
    private function initEvent()
    {
        $event = Event::Inst();
        $event->addEvent(Event::STATE_BEGIN, $this, 'onStateBegin');
        $event->addEvent(Event::STATE_INIT, $this, 'onStateInit');
        $event->addEvent(Event::STATE_BEFORE_REQUEST, $this, 'onBeforeRequest');
        $event->addEvent(Event::STATE_INIT_REQUEST, $this, 'onInitRequest');
        $event->addEvent(Event::STATE_PROCESS, $this, 'onProcess');
        $event->addEvent(Event::STATE_AFTER_REQUEST, $this, 'onAfterRequest');
        $event->addEvent(Event::STATE_END, $this, 'onStateEnd');
    }

    /**
     * 框架启动函数
     */
    public function start()
    {
        self::initEvent();
        $event = Event::Inst();
        $event->trigger(Event::STATE_BEGIN);
        $event->trigger(Event::STATE_INIT);
        $event->trigger(Event::STATE_BEFORE_REQUEST);
        $event->trigger(Event::STATE_INIT_REQUEST);
        $event->trigger(Event::STATE_PROCESS);
        $event->trigger(Event::STATE_AFTER_REQUEST);
        $event->trigger(Event::STATE_END);
    }

    /**
     * event: APP_STATE_BEGIN
     *
     * @return void
     * @throws ReflectionException
     * @throws \jan\basic\InvalidConfigException
     * @throws \jan\di\NotInstantiableException
     */
    public function onStateBegin()
    {
        // 错误和异常处理
        $error_handle = Jan::createObject('\jan\basic\ErrorHandle');
        $error_handle->register();
        // 加载配置
        Jan::$config = ConfigHelper::loadConfigFile();
        // 初始化应用
        Jan::$app = Jan::createObject('\jan\basic\Application');
    }

    /**
     * event: APP_STATE_INIT
     *
     * @return void
     * @throws ReflectionException
     * @throws \jan\basic\InvalidConfigException
     * @throws \jan\di\NotInstantiableException
     */
    public function onStateInit()
    {
        $this->loadLanguages();

        $components = Jan::getConfig('components', null);
        if (is_array($components)) {
            // 实例化组件并加入 Jan::$app 变量中
            foreach ($components as $name => $conf) {
                /**
                 * @var $component \jan\basic\Component
                 */
                $component = Jan::createObject($conf, [], is_string($name) ? $name : null, true);
                Jan::$app->$name = $component;
                $component->run();
            }
        }
    }

    /**
     * event: APP_STATE_BEFORE_REQUEST
     */
    public function onBeforeRequest()
    {
        $r = require \jan\basic\Loader::getAlias('@app/libs/routes.php');
        Jan::$app->router->setRoutes($r);
        if (Jan::$app->request->linkI18n) {
            $langs = Jan::$app->request->getHeaders()->get('Accept-Language');
            if (!empty($langs)) {
                if (stripos($langs, ',')) {
                    $langs = explode(',', $langs);
                    i18n::setLang($langs);
                }else{
                    i18n::setLang($langs);
                }
            }
        }
    }

    /**
     * event: APP_STATE_INIT_REQUEST
     */
    public function onInitRequest()
    {
        Jan::$app->router->parseUrl();
    }

    /**
     * event: APP_STATE_PROCESS
     */
    public function onProcess()
    {
        Jan::$app->request->execute();
    }

    /**
     * event: APP_STATE_AFTER_REQUEST
     */
    public function onAfterRequest() {}

    /**
     * event: APP_STATE_END
     *
     * @param int $code
     */
    public function onStateEnd($code = 0)
    {
        if (Jan::$app->response !== null) {
            Jan::$app->response->send();
        }
        if (Jan::$app->log !== null) {
            Jan::$app->log->flush();
        }

        exit($code);
    }

    /**
     * 合并国际化相关的语言配置
     */
    private function loadLanguages()
    {
        $default_lang = Jan::getConfig('language', 'en_us');
        i18n::setLang($default_lang);

        $load = Jan::getConfig('defaultLoadLanguage');
        if (is_array($load)) {
            foreach ($load as $lang => $paths) {
                if (!empty($paths)) {
                    foreach ($paths as $path) {
                        i18n::loadAuto($path,$lang);
                    }
                }
            }
        }
    }
}