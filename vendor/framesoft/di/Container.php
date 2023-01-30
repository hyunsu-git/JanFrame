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

namespace jan\di;

use jan\basic\InvalidConfigException;
use ReflectionClass;
use ReflectionException;

/**
 * 依赖注入容器
 */
class Container
{

    /**
     * @var array 保存设置的类的构造参数
     * @see set()
     */
    private static $definitions = [];

    /**
     * @var array 保存设置的类的属性
     * @see set()
     */
    private static $params = [];

    /**
     * @var array 保存类的反射实例
     */
    private static $reflections = [];

    /**
     * @var array 保存类的依赖数组，即构造函数参数
     */
    private static $dependencies = [];

    /**
     * 在容器中设置类的定义，支持以下方式
     *
     * ```php
     *
     * // 定义接口
     * // 当某个类依赖这个接口，会自动注入对应的实例
     * $container->set('jan\components\request\RequestParserInterface', 'jan\components\request\JsonParser');
     *
     * // 定义类的依赖
     * // 当这个类实例化的时，会自动注入这些依赖
     * $container->set('jan\components\db\Connection', [
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=jan',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8mb4',
     * ]);
     *
     * // 定义类的实例
     * // 当某些类依赖于这个类的时候，会自动注入这个实例，类似于单例的变相实现
     * $container->set('jan\components\db\Connection', new Connection());
     *
     * ```
     *
     * @param string $class      类名
     * @param mixed  $definition 类的定义，可以是全限定类名、变量、或者一个配置数组
     * @param array  $params     类实例化后作为属性传入
     * @return void
     */
    public static function set($class, $definition = [], array $params = [])
    {
        // 保存类的定义
        self::$definitions[$class] = $definition;
        // 保存类的属性
        self::$params[$class] = $params;
    }

    /**
     * 获取某个类的依赖，即获取类的构造函数参数
     * 类的反射对象和依赖参数会进行保存
     *  - 对于可变参数，不做处理，
     *  - 对于有默认值的参数，取默认值
     *  - 对于命名类参数，会被作为 [[\jan\di\Instance]] 的实例保存
     *  - 其它参数解析为null
     *
     * @param string $class 类名
     * @return array 返回类的反射实例和依赖数组
     * @throws NotInstantiableException
     */
    public static function getDependencies($class)
    {
        //如果反射实例数组中存在，则直接返回这个实例和依赖
        if (isset(self::$reflections[$class]) && isset(self::$dependencies[$class])) {
            return [self::$reflections[$class], self::$dependencies[$class]];
        }
        $dependencies = [];

        try {
            //尝试反射对象
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new NotInstantiableException($class, null, 0, $e);
        }

        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $c_param) {
                if (version_compare(PHP_VERSION, '5.6.0', '>=') && $c_param->isVariadic()) {
                    //可变参数不处理
                    break;
                } elseif ($c_param->isDefaultValueAvailable()) {
                    $dependencies[] = $c_param->getDefaultValue();
                } else {
                    $cls = $c_param->getClass();
                    if ($cls === null) {
                        if (version_compare(PHP_VERSION, '7.1.0', '>=')) {
                            $ty = $c_param->getType();
                            if ($ty === null) {
                                $dependencies[] = null;
                            } else {
                                $dependencies[] = self::getNamedTypeDefaultValue($ty->getName());
                            }
                        } else {
                            $dependencies[] = null;
                        }
                    } else {
                        $dependencies[] = Instance::of($cls->getName());
                    }
                }
            }
        }

        self::$reflections[$class] = $reflection;
        self::$dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    /**
     * 获取数据类型的默认值
     *
     * @param $type
     * @return mixed
     */
    public static function getNamedTypeDefaultValue($type)
    {
        switch ($type) {
            case 'int':
                return 0;
            case 'float':
                return 0.0;
            case 'string':
                return '';
            case 'bool':
                return false;
            case 'array':
                return [];
            case 'callable':
                return function () {};
            default:
                return null;
        }
    }

    /**
     * 解析依赖
     *
     * @param $dependencies array 依赖参数的数组
     * @return array 解析后的实参数组
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @throws ReflectionException
     */
    protected static function resolveDependencies(array $dependencies) : array
    {
        foreach ($dependencies as $index => $dependency) {
            if ($dependency instanceof Instance) {
                if ($dependency->name !== null) {
                    $dependencies[$index] = self::get($dependency->name);
                }
            }
        }
        return $dependencies;
    }

    /**
     * 获取类的实例
     *
     * @param string|Instance $class       类名或者 [[\jan\di\Instance]] 实例
     * @param array           $definitions 构造参数数组,构造参数为索引数组,按照顺序传入参数
     * @param array           $params      实例化后作为类的属性
     * @return mixed|object
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @throws ReflectionException
     */
    public static function get($class, array $definitions = [], array $params = [])
    {
        if ($class instanceof Instance) {
            $class = $class->name;
        }

        if (isset(self::$definitions[$class]) && isset(self::$params[$class])) {
            if (is_object(self::$definitions[$class])) {
                $obj = self::$definitions[$class];
                foreach (self::$params[$class] as $name => $value) {
                    $obj->$name = $value;
                }
                return $obj;
            } else if (is_array(self::$definitions[$class])) {
                $definitions = array_merge(self::$definitions[$class], $definitions);
                $params = array_merge(self::$params[$class], $params);
            } else if (is_string(self::$definitions[$class])) {
                $class = self::$definitions[$class];
            } else {
                throw new InvalidConfigException("Invalid definition setting for $class");
            }
        }

        return self::build($class, $definitions, $params);
    }


    /**
     * 创建指定类的实例。
     *
     * @param string $class       类名
     * @param array  $definitions 类的定义，会和解析的依赖合并
     * @param array  $params      类的属性
     * @return object
     * @throws NotInstantiableException|ReflectionException|InvalidConfigException
     */
    protected static function build($class, $definitions, $params)
    {
        /* @var $reflection ReflectionClass */
        list($reflection, $dependencies) = self::getDependencies($class);

        //整合解析出的依赖参数和自定义构造参数,也就是传入依赖的会覆盖自动解析的依赖
        foreach ($definitions as $index => $def) {
            $dependencies[$index] = $def;
        }

        // 这里检查构造函数的参数如果是某个类,则先进行创建
        // 注意这里创建完成后,不要回写到 $dependencies 数组中
        $dependencies = self::resolveDependencies($dependencies);

        // isInstantiable() 方法判断类是否可以实例化
        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }

        //通过反射实例化类
        if (empty($dependencies)) {
            $object = $reflection->newInstance();
        } else {
            $object = $reflection->newInstanceArgs($dependencies);
        }

        if (!empty($params)) {
            $params = self::resolveDependencies($params);
            //将参数作为属性传入
            foreach ($params as $name => $value) {
                $object->$name = $value;
            }
        }

        return $object;
    }
}