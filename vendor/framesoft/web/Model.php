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

use Jan;
use jan\basic\i18n;
use jan\components\request\Request;
use jan\validators\InvalidValidationRule;
use jan\validators\Validator;

/**
 * Class Model
 * 数据模型,主要提供自动赋值,数据校验,场景区分 功能
 *
 * @property string $scenario 场景
 *
 * 验证规则受到场景的限制:
 *  - 可以在 rules() 方法中的每条验证规则中加入 scenario 字段,仅适用于单条验证规则
 *  - 可以在 scenarios() 中设置,适用于所有验证规则
 */
class Model extends ModelBase
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_ROUTE = 'PUT';

    /**
     * @var bool 自动赋值,是否去掉两边的空白
     */
    protected $trimParamsSpace = true;

    /**
     * @var array 保存所有的错误信息
     */
    protected $errors = [];

    /**
     * @var bool 存在多条验证规则,如果前一条规则验证失败,是否跳过后面的验证
     */
    protected $validateSkipError = true;

    /**
     * @return bool
     */
    public function isValidateSkipError()
    {
        return $this->validateSkipError;
    }

    /**
     * @param bool $validateSkipError
     */
    public function setValidateSkipError($validateSkipError)
    {
        $this->validateSkipError = $validateSkipError;
    }

    /**
     * @var string 场景
     *
     * 场景由用户自定义,可以是任意合法字符串
     * 不设置表示不使用场景,则验证规则和自动赋值对所有字段生效
     * 虽然是非public字段,依然可以外部赋值
     * ```php
     * $model = new Model();
     * $model->scenario = 'create';
     * ```
     *
     * @see scenarios()
     */
    private $scenario;

    /**
     * @return string
     */
    public function getScenario()
    {
        return $this->scenario;
    }

    /**
     * @param string $scenario
     */
    public function setScenario($scenario)
    {
        $this->scenario = $scenario;
    }

    /**
     * 设置对于类中属性的验证规则
     *
     * @return array
     *
     * 关于验证规则:
     *  - 返回的必须是数组,每个元素表示一条验证规则
     *  - 每条验证规则必须是一个数组
     * 关于一条验证规则:
     *  - 第一个元素可以是属性名称的字符串或者属性名称的数组
     *  - 第二个元素表示要使用的验证规则,参见 `\jan\validators\Validator::$builtValidators`
     * @see \jan\validators\Validator::$builtValidators
     *  - 第二个元素如果不属于验证规则,则表示自定义验证,类中需要存在同名函数
     *  - 第三个元素必须是数组,针对不同的验证规则的设置
     *  - 可以在第二个元素之后(一般放在最后)任意位置出现`scenario`元素，值是场景字符串或数组,表示这条验证收到场景限制
     *  - 不设置`scenario`元素表示所有场景均生效
     * @see $scenario
     * eg:
     * return [
     *          // 下面表示适用于所有场景
     *
     *          // 必须是邮箱格式
     *          ['user_email','email'],
     *          // 用户名和手机号都必须是字符串,最大11位
     *          [['username','user_mobile'],'string',['max'=>11]],
     *          [['username','user_mobile'],'string',['max'=>11],'scenario'=>['*']],
     *
     *
     *          // 下面表示适用于create场景,单场景的简写方式
     *
     *          // 必须是整数,99,最小1
     *          [['age'],'integer',['max'=>99,min=>1],'scenario'=>'create'],
     *
     *
     *          // 下面表示仅适用于 s1和s2 场景
     *
     *          // 使用自定义验证,直接使用回调函数
     *          [['avatar'],'customer','callback'=>function(){},'scenario'=>['s1','s2']],
     *          // 使用自定义验证,类中需要存在 avatarRule() 方法
     *          [['avatar'],'avatarRule','scenario'=>['s1','s2']],
     *      ]
     */
    public function rules()
    {
        return array();
    }

    /**
     * 获取验证规则,并将验证规则格式化为每个字段单独一条验证规则
     *
     * @return array
     * @throws InvalidValidationRule
     */
    public function getRules()
    {
        $rules = [];
        foreach ($this->rules() as $rule) {

            if (!is_array($rule) || sizeof($rule) < 2) {
                throw new InvalidValidationRule('Validation rule must be an array!');
            }
            if (empty($rule[0]) || (!is_string($rule[0]) && !is_array($rule[0]))) {
                throw new InvalidValidationRule('The first parameter of a validation rule must be an array or string!');
            }
            if (empty($rule[1])) {
                throw new InvalidValidationRule('The second parameter of the validation rule must be a string!');
            }

            if (is_array($rule[0])) {
                foreach ($rule[0] as $field) {
                    $row = $rule;
                    $row[0] = $field;
                    $rules[] = $row;
                }
            } else {
                $rules[] = $rule;
            }
        }
        return $rules;
    }


    /**
     * 对字段进行国际化转换/翻译
     *
     * 可以直接返回一个键值对数组,则对于所有语言均使用该翻译
     * eg: return ['name'=>'姓名']
     * 也可以分为不同语言返回不同数组;根据设置项 `language` 进行匹配,匹配不到则不进行翻译.这种形式表示遇见的键必须是小写
     * eg return [
     *          'zh_cn'=>['name'=>'姓名'],
     *          'japanese'=>['name'=>'めいしょう']
     *      ]
     *
     * @return array
     *
     */
    public function attributeLangs()
    {
        return [];
    }

    /**
     * 获取国际化转换
     * 使用该方法而不是 `attributeLangs()` 方法, 因为可能存在子类可能重写
     *
     * @return array
     */
    public function getAttributeLangs()
    {
        return $this->attributeLangs();
    }


    /**
     * 返回场景对应的字段
     * 适用于
     * 1. 验证规则: 设置场景的情况下,仅验证场景对应的字段
     * 2. 自动赋值: 设置场景的情况下,自动赋值将仅对场景包含的字段生效
     *
     * 设置规则: 数组第一个元素为 '*' 表示取反
     * 以下的字段,仅指类中所有的 public  属性,不包含 static 属性
     *
     * @return array
     *
     * @see attributes()
     *
     * 支持以下几种语法
     * 'scene'=>['*'];              // 表示适用全部字段
     * 'scene'=>'*';                // ['*']写法的快捷方式,表示所有字段
     * 'scene'=>['*','f1','f2'];    // 表示除了 f1,f2 以外的所有字段
     * 'scene'=>['f3','f4'];        // 表示仅适用 f3,f4 字段
     */
    public function scenarios()
    {
        return array();
    }

    /**
     * 获取场景字段
     * 使用该方法,而不是 `scenarios()`, 因为可能存在子类可能重写
     *
     * @return array
     */
    public function getScenarios()
    {
        return $this->scenarios();
    }

    /**
     * 返回类中场景相关的所有的非静态公有属性
     *
     * @param string $scenario
     * @return array
     * @see attributes() 两个方法功能类似
     */
    public function scenarioAttributes($scenario = null)
    {
        if (empty($scenario)) $scenario = $this->scenario;
        if (empty($scenario)) {
            // 未设置场景,返回所有字段
            return $this->attributes();
        }

        $scenarios = $this->getScenarios();
        if (!isset($scenarios[$scenario])) {
            // 未设置场景相关字段
            return [];
        }
        $scenarios = $scenarios[$scenario];
        if ($scenarios === '*') {
            $scenarios = ['*'];
        }
        if ($scenarios[0] === '*') {
            // 表示取反
            unset($scenarios[0]);
            return array_values(array_diff($this->attributes(), $scenarios));
        } else {
            return $scenarios;
        }
    }

    /**
     * 返回类中场景相关的属性值的键值对
     *
     * @param string $scenario
     * @return array
     * @see getAttrValues() 两个方法功能类似
     */
    public function getScenarioAttrValues($scenario = null)
    {
        $values = [];
        $names = $this->scenarioAttributes($scenario);
        foreach ($names as $name) {
            $values[$name] = isset($this->$name) ? $this->$name : null;
        }
        return $values;
    }

    /**
     * 自动参数赋值,该方法解析类中的 public 类型变量,自动使用传入的参数赋值
     *
     * @param string|array $method POST|GET 使用的请求方式，传入post|get|route，不传则使用 [[\jan\components\request\Request::value()]] 函数获取
     * @param array  $source 使用的数据源，传入该参数，$method 参数无效
     * @see Request::value()
     */
    public function autoAssign($method = null, array $source = [])
    {
        $attrs = $this->scenarioAttributes();

        if (empty($source)) {
            switch (strtoupper($method)) {
                case self::METHOD_POST:
                    $source = Jan::$app->request->post();
                    break;
                case self::METHOD_GET:
                    $source = Jan::$app->request->get();
                    break;
                case self::METHOD_ROUTE:
                    $source = Jan::$app->request->param();
                    break;
            }
        }

        foreach ($attrs as $field) {
            $val = null;
            if (empty($source)) {
                $val = Jan::$app->request->value($field);
            }else{
                if (isset($source[$field])) {
                    $val = $source[$field];
                }
            }
            if ($val) {
                if ($this->trimParamsSpace && (is_string($val) || is_numeric($val))) {
                    $this->$field = trim($val);
                } else {
                    $this->$field = $val;
                }
            }
        }
    }

    /**
     * 根据设置的验证规则,对模型进行验证
     *
     * @return bool
     * @see rules()
     */
    public function validate()
    {
        $rules = $this->getRules();
        $scenario_attrs = $this->scenarioAttributes();

        $response = true;
        foreach ($rules as $rule) {
            // 如果设置了场景
            if (isset($rule['scenario'])) {
                $rule_scene = is_array($rule['scenario']) ? $rule['scenario'] : [$rule['scenario']];
                // 不属于当前场景的略过
                if (!in_array($this->scenario, $rule_scene)) {
                    continue;
                } else {
                    unset($rule['scenario']);
                }
            }
            // 如果字段不在相关场景中,略过
            $field = $rule[0];
            if (!in_array($field, $scenario_attrs)) continue;
            $inst = Validator::createValidatorByRule($rule, $this);
            $value = isset($this->$field) ? $this->$field : null;
            if (!$inst->validate($field, $value)) {
                $response = false;
                $this->addError($inst->getError());
                if ($this->validateSkipError) {
                    // 跳过后面的验证
                    break;
                }
            }
        }

        return $response;
    }

    /**
     * 追加错误信息
     *
     * @param string $message
     */
    public function addError($message)
    {
        $message = i18n::t($message);
        $this->errors[] = $message;
        return false;
    }

    /**
     * 追加错误信息
     *
     * @param array|string $errors
     */
    public function addErrors($errors)
    {
        if (is_array($errors)) {
            foreach ($errors as $item) {
                $this->errors[] = $item;
            }
        } else {
            $this->errors[] = $errors;
        }
        return false;
    }

    /**
     * 返回模型是否有错误
     * 包括验证错误和自定义错误
     *
     * @return bool
     */
    public function hasError()
    {
        return !empty($this->errors);
    }

    /**
     * 获取所有错误信息
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * 获取最后一条错误信息
     *
     * @return mixed|null
     */
    public function getLastError()
    {
        $size = count($this->errors);
        if ($size < 1) {
            return null;
        }
        return $this->errors[$size - 1];
    }

    /**
     * 获取第一条错误信息
     *
     * @return mixed|null
     */
    public function getFirstError()
    {
        if (empty($this->errors)) {
            return null;
        } else {
            return $this->errors[0];
        }
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->initLangs();
        parent::init();
    }

    /**
     * 初始化国际化
     */
    protected function initLangs()
    {
        $langs = $this->getAttributeLangs();
        if (is_string(current($langs))) {
            i18n::loadLang($langs);
        } else {
            foreach ($langs as $key => $val) {
                i18n::loadLang($val, $key);
            }
        }
    }

    /**
     * 获取所有设置了验证规则的字段
     *
     * @param $scenario
     * @return array
     * @throws InvalidValidationRule
     */
    public function ruleFields($scenario = null)
    {
        if (empty($scenario)) $scenario = $this->scenario;
        $fields = [];
        $rules = $this->getRules();
        foreach ($rules as $rule) {
            if (isset($rule['scenario']) && $scenario) {
                $sc = is_array($rule['scenario']) ? $rule['scenario'] : [$rule['scenario']];
                if (in_array($scenario, $sc)) {
                    $fields[] = $rule[0];
                }
            } else {
                $fields[] = $rule[0];
            }
        }
        return $fields;
    }

    /**
     * @return mixed
     */
    public function run()
    {

    }
}