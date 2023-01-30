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

namespace jan\validators;


/**
 * Class UserValidator
 * 用户自定义验证器
 */
class UserValidator extends Validator
{
    /**
     * @var string 用户自定义的回调函数
     */
    public $callback;

    /**
     * @var string 构造函数传入的配置项
     */
    public $params;


    /**
     * UserValidator constructor.
     * @param string $callback 用户自定义回调函数名
     * @param array $config
     */
    public function __construct($callback, $config = [])
    {
        $this->params = $config;
        unset($this->params['model']);
        unset($this->params['attributes']);
        $this->callback = $callback;
        parent::__construct($config);
    }


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = '{attribute} is an invalid value.';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, $attribute = '')
    {
        if (is_callable($this->callback)) {
            $result = call_user_func($this->callback, $value, $this->params);
        } else{
            if (!$this->model->hasMethod($this->callback)) {
                throw new InvalidValidationRule("Validation rule '{$this->callback}' not found in class " . get_class($this->model));
            }else{
                $result = call_user_func(array($this->model, $this->callback), $value, $this->params);
            }
        }

        if ($result) {
            if ($result === true) {
                return true;
            } else {
                $this->message = $result;
                return false;
            }
        } else {
            return false;
        }
    }
}
