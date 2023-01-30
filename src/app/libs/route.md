## 路由组件

`Jan`框架提供了强大的路由功能，可以支持指定规则路由和自动路由两种方式

### 指定规则路由
指定规则路由是指写在 `routes.php` 文件中的路由规则，先看一个简单的例子：

```php
return array(
    '/version'=>function(){
        return '1.0';   
    },
    'GET:/userinfo/:id'=>[UserController::className(),'info'],
    'POST:/user/password/:id<\w+>'=>function(){},
)
```
以上例子展示了`Jan`框架基本的路由配置方式。不同的规则中使用`/`符号来划分层级，层级越深的规则优先级也会越高。

### 路由规则
路由配置整体为一个数组，数组的键名为路由规则，键值为路由处理函数。

指定路由的规则为：
```
[分组:][请求方式:]路由规则[<正则表达式>] => 路由处理程序
```

路由规则中可以使用`/`符号来划分层级，使用 `:` 来做模糊匹配，模糊匹配时可以使用正则表达式来限制匹配的内容。下面是几种路由规则的例子：

```php
return array(
    // 普通路由，精准匹配
    "/user/info"=>function(){},
    // 模糊匹配
    "/user/info/:id"=>function(){},
    // 模糊匹配，使用正则表达式限制id只能为数字
    "/user/info/:id<\d+>"=>function(){},
    // 路由分组
    "Group:/v1"=>array(
        // 实际路由为 /v1/user/info
        "/user/info"=>function(){},
    )
)
```

#### 模糊匹配
模糊匹配是指路由规则中使用 `:` 来做模糊匹配，模糊匹配时可以使用正则表达式来限制匹配的内容。下面是几种路由规则的例子：

| 请求路径             | 路由规则              | 结果  |
|------------------|-------------------|-----|
| /user/123        | /user/:id         | yes |
| /user/tom        | /user/:id         | yes |
| /user/tom        | /user/:id<\d+>    | no  |
| /user/info/tom   | /user/:id         | no  |
| /user            | /user/:id         | no  |
| /user/tom/update | /user/:id/update  | yes |
| /user/tom/update | /user/:id/:action | yes |


#### 使用正则表达式
正则表达式必须使用`<`和`>`包裹，并且需要紧跟在模糊匹配名称后面出现。例如：
```php
return array(
    "/user/info/:id<\d+>"=>function(){},
    // 下面写法无效
    "/user/info/:id <\d+>"=>function(){}, // 不能带空格
    "/user/info/id<\d+>"=>function(){}, 
    "/user/info/<\d+>"=>function(){},
    "/user/info/<\d+>:id"=>function(){},
)
```
对于未限定正则表达式的模糊匹配，实际上隐式包含 `[A-Za-z0-9_]+` 正则表达式

#### 指定请求方式
路由规则中可以指定请求方式，如 `GET:/userinfo/:id` 表示只有`GET`请求才会匹配到这个规则。请求方式共10种，分别是：`GET`,`POST`,`PUT`,`DELETE`,`HEAD`,`OPTIONS`,`PATCH`,`TRACE`,`CONNECT`,`CLI`，请求方式不区分大小写 。`CLI` 表示命令行请求，其它方式均为HTTP请求。 下面是几种路由规则的例子：
```php
return array(
    // 指定请求方式，请求方式不区分大小写
    "GET:/user/info"=>function(){},
    "post:/user/info/:id"=>function(){},
    // 多种请求方式用`|`分隔
    "GET|Post:/user/info"=>function(){},
    // 不带任何请求方式的路由，表示所有请求方式都可以匹配
    "/user/info"=>function(){},
)
```

#### 路由分组
还可以对路由进行分组，同一组的路由规则会共享一个前缀。路由分组可以嵌套，也可以整体指定请求方式。路由分组的规则是以`GROUP:`（不区分大小写）开头，后面跟分组的前缀，如：

```php
return (
    // 路由分组
    "GROUP:/v1"=>array(
        // 实际路由为 /v1/user/info
        "/user/info"=>function(){},
        // 实际路由为 /v1/version
        "/version"=>function(){}
         // 可以嵌套路由分组
        "group:/admin"=>array(
            // 实际路由为 /v1/admin/user/info
            "/user/info"=>function(){},
            // 实际路由为 /v1/admin/version
            "/version"=>function(){}
        )
    )
    // 可以整组指定请求方式
    "GROUP:GET:/v1"=>array(
        // 实际路由为 GET:/v1/user/info
        "/user/info"=>function(){},
        // 实际路由为 GET:/v1/version
        "/version"=>function(){}
        // 子组指定的请求方式，优先级更高
        "group:Post:/admin"=>array(
            // 实际路由为 post:/v1/admin/user/info
            "/user/info"=>function(){},
            // 实际路由为 post:/v1/admin/version
            "/version"=>function(){}
        )
        // 子组未指定请求方式，使用父组的请求方式
        "group:/app"=>array(
            // 实际路由为 GET:/v1/app/user/info
            "/user/info"=>function(){},
            // 实际路由为 GET:/v1/app/version
            "/version"=>function(){}
        )
    )
)
```

#### 优先级控制
优先级控制按照深度优先策略，主要的几点因素：

**1. 层级越深的规则优先级越高；例如：`/a/b/:name` 优先级高于 `/a/b`**

**2. 层级相同的情况下，精准匹配高于模糊匹配；例如：`/a/b/c` 优先级高于 `/a/b/:name`**

**3. 其它条件相同的情况下，更少的请求方式优先级高于更多的请求方式；例如：`GET:/a/b` 优先级高于 `GET|POST:/a/b` 高于 `/a/b`**

**4. 在上述条件无法区分优先级时，按照用户书写前后顺序区分**

#### 关于路由覆盖
如果出现相同的路由规则，则第二个会覆盖第一个，例如：

```php
// 下面会产生相同的路由，第二个会覆盖第一个
return array(
    '/v1/version'=>function(){
        return '1.0';   
    },
    'group:/1'=>array(
        '/version'=>function(){
            return '2.0';   
        },
    ),
)
```


### 路由处理程序

路由处理程序可以使用 `字符串`，`数组`，`匿名回调函数`

#### 字符串

对于字符串，会直接作为结果返回到客户端。 **注意：如果`response`组件对返回结果进行了统一处理，返回内容可能不同**。例如
```php
return array(
    '/version'=> "1.0.0",
)
```

#### 匿名回调函数

对于匿名回调函数，会直接执行，返回结果作为响应内容返回到客户端。例如：
```php
return array(
    '/version'=> function(){
        return "1.0.0";
    },
)
```

#### 数组

对于数组，支持一个元素或者两个元素，更过的元素会被忽略。第一个元素表示类名，第二个元素表示方法名。例如：
```php
return array(
    '/version'=> array('app\controller\CommonController','getVersion'),
)
```
只有一个元素的情况比较特殊，路由组件会通过反射获取类中所有已`action`开头的`public`方法，然后将路由规则和方法名拼接成新的路由规则。关于 `action` 方法的使用，参考自动路由部分。
但是如果同级已经存在相同的路由规则，这个类方法会被忽略 。例如：
```php

// app\controller\CommonController.php
class CommonController{
    public function actionVersion(){
        return "1.0.0";
    }
    public function actionGetAppName(){
        return "app name";
    }
    public function actionGetId(){
        return "id";
    }
}

// libs/routes.php
return array(
    'GET:/common/id'=>function(){}
    '/common'=> array('app\controller\CommonController'),
)

// 实际路由为
return array(
    'GET:/common/id'=>function(){}
    '/common/version'=> array('app\controller\CommonController','actionVersion'),
    'GET:/common/appName'=> array('app\controller\CommonController','actionGetAppName'),
    // 这个不会生成，因为已经存在相同的路由规则
    // 'GET:/common/id'=> array('app\controller\CommonController','actionGetId'),
)

```


### 自动路由

自动路由是指不需要指定路由规则，路由组件根据当前请求的 url 自动解析到对应的类和方法。

自动路由最多支持3层解析，分别对应 `module`，`controller`，`action` 三个部分。

路由的路径层数不同，对应的解析方式也不同。
1. 没有路径时，`module` 为空，`controller` 为 `index`，`action` 为 `index`
2. 有一个路径时，`module` 为空，`controller` 为路径，`action` 为 `index`
3. 有两个路径时，`module` 为空，`controller` 为第一个路径，`action` 为第二个路径
4. 有三个路径时，`module` 为第一个路径，`controller` 为第二个路径，`action` 为第三个路径

以项目名称为 `app` 为例：

```
http://127.0.0.1
会被解析到 app\controllers\IndexController::actionIndex

http://127.0.0.1/user
会被解析到 app\controllers\UserController::actionIndex

http://127.0.0.1/user/info
会被解析到 app\controllers\UserController::actionInfo

http://127.0.0.1/v1/user/index
会被解析到 app\module\v1\controllers\UserController::actionIndex
```

#### 自动路由限制

要使用自动路由，必须按照一定的规则组织文件目录，书写类名与方法名。包括：

**1.对于controller，文件名和类名需要保持一致，并且以 `Controller` 结尾，例如 `UserController`，`IndexController`，对应的文件名为 `UserController.php`，`IndexController.php`。**

**2.对于类中的方法，必须以 `action` 开头，例如 `actionInfo`，`actionGetUserInfo`。**

**3.module必须放在 `app\modules\名称` 目录下，并且目录下需要存在单独的 `controllers` 目录**

#### 通过 action 方法名控制请求方式

对于自动路由，可以通过 `action` 方法名控制请求方式。规则是：
```
action[请求方式][方法名]
```
其中action这几个字母为小写，请求方式部分首字母大写，其它字母小写，方法名部分首字母需要大写，其它部分不做限制。例如：
```php

// app\controller\UserController.php
class UserController{
    // 会相应所有请求方式的 /user/info 
    public function actionInfo(){
        return "user info";
    }
    // 只会响应 POST 请求方式的 /user/getUserInfo
    public function actionPostUserInfo(){
        return "post user info";
    }
    // 只会响应 GET 请求方式的 /user/getUserInfo
    public function actionGetUserInfo(){
        return "get user info";
    }
}
```

一个方法只能相应一种请求方式或者全部请求方式，如果希望限定某几种请求，就只能写多个方法。

但是类中可能同时存在两个相应方法，限定了请求方式的优先。例如：
```php
// 对于Get请求 /user/info,会响应 `actionGetInfo` 方法，
// 对于其它请求方式 /user/info,会响应 `actionInfo` 方法。

class UserController{
    public function actionInfo(){
        return "user info";
    }
    public function actionGetInfo(){
        return "get user info";
    }
}
```

