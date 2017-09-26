<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/**
 * Think 系统函数库123
 */

/**
 * 获取和设置配置参数 支持批量定义
 * @param string|array $name 配置变量
 * @param mixed $value 配置值
 * @param mixed $default 默认值
 * @return mixed
 */
header('Content-Type:text/html;Charset=utf-8');
function C($name = null, $value = null, $default = null)
{
    static $_config = array();
    // 无参数时获取所有
    if (empty($name)) {
        return $_config;
    }
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtoupper($name);
            if (is_null($value))
                return isset($_config[$name]) ? $_config[$name] : $default;
            $_config[$name] = $value;
            return null;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0] = strtoupper($name[0]);
        if (is_null($value))
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
        $_config[$name[0]][$name[1]] = $value;
        return null;
    }
    // 批量设置
    if (is_array($name)) {
        $_config = array_merge($_config, array_change_key_case($name, CASE_UPPER));
        return null;
    }
    return null; // 避免非法参数
}

/**
 * 加载配置文件 支持格式转换 仅支持一级配置
 * @param string $file 配置文件名
 * @param string $parse 配置解析方法 有些格式需要用户自己解析
 * @return array
 */
function load_config($file, $parse = CONF_PARSE)
{
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    switch ($ext) {
        case 'php':
            return include $file;
        case 'ini':
            return parse_ini_file($file);
        case 'yaml':
            return yaml_parse_file($file);
        case 'xml':
            return (array)simplexml_load_file($file);
        case 'json':
            return json_decode(file_get_contents($file), true);
        default:
            if (function_exists($parse)) {
                return $parse($file);
            } else {
                E(L('_NOT_SUPPORT_') . ':' . $ext);
            }
    }
}

/**
 * 解析yaml文件返回一个数组
 * @param string $file 配置文件名
 * @return array
 */
if (!function_exists('yaml_parse_file')) {
    function yaml_parse_file($file)
    {
        vendor('spyc.Spyc');
        return Spyc::YAMLLoad($file);
    }
}

/**
 * 抛出异常处理
 * @param string $msg 异常消息
 * @param integer $code 异常代码 默认为0
 * @throws Think\Exception
 * @return void
 */
function E($msg, $code = 0)
{
    throw new Think\Exception($msg, $code);
}

/**
 * 记录和统计时间（微秒）和内存使用情况
 * 使用方法:
 * <code>
 * G('begin'); // 记录开始标记位
 * // ... 区间运行代码
 * G('end'); // 记录结束标签位
 * echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
 * echo G('begin','end','m'); // 统计区间内存使用情况
 * 如果end标记位没有定义，则会自动以当前作为标记位
 * 其中统计内存使用需要 MEMORY_LIMIT_ON 常量为true才有效
 * </code>
 * @param string $start 开始标签
 * @param string $end 结束标签
 * @param integer|string $dec 小数位或者m
 * @return mixed
 */
function G($start, $end = '', $dec = 4)
{
    static $_info = array();
    static $_mem = array();
    if (is_float($end)) { // 记录时间
        $_info[$start] = $end;
    } elseif (!empty($end)) { // 统计时间和内存使用
        if (!isset($_info[$end])) $_info[$end] = microtime(TRUE);
        if (MEMORY_LIMIT_ON && $dec == 'm') {
            if (!isset($_mem[$end])) $_mem[$end] = memory_get_usage();
            return number_format(($_mem[$end] - $_mem[$start]) / 1024);
        } else {
            return number_format(($_info[$end] - $_info[$start]), $dec);
        }

    } else { // 记录时间和内存使用
        $_info[$start] = microtime(TRUE);
        if (MEMORY_LIMIT_ON) $_mem[$start] = memory_get_usage();
    }
    return null;
}

/**
 * 获取和设置语言定义(不区分大小写)
 * @param string|array $name 语言变量
 * @param mixed $value 语言值或者变量
 * @return mixed
 */
function L($name = null, $value = null)
{
    static $_lang = array();
    // 空参数返回所有定义
    if (empty($name))
        return $_lang;
    // 判断语言获取(或设置)
    // 若不存在,直接返回全大写$name
    if (is_string($name)) {
        $name = strtoupper($name);
        if (is_null($value)) {
            return isset($_lang[$name]) ? $_lang[$name] : $name;
        } elseif (is_array($value)) {
            // 支持变量
            $replace = array_keys($value);
            foreach ($replace as &$v) {
                $v = '{$' . $v . '}';
            }
            return str_replace($replace, $value, isset($_lang[$name]) ? $_lang[$name] : $name);
        }
        $_lang[$name] = $value; // 语言定义
        return null;
    }
    // 批量定义
    if (is_array($name))
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_UPPER));
    return null;
}

/**
 * 添加和获取页面Trace记录
 * @param string $value 变量
 * @param string $label 标签
 * @param string $level 日志级别
 * @param boolean $record 是否记录日志
 * @return void|array
 */
function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
{
    return Think\Think::trace($value, $label, $level, $record);
}

/**
 * 编译文件
 * @param string $filename 文件名
 * @return string
 */
function compile($filename)
{
    $content = php_strip_whitespace($filename);
    $content = trim(substr($content, 5));
    // 替换预编译指令
    $content = preg_replace('/\/\/\[RUNTIME\](.*?)\/\/\[\/RUNTIME\]/s', '', $content);
    if (0 === strpos($content, 'namespace')) {
        $content = preg_replace('/namespace\s(.*?);/', 'namespace \\1{', $content, 1);
    } else {
        $content = 'namespace {' . $content;
    }
    if ('?>' == substr($content, -2))
        $content = substr($content, 0, -2);
    return $content . '}';
}

/**
 * 获取模版文件 格式 资源://模块@主题/控制器/操作
 * @param string $template 模版资源地址
 * @param string $layer 视图层（目录）名称
 * @return string
 */
function T($template = '', $layer = '')
{

    // 解析模版资源地址
    if (false === strpos($template, '://')) {
        $template = 'http://' . str_replace(':', '/', $template);
    }
    $info = parse_url($template);
    $file = $info['host'] . (isset($info['path']) ? $info['path'] : '');
    $module = isset($info['user']) ? $info['user'] . '/' : MODULE_NAME . '/';
    $extend = $info['scheme'];
    $layer = $layer ? $layer : C('DEFAULT_V_LAYER');

    // 获取当前主题的模版路径
    $auto = C('AUTOLOAD_NAMESPACE');
    if ($auto && isset($auto[$extend])) { // 扩展资源
        $baseUrl = $auto[$extend] . $module . $layer . '/';
    } elseif (C('VIEW_PATH')) {
        // 改变模块视图目录
        $baseUrl = C('VIEW_PATH');
    } elseif (defined('TMPL_PATH')) {
        // 指定全局视图目录
        $baseUrl = TMPL_PATH . $module;
    } else {
        $baseUrl = APP_PATH . $module . $layer . '/';
    }

    // 获取主题
    $theme = substr_count($file, '/') < 2 ? C('DEFAULT_THEME') : '';

    // 分析模板文件规则
    $depr = C('TMPL_FILE_DEPR');
    if ('' == $file) {
        // 如果模板文件名为空 按照默认规则定位
        $file = CONTROLLER_NAME . $depr . ACTION_NAME;
    } elseif (false === strpos($file, '/')) {
        $file = CONTROLLER_NAME . $depr . $file;
    } elseif ('/' != $depr) {
        $file = substr_count($file, '/') > 1 ? substr_replace($file, $depr, strrpos($file, '/'), 1) : str_replace('/', $depr, $file);
    }
    return $baseUrl . ($theme ? $theme . '/' : '') . $file . C('TMPL_TEMPLATE_SUFFIX');
}

/**
 * 获取输入参数 支持过滤和默认值
 * 使用方法:
 * <code>
 * I('id',0); 获取id参数 自动判断get或者post
 * I('post.name','','htmlspecialchars'); 获取$_POST['name']
 * I('get.'); 获取$_GET
 * </code>
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法
 * @param mixed $datas 要获取的额外数据源
 * @return mixed
 */
function I($name, $default = '', $filter = null, $datas = null)
{
    static $_PUT = null;
    if (strpos($name, '/')) { // 指定修饰符
        list($name, $type) = explode('/', $name, 2);
    } elseif (C('VAR_AUTO_STRING')) { // 默认强制转换为字符串
        $type = 's';
    }
    if (strpos($name, '.')) { // 指定参数来源
        list($method, $name) = explode('.', $name, 2);
    } else { // 默认为自动判断
        $method = 'param';
    }
    switch (strtolower($method)) {
        case 'get'     :
            $input =& $_GET;
            break;
        case 'post'    :
            $input =& $_POST;
            break;
        case 'put'     :
            if (is_null($_PUT)) {
                parse_str(file_get_contents('php://input'), $_PUT);
            }
            $input = $_PUT;
            break;
        case 'param'   :
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $input = $_POST;
                    break;
                case 'PUT':
                    if (is_null($_PUT)) {
                        parse_str(file_get_contents('php://input'), $_PUT);
                    }
                    $input = $_PUT;
                    break;
                default:
                    $input = $_GET;
            }
            break;
        case 'path'    :
            $input = array();
            if (!empty($_SERVER['PATH_INFO'])) {
                $depr = C('URL_PATHINFO_DEPR');
                $input = explode($depr, trim($_SERVER['PATH_INFO'], $depr));
            }
            break;
        case 'request' :
            $input =& $_REQUEST;
            break;
        case 'session' :
            $input =& $_SESSION;
            break;
        case 'cookie'  :
            $input =& $_COOKIE;
            break;
        case 'server'  :
            $input =& $_SERVER;
            break;
        case 'globals' :
            $input =& $GLOBALS;
            break;
        case 'data'    :
            $input =& $datas;
            break;
        default:
            return null;
    }
    if ('' == $name) { // 获取全部变量
        $data = $input;
        $filters = isset($filter) ? $filter : C('DEFAULT_FILTER');
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            }
            foreach ($filters as $filter) {
                $data = array_map_recursive($filter, $data); // 参数过滤
            }
        }
    } elseif (isset($input[$name])) { // 取值操作
        $data = $input[$name];
        $filters = isset($filter) ? $filter : C('DEFAULT_FILTER');
        if ($filters) {
            if (is_string($filters)) {
                if (0 === strpos($filters, '/')) {
                    if (1 !== preg_match($filters, (string)$data)) {
                        // 支持正则验证
                        return isset($default) ? $default : null;
                    }
                } else {
                    $filters = explode(',', $filters);
                }
            } elseif (is_int($filters)) {
                $filters = array($filters);
            }

            if (is_array($filters)) {
                foreach ($filters as $filter) {
                    if (function_exists($filter)) {
                        $data = is_array($data) ? array_map_recursive($filter, $data) : $filter($data); // 参数过滤
                    } else {
                        $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                        if (false === $data) {
                            return isset($default) ? $default : null;
                        }
                    }
                }
            }
        }
        if (!empty($type)) {
            switch (strtolower($type)) {
                case 'a':    // 数组
                    $data = (array)$data;
                    break;
                case 'd':    // 数字
                    $data = (int)$data;
                    break;
                case 'f':    // 浮点
                    $data = (float)$data;
                    break;
                case 'b':    // 布尔
                    $data = (boolean)$data;
                    break;
                case 's':   // 字符串
                default:
                    $data = (string)$data;
            }
        }
    } else { // 变量默认值
        $data = isset($default) ? $default : null;
    }
    is_array($data) && array_walk_recursive($data, 'think_filter');
    return $data;
}

function array_map_recursive($filter, $data)
{
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
            ? array_map_recursive($filter, $val)
            : call_user_func($filter, $val);
    }
    return $result;
}

/**
 * 设置和获取统计数据
 * 使用方法:
 * <code>
 * N('db',1); // 记录数据库操作次数
 * N('read',1); // 记录读取次数
 * echo N('db'); // 获取当前页面数据库的所有操作次数
 * echo N('read'); // 获取当前页面读取次数
 * </code>
 * @param string $key 标识位置
 * @param integer $step 步进值
 * @param boolean $save 是否保存结果
 * @return mixed
 */
function N($key, $step = 0, $save = false)
{
    static $_num = array();
    if (!isset($_num[$key])) {
        $_num[$key] = (false !== $save) ? S('N_' . $key) : 0;
    }
    if (empty($step)) {
        return $_num[$key];
    } else {
        $_num[$key] = $_num[$key] + (int)$step;
    }
    if (false !== $save) { // 保存结果
        S('N_' . $key, $_num[$key], $save);
    }
    return null;
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name, $type = 0)
{
    if ($type) {
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name));
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

/**
 * 优化的require_once
 * @param string $filename 文件地址
 * @return boolean
 */
function require_cache($filename)
{
    static $_importFiles = array();
    if (!isset($_importFiles[$filename])) {
        if (file_exists_case($filename)) {
            require $filename;
            $_importFiles[$filename] = true;
        } else {
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}

/**
 * 区分大小写的文件存在判断
 * @param string $filename 文件地址
 * @return boolean
 */
function file_exists_case($filename)
{
    if (is_file($filename)) {
        if (IS_WIN && APP_DEBUG) {
            if (basename(realpath($filename)) != basename($filename))
                return false;
        }
        return true;
    }
    return false;
}

/**
 * 导入所需的类库 同java的Import 本函数有缓存功能
 * @param string $class 类库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $ext 导入的文件扩展名
 * @return boolean
 */
function import($class, $baseUrl = '', $ext = EXT)
{
    static $_file = array();
    $class = str_replace(array('.', '#'), array('/', '.'), $class);
    if (isset($_file[$class . $baseUrl]))
        return true;
    else
        $_file[$class . $baseUrl] = true;
    $class_strut = explode('/', $class);
    if (empty($baseUrl)) {
        if ('@' == $class_strut[0] || MODULE_NAME == $class_strut[0]) {
            //加载当前模块的类库
            $baseUrl = MODULE_PATH;
            $class = substr_replace($class, '', 0, strlen($class_strut[0]) + 1);
        } elseif ('Common' == $class_strut[0]) {
            //加载公共模块的类库
            $baseUrl = COMMON_PATH;
            $class = substr($class, 7);
        } elseif (in_array($class_strut[0], array('Think', 'Org', 'Behavior', 'Com', 'Vendor')) || is_dir(LIB_PATH . $class_strut[0])) {
            // 系统类库包和第三方类库包
            $baseUrl = LIB_PATH;
        } else { // 加载其他模块的类库
            $baseUrl = APP_PATH;
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
    $classfile = $baseUrl . $class . $ext;
    if (!class_exists(basename($class), false)) {
        // 如果类不存在 则导入类库文件
        return require_cache($classfile);
    }
    return null;
}

/**
 * 基于命名空间方式导入函数库
 * load('@.Util.Array')
 * @param string $name 函数库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $ext 导入的文件扩展名
 * @return void
 */
function load($name, $baseUrl = '', $ext = '.php')
{
    $name = str_replace(array('.', '#'), array('/', '.'), $name);
    if (empty($baseUrl)) {
        if (0 === strpos($name, '@/')) {//加载当前模块函数库
            $baseUrl = MODULE_PATH . 'Common/';
            $name = substr($name, 2);
        } else { //加载其他模块函数库
            $array = explode('/', $name);
            $baseUrl = APP_PATH . array_shift($array) . '/Common/';
            $name = implode('/', $array);
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
    require_cache($baseUrl . $name . $ext);
}

/**
 * 快速导入第三方框架类库 所有第三方框架的类库文件统一放到 系统的Vendor目录下面
 * @param string $class 类库
 * @param string $baseUrl 基础目录
 * @param string $ext 类库后缀
 * @return boolean
 */
function vendor($class, $baseUrl = '', $ext = '.php')
{
    if (empty($baseUrl))
        $baseUrl = VENDOR_PATH;
    return import($class, $baseUrl, $ext);
}

/**
 * 实例化模型类 格式 [资源://][模块/]模型
 * @param string $name 资源地址
 * @param string $layer 模型层名称
 * @return Think\Model
 */
function D($name = '', $layer = '')
{
    if (empty($name)) return new Think\Model;
    static $_model = array();
    $layer = $layer ?: C('DEFAULT_M_LAYER');
    if (isset($_model[$name . $layer]))
        return $_model[$name . $layer];
    $class = parse_res_name($name, $layer);
    if (class_exists($class)) {
        $model = new $class(basename($name));
    } elseif (false === strpos($name, '/')) {
        // 自动加载公共模块下面的模型
        if (!C('APP_USE_NAMESPACE')) {
            import('Common/' . $layer . '/' . $class);
        } else {
            $class = '\\Common\\' . $layer . '\\' . $name . $layer;
        }
        $model = class_exists($class) ? new $class($name) : new Think\Model($name);
    } else {
        Think\Log::record('D方法实例化没找到模型类' . $class, Think\Log::NOTICE);
        $model = new Think\Model(basename($name));
    }
    $_model[$name . $layer] = $model;
    return $model;
}

/**
 * 实例化一个没有模型文件的Model
 * @param string $name Model名称 支持指定基础模型 例如 MongoModel:User
 * @param string $tablePrefix 表前缀
 * @param mixed $connection 数据库连接信息
 * @return Think\Model
 */
function M($name = '', $tablePrefix = '', $connection = '')
{
    static $_model = array();
    if (strpos($name, ':')) {
        list($class, $name) = explode(':', $name);
    } else {
        $class = 'Think\\Model';
    }
    $guid = (is_array($connection) ? implode('', $connection) : $connection) . $tablePrefix . $name . '_' . $class;
    if (!isset($_model[$guid]))
        $_model[$guid] = new $class($name, $tablePrefix, $connection);
    return $_model[$guid];
}

/**
 * 解析资源地址并导入类库文件
 * 例如 module/controller addon://module/behavior
 * @param string $name 资源地址 格式：[扩展://][模块/]资源名
 * @param string $layer 分层名称
 * @param integer $level 控制器层次
 * @return string
 */
function parse_res_name($name, $layer, $level = 1)
{
    if (strpos($name, '://')) {// 指定扩展资源
        list($extend, $name) = explode('://', $name);
    } else {
        $extend = '';
    }
    if (strpos($name, '/') && substr_count($name, '/') >= $level) { // 指定模块
        list($module, $name) = explode('/', $name, 2);
    } else {
        $module = defined('MODULE_NAME') ? MODULE_NAME : '';
    }
    $array = explode('/', $name);
    if (!C('APP_USE_NAMESPACE')) {
        $class = parse_name($name, 1);
        import($module . '/' . $layer . '/' . $class . $layer);
    } else {
        $class = $module . '\\' . $layer;
        foreach ($array as $name) {
            $class .= '\\' . parse_name($name, 1);
        }
        // 导入资源类库
        if ($extend) { // 扩展资源
            $class = $extend . '\\' . $class;
        }
    }
    return $class . $layer;
}

/**
 * 用于实例化访问控制器
 * @param string $name 控制器名
 * @param string $path 控制器命名空间（路径）
 * @return Think\Controller|false
 */
function controller($name, $path = '')
{
    $layer = C('DEFAULT_C_LAYER');
    if (!C('APP_USE_NAMESPACE')) {
        $class = parse_name($name, 1) . $layer;
        import(MODULE_NAME . '/' . $layer . '/' . $class);
    } else {
        $class = ($path ? basename(ADDON_PATH) . '\\' . $path : MODULE_NAME) . '\\' . $layer;
        $array = explode('/', $name);
        foreach ($array as $name) {
            $class .= '\\' . parse_name($name, 1);
        }
        $class .= $layer;
    }
    if (class_exists($class)) {
        return new $class();
    } else {
        return false;
    }
}

/**
 * 实例化多层控制器 格式：[资源://][模块/]控制器
 * @param string $name 资源地址
 * @param string $layer 控制层名称
 * @param integer $level 控制器层次
 * @return Think\Controller|false
 */
function A($name, $layer = '', $level = 0)
{
    static $_action = array();
    $layer = $layer ?: C('DEFAULT_C_LAYER');
    $level = $level ?: ($layer == C('DEFAULT_C_LAYER') ? C('CONTROLLER_LEVEL') : 1);
    if (isset($_action[$name . $layer]))
        return $_action[$name . $layer];

    $class = parse_res_name($name, $layer, $level);
    if (class_exists($class)) {
        $action = new $class();
        $_action[$name . $layer] = $action;
        return $action;
    } else {
        return false;
    }
}


/**
 * 远程调用控制器的操作方法 URL 参数格式 [资源://][模块/]控制器/操作
 * @param string $url 调用地址
 * @param string|array $vars 调用参数 支持字符串和数组
 * @param string $layer 要调用的控制层名称
 * @return mixed
 */
function R($url, $vars = array(), $layer = '')
{
    $info = pathinfo($url);
    $action = $info['basename'];
    $module = $info['dirname'];
    $class = A($module, $layer);
    if ($class) {
        if (is_string($vars)) {
            parse_str($vars, $vars);
        }
        return call_user_func_array(array(&$class, $action . C('ACTION_SUFFIX')), $vars);
    } else {
        return false;
    }
}

/**
 * 处理标签扩展
 * @param string $tag 标签名称
 * @param mixed $params 传入参数
 * @return void
 */
function tag($tag, &$params = NULL)
{
    \Think\Hook::listen($tag, $params);
}

/**
 * 执行某个行为
 * @param string $name 行为名称
 * @param string $tag 标签名称（行为类无需传入）
 * @param Mixed $params 传入的参数
 * @return void
 */
function B($name, $tag = '', &$params = NULL)
{
    if ('' == $tag) {
        $name .= 'Behavior';
    }
    return \Think\Hook::exec($name, $tag, $params);
}

/**
 * 去除代码中的空白和注释
 * @param string $content 代码内容
 * @return string
 */
function strip_whitespace($content)
{
    $stripStr = '';
    //分析php源码
    $tokens = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //过滤各种PHP注释
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //过滤空格
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<THINK\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "THINK;\n";
                    for ($k = $i + 1; $k < $j; $k++) {
                        if (is_string($tokens[$k]) && $tokens[$k] == ';') {
                            $i = $k;
                            break;
                        } else if ($tokens[$k][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

/**
 * 自定义异常处理
 * @param string $msg 异常消息
 * @param string $type 异常类型 默认为Think\Exception
 * @param integer $code 异常代码 默认为0
 * @return void
 */
function throw_exception($msg, $type = 'Think\\Exception', $code = 0)
{
    Think\Log::record('建议使用E方法替代throw_exception', Think\Log::NOTICE);
    if (class_exists($type, false))
        throw new $type($msg, $code);
    else
        Think\Think::halt($msg);        // 异常类型不存在则输出错误信息字串
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo = true, $label = null, $strict = true)
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    } else
        return $output;
}

/**
 * 设置当前页面的布局
 * @param string|false $layout 布局名称 为false的时候表示关闭布局
 * @return void
 */
function layout($layout)
{
    if (false !== $layout) {
        // 开启布局
        C('LAYOUT_ON', true);
        if (is_string($layout)) { // 设置新的布局模板
            C('LAYOUT_NAME', $layout);
        }
    } else {// 临时关闭布局
        C('LAYOUT_ON', false);
    }
}

/**
 * URL组装 支持不同URL模式
 * @param string $url URL表达式，格式：'[模块/控制器/操作#锚点@域名]?参数1=值1&参数2=值2...'
 * @param string|array $vars 传入的参数，支持数组和字符串
 * @param string|boolean $suffix 伪静态后缀，默认为true表示获取配置值
 * @param boolean $domain 是否显示域名
 * @return string
 */
function U($url = '', $vars = '', $suffix = true, $domain = false)
{
    // 解析URL
    $info = parse_url($url);
    $url = !empty($info['path']) ? $info['path'] : ACTION_NAME;
    if (isset($info['fragment'])) { // 解析锚点
        $anchor = $info['fragment'];
        if (false !== strpos($anchor, '?')) { // 解析参数
            list($anchor, $info['query']) = explode('?', $anchor, 2);
        }
        if (false !== strpos($anchor, '@')) { // 解析域名
            list($anchor, $host) = explode('@', $anchor, 2);
        }
    } elseif (false !== strpos($url, '@')) { // 解析域名
        list($url, $host) = explode('@', $info['path'], 2);
    }
    // 解析子域名
    if (isset($host)) {
        $domain = $host . (strpos($host, '.') ? '' : strstr($_SERVER['HTTP_HOST'], '.'));
    } elseif ($domain === true) {
        $domain = $_SERVER['HTTP_HOST'];
        if (C('APP_SUB_DOMAIN_DEPLOY')) { // 开启子域名部署
            $domain = $domain == 'localhost' ? 'localhost' : 'www' . strstr($_SERVER['HTTP_HOST'], '.');
            // '子域名'=>array('模块[/控制器]');
            foreach (C('APP_SUB_DOMAIN_RULES') as $key => $rule) {
                $rule = is_array($rule) ? $rule[0] : $rule;
                if (false === strpos($key, '*') && 0 === strpos($url, $rule)) {
                    $domain = $key . strstr($domain, '.'); // 生成对应子域名
                    $url = substr_replace($url, '', 0, strlen($rule));
                    break;
                }
            }
        }
    }

    // 解析参数
    if (is_string($vars)) { // aaa=1&bbb=2 转换成数组
        parse_str($vars, $vars);
    } elseif (!is_array($vars)) {
        $vars = array();
    }
    if (isset($info['query'])) { // 解析地址里面参数 合并到vars
        parse_str($info['query'], $params);
        $vars = array_merge($params, $vars);
    }

    // URL组装
    $depr = C('URL_PATHINFO_DEPR');
    $urlCase = C('URL_CASE_INSENSITIVE');
    if ($url) {
        if (0 === strpos($url, '/')) {// 定义路由
            $route = true;
            $url = substr($url, 1);
            if ('/' != $depr) {
                $url = str_replace('/', $depr, $url);
            }
        } else {
            if ('/' != $depr) { // 安全替换
                $url = str_replace('/', $depr, $url);
            }
            // 解析模块、控制器和操作
            $url = trim($url, $depr);
            $path = explode($depr, $url);
            $var = array();
            $varModule = C('VAR_MODULE');
            $varController = C('VAR_CONTROLLER');
            $varAction = C('VAR_ACTION');
            $var[$varAction] = !empty($path) ? array_pop($path) : ACTION_NAME;
            $var[$varController] = !empty($path) ? array_pop($path) : CONTROLLER_NAME;
            if ($maps = C('URL_ACTION_MAP')) {
                if (isset($maps[strtolower($var[$varController])])) {
                    $maps = $maps[strtolower($var[$varController])];
                    if ($action = array_search(strtolower($var[$varAction]), $maps)) {
                        $var[$varAction] = $action;
                    }
                }
            }
            if ($maps = C('URL_CONTROLLER_MAP')) {
                if ($controller = array_search(strtolower($var[$varController]), $maps)) {
                    $var[$varController] = $controller;
                }
            }
            if ($urlCase) {
                $var[$varController] = parse_name($var[$varController]);
            }
            $module = '';

            if (!empty($path)) {
                $var[$varModule] = implode($depr, $path);
            } else {
                if (C('MULTI_MODULE')) {
                    if (MODULE_NAME != C('DEFAULT_MODULE') || !C('MODULE_ALLOW_LIST')) {
                        $var[$varModule] = MODULE_NAME;
                    }
                }
            }
            if ($maps = C('URL_MODULE_MAP')) {
                if ($_module = array_search(strtolower($var[$varModule]), $maps)) {
                    $var[$varModule] = $_module;
                }
            }
            if (isset($var[$varModule])) {
                $module = $var[$varModule];
                unset($var[$varModule]);
            }

        }
    }

    if (C('URL_MODEL') == 0) { // 普通模式URL转换
        $url = __APP__ . '?' . C('VAR_MODULE') . "={$module}&" . http_build_query(array_reverse($var));
        if ($urlCase) {
            $url = strtolower($url);
        }
        if (!empty($vars)) {
            $vars = http_build_query($vars);
            $url .= '&' . $vars;
        }
    } else { // PATHINFO模式或者兼容URL模式
        if (isset($route)) {
            $url = __APP__ . '/' . rtrim($url, $depr);
        } else {
            $module = (defined('BIND_MODULE') && BIND_MODULE == $module) ? '' : $module;
            $url = __APP__ . '/' . ($module ? $module . MODULE_PATHINFO_DEPR : '') . implode($depr, array_reverse($var));
        }
        if ($urlCase) {
            $url = strtolower($url);
        }
        if (!empty($vars)) { // 添加参数
            foreach ($vars as $var => $val) {
                if ('' !== trim($val)) $url .= $depr . $var . $depr . urlencode($val);
            }
        }
        if ($suffix) {
            $suffix = $suffix === true ? C('URL_HTML_SUFFIX') : $suffix;
            if ($pos = strpos($suffix, '|')) {
                $suffix = substr($suffix, 0, $pos);
            }
            if ($suffix && '/' != substr($url, -1)) {
                $url .= '.' . ltrim($suffix, '.');
            }
        }
    }
    if (isset($anchor)) {
        $url .= '#' . $anchor;
    }
    if ($domain) {
        $url = (is_ssl() ? 'https://' : 'http://') . $domain . $url;
    }
    return $url;
}

/**
 * 渲染输出Widget
 * @param string $name Widget名称
 * @param array $data 传入的参数
 * @return void
 */
function W($name, $data = array())
{
    return R($name, $data, 'Widget');
}

/**
 * 判断是否SSL协议
 * @return boolean
 */
function is_ssl()
{
    if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
        return true;
    } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
        return true;
    }
    return false;
}

/**
 * URL重定向
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 */
function redirect($url, $time = 0, $msg = '')
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

/**
 * 缓存管理
 * @param mixed $name 缓存名称，如果为数组表示进行缓存设置
 * @param mixed $value 缓存值
 * @param mixed $options 缓存参数
 * @return mixed
 */
function S($name, $value = '', $options = null)
{
    static $cache = '';
    if (is_array($options)) {
        // 缓存操作的同时初始化
        $type = isset($options['type']) ? $options['type'] : '';
        $cache = Think\Cache::getInstance($type, $options);
    } elseif (is_array($name)) { // 缓存初始化
        $type = isset($name['type']) ? $name['type'] : '';
        $cache = Think\Cache::getInstance($type, $name);
        return $cache;
    } elseif (empty($cache)) { // 自动初始化
        $cache = Think\Cache::getInstance();
    }
    if ('' === $value) { // 获取缓存
        return $cache->get($name);
    } elseif (is_null($value)) { // 删除缓存
        return $cache->rm($name);
    } else { // 缓存数据
        if (is_array($options)) {
            $expire = isset($options['expire']) ? $options['expire'] : NULL;
        } else {
            $expire = is_numeric($options) ? $options : NULL;
        }
        return $cache->set($name, $value, $expire);
    }
}

/**
 * 快速文件数据读取和保存 针对简单类型数据 字符串、数组
 * @param string $name 缓存名称
 * @param mixed $value 缓存值
 * @param string $path 缓存路径
 * @return mixed
 */
function F($name, $value = '', $path = DATA_PATH)
{
    static $_cache = array();
    $filename = $path . $name . '.php';
    if ('' !== $value) {
        if (is_null($value)) {
            // 删除缓存
            if (false !== strpos($name, '*')) {
                return false; // TODO 
            } else {
                unset($_cache[$name]);
                return Think\Storage::unlink($filename, 'F');
            }
        } else {
            Think\Storage::put($filename, serialize($value), 'F');
            // 缓存数据
            $_cache[$name] = $value;
            return null;
        }
    }
    // 获取缓存数据
    if (isset($_cache[$name]))
        return $_cache[$name];
    if (Think\Storage::has($filename, 'F')) {
        $value = unserialize(Think\Storage::read($filename, 'F'));
        $_cache[$name] = $value;
    } else {
        $value = false;
    }
    return $value;
}

/**
 * 根据PHP各种类型变量生成唯一标识号
 * @param mixed $mix 变量
 * @return string
 */
function to_guid_string($mix)
{
    if (is_object($mix)) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * XML编码
 * @param mixed $data 数据
 * @param string $root 根节点名
 * @param string $item 数字索引的子节点名
 * @param string $attr 根节点属性
 * @param string $id 数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 * @return string
 */
function xml_encode($data, $root = 'think', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8')
{
    if (is_array($attr)) {
        $_attr = array();
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $_attr);
    }
    $attr = trim($attr);
    $attr = empty($attr) ? '' : " {$attr}";
    $xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $xml .= "<{$root}{$attr}>";
    $xml .= data_to_xml($data, $item, $id);
    $xml .= "</{$root}>";
    return $xml;
}

/**
 * 数据XML编码
 * @param mixed $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id 数字索引key转换为的属性名
 * @return string
 */
function data_to_xml($data, $item = 'item', $id = 'id')
{
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if (is_numeric($key)) {
            $id && $attr = " {$id}=\"{$key}\"";
            $key = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}

/**
 * session管理函数
 * @param string|array $name session名称 如果为数组则表示进行session设置
 * @param mixed $value session值
 * @return mixed
 */
function session($name = '', $value = '')
{
    $prefix = C('SESSION_PREFIX');
    if (is_array($name)) { // session初始化 在session_start 之前调用
        if (isset($name['prefix'])) C('SESSION_PREFIX', $name['prefix']);
        if (C('VAR_SESSION_ID') && isset($_REQUEST[C('VAR_SESSION_ID')])) {
            session_id($_REQUEST[C('VAR_SESSION_ID')]);
        } elseif (isset($name['id'])) {
            session_id($name['id']);
        }
        if ('common' == APP_MODE) { // 其它模式可能不支持
            ini_set('session.auto_start', 0);
        }
        if (isset($name['name'])) session_name($name['name']);
        if (isset($name['path'])) session_save_path($name['path']);
        if (isset($name['domain'])) ini_set('session.cookie_domain', $name['domain']);
        if (isset($name['expire'])) {
            ini_set('session.gc_maxlifetime', $name['expire']);
            ini_set('session.cookie_lifetime', $name['expire']);
        }
        if (isset($name['use_trans_sid'])) ini_set('session.use_trans_sid', $name['use_trans_sid'] ? 1 : 0);
        if (isset($name['use_cookies'])) ini_set('session.use_cookies', $name['use_cookies'] ? 1 : 0);
        if (isset($name['cache_limiter'])) session_cache_limiter($name['cache_limiter']);
        if (isset($name['cache_expire'])) session_cache_expire($name['cache_expire']);
        if (isset($name['type'])) C('SESSION_TYPE', $name['type']);
        if (C('SESSION_TYPE')) { // 读取session驱动
            $type = C('SESSION_TYPE');
            $class = strpos($type, '\\') ? $type : 'Think\\Session\\Driver\\' . ucwords(strtolower($type));
            $hander = new $class();
            session_set_save_handler(
                array(&$hander, "open"),
                array(&$hander, "close"),
                array(&$hander, "read"),
                array(&$hander, "write"),
                array(&$hander, "destroy"),
                array(&$hander, "gc"));
        }
        // 启动session
        if (C('SESSION_AUTO_START')) session_start();
    } elseif ('' === $value) {
        if ('' === $name) {
            // 获取全部的session
            return $prefix ? $_SESSION[$prefix] : $_SESSION;
        } elseif (0 === strpos($name, '[')) { // session 操作
            if ('[pause]' == $name) { // 暂停session
                session_write_close();
            } elseif ('[start]' == $name) { // 启动session
                session_start();
            } elseif ('[destroy]' == $name) { // 销毁session
                $_SESSION = array();
                session_unset();
                session_destroy();
            } elseif ('[regenerate]' == $name) { // 重新生成id
                session_regenerate_id();
            }
        } elseif (0 === strpos($name, '?')) { // 检查session
            $name = substr($name, 1);
            if (strpos($name, '.')) { // 支持数组
                list($name1, $name2) = explode('.', $name);
                return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
            } else {
                return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
            }
        } elseif (is_null($name)) { // 清空session
            if ($prefix) {
                unset($_SESSION[$prefix]);
            } else {
                $_SESSION = array();
            }
        } elseif ($prefix) { // 获取session
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;
            } else {
                return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
            }
        } else {
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
            } else {
                return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            }
        }
    } elseif (is_null($value)) { // 删除session
        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                unset($_SESSION[$prefix][$name1][$name2]);
            } else {
                unset($_SESSION[$name1][$name2]);
            }
        } else {
            if ($prefix) {
                unset($_SESSION[$prefix][$name]);
            } else {
                unset($_SESSION[$name]);
            }
        }
    } else { // 设置session
        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                $_SESSION[$prefix][$name1][$name2] = $value;
            } else {
                $_SESSION[$name1][$name2] = $value;
            }
        } else {
            if ($prefix) {
                $_SESSION[$prefix][$name] = $value;
            } else {
                $_SESSION[$name] = $value;
            }
        }
    }
    return null;
}

/**
 * Cookie 设置、获取、删除
 * @param string $name cookie名称
 * @param mixed $value cookie值
 * @param mixed $option cookie参数
 * @return mixed
 */
function cookie($name = '', $value = '', $option = null)
{
    // 默认设置
    $config = array(
        'prefix' => C('COOKIE_PREFIX'), // cookie 名称前缀
        'expire' => C('COOKIE_EXPIRE'), // cookie 保存时间
        'path' => C('COOKIE_PATH'), // cookie 保存路径
        'domain' => C('COOKIE_DOMAIN'), // cookie 有效域名
        'secure' => C('COOKIE_SECURE'), //  cookie 启用安全传输
        'httponly' => C('COOKIE_HTTPONLY'), // httponly设置
    );
    // 参数设置(会覆盖黙认设置)
    if (!is_null($option)) {
        if (is_numeric($option))
            $option = array('expire' => $option);
        elseif (is_string($option))
            parse_str($option, $option);
        $config = array_merge($config, array_change_key_case($option));
    }
    if (!empty($config['httponly'])) {
        ini_set("session.cookie_httponly", 1);
    }
    // 清除指定前缀的所有cookie
    if (is_null($name)) {
        if (empty($_COOKIE))
            return null;
        // 要删除的cookie前缀，不指定则删除config设置的指定前缀
        $prefix = empty($value) ? $config['prefix'] : $value;
        if (!empty($prefix)) {// 如果前缀为空字符串将不作处理直接返回
            foreach ($_COOKIE as $key => $val) {
                if (0 === stripos($key, $prefix)) {
                    setcookie($key, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
                    unset($_COOKIE[$key]);
                }
            }
        }
        return null;
    } elseif ('' === $name) {
        // 获取全部的cookie
        return $_COOKIE;
    }
    $name = $config['prefix'] . str_replace('.', '_', $name);
    if ('' === $value) {
        if (isset($_COOKIE[$name])) {
            $value = $_COOKIE[$name];
            if (0 === strpos($value, 'think:')) {
                $value = substr($value, 6);
                return array_map('urldecode', json_decode(MAGIC_QUOTES_GPC ? stripslashes($value) : $value, true));
            } else {
                return $value;
            }
        } else {
            return null;
        }
    } else {
        if (is_null($value)) {
            setcookie($name, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
            unset($_COOKIE[$name]); // 删除指定cookie
        } else {
            // 设置cookie
            if (is_array($value)) {
                $value = 'think:' . json_encode(array_map('urlencode', $value));
            }
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
            setcookie($name, $value, $expire, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
            $_COOKIE[$name] = $value;
        }
    }
    return null;
}

/**
 * 加载动态扩展文件
 * @var string $path 文件路径
 * @return void
 */
function load_ext_file($path)
{
    // 加载自定义外部文件
    if ($files = C('LOAD_EXT_FILE')) {
        $files = explode(',', $files);
        foreach ($files as $file) {
            $file = $path . 'Common/' . $file . '.php';
            if (is_file($file)) include $file;
        }
    }
    // 加载自定义的动态配置文件
    if ($configs = C('LOAD_EXT_CONFIG')) {
        if (is_string($configs)) $configs = explode(',', $configs);
        foreach ($configs as $key => $config) {
            $file = is_file($config) ? $config : $path . 'Conf/' . $config . CONF_EXT;
            if (is_file($file)) {
                is_numeric($key) ? C(load_config($file)) : C($key, load_config($file));
            }
        }
    }
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false)
{
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) return $ip[$type];
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * 发送HTTP状态
 * @param integer $code 状态码
 * @return void
 */
function send_http_status($code)
{
    static $_status = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily ',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );
    if (isset($_status[$code])) {
        header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
        // 确保FastCGI模式下正常
        header('Status:' . $code . ' ' . $_status[$code]);
    }
}

function think_filter(&$value)
{
    // TODO 其他安全过滤

    // 过滤查询特殊字符
    if (preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i', $value)) {
        $value .= ' ';
    }
}

// 不区分大小写的in_array实现
function in_array_case($value, $array)
{
    return in_array(strtolower($value), array_map('strtolower', $array));
}

//加密函数
function passport_encrypt($txt, $key)
{
    srand((double)microtime() * 1000000);
    $encrypt_key = md5(rand(0, 32000));
    $ctr = 0;
    $tmp = '';
    for ($i = 0; $i < strlen($txt); $i++) {
        $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
        $tmp .= $encrypt_key[$ctr] . ($txt[$i] ^ $encrypt_key[$ctr++]);
    }
    return base64_encode(passport_key($tmp, $key));
}

//解密函数
function passport_decrypt($txt, $key)
{
    $txt = passport_key(base64_decode($txt), $key);
    $tmp = '';
    for ($i = 0; $i < strlen($txt); $i++) {
        $md5 = $txt[$i];
        $tmp .= $txt[++$i] ^ $md5;
    }
    return $tmp;
}

function passport_key($txt, $encrypt_key)
{
    $encrypt_key = md5($encrypt_key);
    $ctr = 0;
    $tmp = '';
    for ($i = 0; $i < strlen($txt); $i++) {
        $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
        $tmp .= $txt[$i] ^ $encrypt_key[$ctr++];
    }
    return $tmp;
}

/**
 * 将字符串分割为数组
 * @param  string $str 字符串
 * @return array       分割得到的数组
 */
function mb_str_split($str)
{
    return preg_split('/(?<!^)(?!$)/u', $str);
}

/**
 * 生成不重复的随机数
 * @param  int $start 需要生成的数字开始范围
 * @param  int $end 结束范围
 * @param  int $length 需要生成的随机数个数
 * @return array       生成的随机数
 */
function get_rand_number($start = 1, $end = 10, $length = 4)
{
    $connt = 0;
    $temp = array();
    while ($connt < $length) {
        $temp[] = mt_rand($start, $end);
        $data = array_unique($temp);
        $connt = count($data);
    }
    sort($data);
    return $data;
}

/**
 * 执行sql语句
 * @param  int $sql 需要生成的sql文件
 * @param  int $database_name 数据库名
 * @param  int $username 用户名
 * @return int $password   密码
 */
function execsql($sql, $database_name, $host = 'localhost', $username = 'root', $password = '')
{
    $con = @mysql_connect($host, $username, $password);
    if (!$con) {
        die('Could not connect: ' . mysql_error());
    } else {
        mysql_query('CREATE DATABASE IF NOT EXISTS ' . $database_name . ' DEFAULT CHARACTER SET utf8;', $con);
    }
    mysql_select_db($database_name);
    mysql_query('set names utf8');
    $sql_array = preg_split("/;[\r\n]+/", file_get_contents($sql));
    foreach ($sql_array as $k => $v) {
        mysql_query($v, $con);
        echo mysql_error() . '<br>';
    }
}

//循环清除文件，文件夹
function delDirAndFile($dirName)
{
    if ($handle = opendir("$dirName")) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                if (is_dir("$dirName/$item")) {
                    delDirAndFile("$dirName/$item");
                } else {
                    unlink("$dirName/$item");
                }
            }
        }
        closedir($handle);
        if (rmdir($dirName)) return true;//删除空文件夹
    }
}
//判断浏览器类型方法：
function userBrowser() {
    $user_OSagent = $_SERVER['HTTP_USER_AGENT'];

    if (strpos($user_OSagent, "Maxthon") && strpos($user_OSagent, "MSIE")) {
        $visitor_browser = "Maxthon(Microsoft IE)";
    } elseif (strpos($user_OSagent, "Maxthon 2.0")) {
        $visitor_browser = "Maxthon 2.0";
    } elseif (strpos($user_OSagent, "Maxthon")) {
        $visitor_browser = "Maxthon";
    } elseif (strpos($user_OSagent, "MSIE 9.0")) {
        $visitor_browser = "MSIE 9.0";
    } elseif (strpos($user_OSagent, "MSIE 8.0")) {
        $visitor_browser = "MSIE 8.0";
    } elseif (strpos($user_OSagent, "MSIE 7.0")) {
        $visitor_browser = "MSIE 7.0";
    } elseif (strpos($user_OSagent, "MSIE 6.0")) {
        $visitor_browser = "MSIE 6.0";
    } elseif (strpos($user_OSagent, "MSIE 5.5")) {
        $visitor_browser = "MSIE 5.5";
    } elseif (strpos($user_OSagent, "MSIE 5.0")) {
        $visitor_browser = "MSIE 5.0";
    } elseif (strpos($user_OSagent, "MSIE 4.01")) {
        $visitor_browser = "MSIE 4.01";
    } elseif (strpos($user_OSagent, "MSIE")) {
        $visitor_browser = "MSIE 较高版本";
    } elseif (strpos($user_OSagent, "NetCaptor")) {
        $visitor_browser = "NetCaptor";
    } elseif (strpos($user_OSagent, "Netscape")) {
        $visitor_browser = "Netscape";
    } elseif (strpos($user_OSagent, "Chrome")) {
        $visitor_browser = "Chrome";
    } elseif (strpos($user_OSagent, "Lynx")) {
        $visitor_browser = "Lynx";
    } elseif (strpos($user_OSagent, "Opera")) {
        $visitor_browser = "Opera";
    } elseif (strpos($user_OSagent, "Konqueror")) {
        $visitor_browser = "Konqueror";
    } elseif (strpos($user_OSagent, "Mozilla/5.0")) {
        $visitor_browser = "Mozilla";
    } elseif (strpos($user_OSagent, "Firefox")) {
        $visitor_browser = "Firefox";
    } elseif (strpos($user_OSagent, "U")) {
        $visitor_browser = "Firefox";
    } else {
        $visitor_browser = "其它";
    }
    return $visitor_browser;
}
//字体转换
function zt($text)
{
    define('UTF32_BIG_ENDIAN_BOM', chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
    define('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
    define('UTF16_BIG_ENDIAN_BOM', chr(0xFE) . chr(0xFF));
    define('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
    define('UTF8_BOM', chr(0xEF) . chr(0xBB) . chr(0xBF));
    $first2 = substr($text, 0, 2);
    $first3 = substr($text, 0, 3);
    $first4 = substr($text, 0, 3);
    $encodType = "";
    if ($first3 == UTF8_BOM)
        $encodType = 'UTF-8 BOM';
    else if ($first4 == UTF32_BIG_ENDIAN_BOM)
        $encodType = 'UTF-32BE';
    else if ($first4 == UTF32_LITTLE_ENDIAN_BOM)
        $encodType = 'UTF-32LE';
    else if ($first2 == UTF16_BIG_ENDIAN_BOM)
        $encodType = 'UTF-16BE';
    else if ($first2 == UTF16_LITTLE_ENDIAN_BOM)
        $encodType = 'UTF-16LE';

    //下面的判断主要还是判断ANSI编码的·
    if ($encodType == '') {//即默认创建的txt文本-ANSI编码的
        $content = iconv("GBK", "UTF-8", $text);
    } else if ($encodType == 'UTF-8 BOM') {//本来就是UTF-8不用转换
        $content = $text;
    } else {//其他的格式都转化为UTF-8就可以了
        $content = iconv($encodType, "UTF-8", $text);
    }
    return $content;
}

//无限分类
function genTree($items, $pid = 'pid')
{
    $map = [];
    $tree = [];
    foreach ($items as &$it) {
        $map[$it['id']] = &$it;
    }  //数据的ID名生成新的引用索引树
    //var_dump($map);
    foreach ($items as &$it) {
        $parent = &$map[$it[$pid]];
        if ($parent) {
            $parent['son'][] = &$it;
            //var_dump($parent);
        } else {
            $tree[] = &$it;
        }
    }

    return $tree;
}
//tree无限分类
function tree($arr, $pid = 0, $lev = 0)
{
    static $list = array();
    foreach ($arr as $k=>$v) {
        if ($v['pid'] == $pid) {
            $list[$k] = $v;
            $list[$k]['level']=str_repeat('-', $lev);
            tree($arr, $v['id'], $lev + 1);
        }
    }
    return $list;
}
//ip地址接口(1)(2) GetIp real_ip

function GetIp()
{
    $realip = '';
    $unknown = 'unknown';
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($arr as $ip) {
                $ip = trim($ip);
                if ($ip != 'unknown') {
                    $realip = $ip;
                    break;
                }
            }
        } else if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], $unknown)) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            $realip = $_SERVER['REMOTE_ADDR'];
        } else {
            $realip = $unknown;
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else if (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)) {
            $realip = getenv("REMOTE_ADDR");
        } else {
            $realip = $unknown;
        }
    }
    $realip = preg_match("/[\d\.]{7,15}/", $realip, $matches) ? $matches[0] : $unknown;
    return $realip;
}

function real_ip()
{
    static $realip = NULL;

    if ($realip !== NULL) {
        return $realip;
    }

    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip) {
                $ip = trim($ip);

                if ($ip != 'unknown') {
                    $realip = $ip;

                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            } else {
                $realip = '0.0.0.0';
            }
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }
    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
    return $realip;
}

//ip地址库
function GetIpLookup($ip = '')
{
    if ($ip == '') {
        $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
        $ip = json_decode(file_get_contents($url), true);
        $data = $ip;
    } else {
        $url = "http://ip.taobao.com/service/getIpInfo.php?ip=" . $ip;
        $ip = json_decode(file_get_contents($url));
        if ((string)$ip->code == '1') {
            return false;
        }
        $data = (array)$ip->data;
    }

    return $data;
}
//此接口无访问频率限制
function GetIpLookup1($ip = '')
{
    if ($ip == '') {
        $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
        $ip = json_decode(file_get_contents($url), true);
        $data = $ip;
    } else {
        $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=".$ip;
        $ip = json_decode(file_get_contents($url),true);
        $data = $ip;
    }

    return $data;
}
// curl
function curl( $url, $fields = [ ] ) {
    $ch = curl_init();
    //设置我们请求的地址
    curl_setopt( $ch, CURLOPT_URL, $url );
    //数据返回后不要直接显示
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    //禁止证书校验
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
    if ( $fields ) {
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields );
    }
    $data = '';
    if ( curl_exec( $ch ) ) {
        //发送成功,获取数据
        $data = curl_multi_getcontent( $ch );
    }
    curl_close( $ch );

    return $data;

}
// curl_post
function http_post($api_url = '', $param = array(), $timeout = 5)
{

    if (!$api_url) {
        die("error api_url");
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);

    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    if (parse_url($api_url)['scheme'] == 'https') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    }
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:key-' . $this->_api_key);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

    $res = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
        $this->_last_error[] = $error;
        return FALSE;
    }
    return $res;
}

//curl_get
function http_get($api_url = '', $timeout = 5)
{

    if (!$api_url) {
        die("error api_url");
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);

    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    if (parse_url($api_url)['scheme'] == 'https') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    }
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:key-' . $this->_api_key);

    $res = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
        $this->_last_error[] = curl_error($ch);
        return FALSE;
    }
    return $res;
}



//将内容进行UNICODE编码，编码后的内容格式：\u56fe\u7247 （原始：图片）
function unicode_encode($name)
{
    $name = iconv('UTF-8', 'UCS-2', $name);
    $len = strlen($name);
    $str = '';
    for ($i = 0; $i < $len - 1; $i = $i + 2) {
        $c = $name[$i];
        $c2 = $name[$i + 1];
        if (ord($c) > 0) {    // 两个字节的文字
            $str .= '\u' . base_convert(ord($c), 10, 16) . base_convert(ord($c2), 10, 16);
        } else {
            $str .= $c2;
        }
    }
    return $str;
}

// 将UNICODE编码后的内容进行解码，编码后的内容格式：\u56fe\u7247 （原始：图片）
function unicode_decode($name)
{
    // 转换编码，将Unicode编码转换成可以浏览的utf-8编码
    $pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
    preg_match_all($pattern, $name, $matches);
    if (!empty($matches)) {
        $name = '';
        for ($j = 0; $j < count($matches[0]); $j++) {
            $str = $matches[0][$j];
            if (strpos($str, '\\u') === 0) {
                $code = base_convert(substr($str, 2, 2), 16, 10);
                $code2 = base_convert(substr($str, 4), 16, 10);
                $c = chr($code) . chr($code2);
                $c = iconv('UCS-2', 'UTF-8', $c);
                $name .= $c;
            } else {
                $name .= $str;
            }
        }
    }
    return $name;
}

//图片加密
function base64EncodeImage($image_file)
{
    $base64_image = '';
    $image_info = getimagesize($image_file);
    $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
    $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
    return $base64_image;
}

//开启socket客户端
function socket($in, $address = '127.0.0.1')
{
    // 端口
    $service_port = 20002;
    $ret = true;
    // 创建 TCP/IP socket
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket < 0) {
        $ret = false;
    }
    $result = socket_connect($socket, $address, $service_port);
    if ($result < 0) {
        $ret = false;
    }
    $out = '';
    socket_write($socket, $in, strlen($in));
    while ($out = socket_read($socket, 2048)) {
        $ret = $out;
    }
    socket_close($socket);
    return $ret;
}

//取得磁盘使用情况 byte_format get_disk_space get_spec_disk
function byte_format($size, $dec = 2)
{
    $a = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
    $pos = 0;
    while ($size >= 1024) {
        $size /= 1024;
        $pos++;
    }
    return round($size, $dec) . " " . $a [$pos];
}

function get_disk_space($letter)
{
    // 获取磁盘信息
    $diskct = 0;
    $disk = array();
    $diskz = 0; // 磁盘总容量
    $diskk = 0; // 磁盘剩余容量

    $is_disk = $letter . ':';
    if (@disk_total_space($is_disk) != NULL) {
        $diskct++;
        $disk [$letter] [0] = byte_format(@disk_free_space($is_disk));
        $disk [$letter] [1] = byte_format(@disk_total_space($is_disk));
        $disk [$letter] [2] = byte_format(@disk_total_space($is_disk) - @disk_free_space($is_disk));
        $disk [$letter] [3] = round((((@disk_total_space($is_disk) - @disk_free_space($is_disk)) / (1024 * 1024 * 1024)) / (@disk_total_space($is_disk) / (1024 * 1024 * 1024))) * 100, 2) . '%';
        $diskk += byte_format(@disk_free_space($is_disk));
        $diskz += byte_format(@disk_total_space($is_disk));
    }
    return $disk;
}

function get_spec_disk($type = 'system')
{
    $disk = array();

    switch ($type) {
        case 'system' :
            $disk = @get_disk_space(strrev(array_pop(explode(':', strrev(getenv('SystemRoot'))))));
            break;
        case 'all' :
            foreach (range('b', 'z') as $letter) {
                $disk = array_merge($disk, get_disk_space($letter));
            }
            break;
        default :
            $disk = get_disk_space($type);
            break;
    }
    return $disk;
}

// 风险过滤(1)编码
function htmlencode($str)
{

    if (empty($str)) return;

    if ($str == "") return $str;

    $str = trim($str);

    $str = str_replace("&", "&amp;", $str);

    $str = str_replace(">", "&gt;", $str);

    $str = str_replace("<", "&lt;", $str);

    $str = str_replace(chr(32), "&nbsp;", $str);

    $str = str_replace(chr(9), "&nbsp;", $str);

    $str = str_replace(chr(34), "&", $str);

    $str = str_replace(chr(39), "&#39;", $str);

    $str = str_replace(chr(13), "<br />", $str);

    $str = str_replace("'", "''", $str);

    $str = str_replace("select", "sel&#101;ct", $str);

    $str = str_replace("join", "jo&#105;n", $str);

    $str = str_replace("union", "un&#105;on", $str);

    $str = str_replace("where", "wh&#101;re", $str);

    $str = str_replace("insert", "ins&#101;rt", $str);

    $str = str_replace("delete", "del&#101;te", $str);

    $str = str_replace("update", "up&#100;ate", $str);

    $str = str_replace("like", "lik&#101;", $str);

    $str = str_replace("drop", "dro&#112;", $str);

    $str = str_replace("create", "cr&#101;ate", $str);

    $str = str_replace("modify", "mod&#105;fy", $str);

    $str = str_replace("rename", "ren&#097;me", $str);

    $str = str_replace("alter", "alt&#101;r", $str);

    $str = str_replace("cast", "ca&#115;", $str);

    return $str;

}

function htmldecode($str)
{

    if (empty($str)) return;

    if ($str == "") return $str;

    $str = str_replace("sel&#101;ct", "select", $str);

    $str = str_replace("jo&#105;n", "join", $str);

    $str = str_replace("un&#105;on", "union", $str);

    $str = str_replace("wh&#101;re", "where", $str);

    $str = str_replace("ins&#101;rt", "insert", $str);

    $str = str_replace("del&#101;te", "delete", $str);

    $str = str_replace("up&#100;ate", "update", $str);

    $str = str_replace("lik&#101;", "like", $str);

    $str = str_replace("dro&#112;", "drop", $str);

    $str = str_replace("cr&#101;ate", "create", $str);

    $str = str_replace("mod&#105;fy", "modify", $str);

    $str = str_replace("ren&#097;me", "rename", $str);

    $str = str_replace("alt&#101;r", "alter", $str);

    $str = str_replace("ca&#115;", "cast", $str);

    $str = str_replace("&amp;", "&", $str);

    $str = str_replace("&gt;", ">", $str);

    $str = str_replace("&lt;", "<", $str);

    $str = str_replace("&nbsp;", chr(32), $str);

    $str = str_replace("&nbsp;", chr(9), $str);

    $str = str_replace("&", chr(34), $str);

    $str = str_replace("&#39;", chr(39), $str);

    $str = str_replace("<br />", chr(13), $str);

    $str = str_replace("''", "'", $str);

    return $str;

}

//判断session 过期时间

function loginEmployeeid()
{
    $employee = session('employee');
    if (empty($employee)) {
        return 0;
    } else {

        $last_access = session('last_access');

        if ($last_access != null && (time() - $last_access) <= 30) {
            session('last_access', time());
            return 1;
        } else {
            return 0;
        }
    }
}

// 输入秒返回天时分秒
function DateTimeFormat($Sec)
{
    if (!isset ($Sec)) {
        return "";
    }
    $dd = 0;
    $hh = 0;
    $mm = 0;
    $Sec = ( int )$Sec;

    $dd = floor($Sec / (24 * 3600));

    $Sec = $Sec - (24 * 3600) * $dd;

    $hh = floor($Sec / 3600);

    $Sec = $Sec - 3600 * $hh;

    $mm = floor($Sec / 60);

    $Sec = $Sec - 60 * $mm;

    $dStr = $dd > 0 ? $dd . "天" : "";
    $hStr = $hh > 0 ? $hh . "小时" : "";
    $mStr = $mm > 0 ? $mm . "分" : "";
    $sStr = $Sec > 0 ? $Sec . "秒" : "";

    return $dStr . $hStr . $mStr . $sStr;
}

// 输入秒返回时分秒
function DateTimeFormatExt($Sec)
{
    if (!isset ($Sec)) {
        return "";
    }
    $dd = 0;
    $hh = 0;
    $mm = 0;
    $Sec = ( int )$Sec;
    $hh = floor($Sec / 3600);

    $Sec = $Sec - 3600 * $hh;

    $mm = floor($Sec / 60);

    $Sec = $Sec - 60 * $mm;

    $dStr = $dd > 0 ? $dd . "天" : "";
    $hStr = $hh > 0 ? $hh . "小时" : "";
    $mStr = $mm > 0 ? $mm . "分" : "";
    $sStr = $Sec . "秒";
    return $dStr . $hStr . $mStr . $sStr;
}
//将数组转为xml文件
function arrayToXml($arr,$dom=0,$item=0){
    if (!$dom){
        $dom = new DOMDocument("1.0");
    }
    if(!$item){
        $item = $dom->createElement("root");
        $dom->appendChild($item);
    }
    foreach ($arr as $key=>$val){
        $itemx = $dom->createElement(is_string($key)?$key:"item");
        $item->appendChild($itemx);
        if (!is_array($val)){
            $text = $dom->createTextNode($val);
            $itemx->appendChild($text);

        }else {
            arrayToXml($val,$dom,$itemx);
        }
    }
    return $dom->saveXML();
}
//二维数组模糊查询
function arrList($arrs,$keywords=NULL,$type=array('title')){
    $result= array();
    foreach ($arrs as $key => $searchData) {
        $arr = array();
        foreach($searchData as $values=>$v ) {
            for ($i=0;$i<count($type);$i++){
                if ($values==$type[$i]){
                    array_push($arr, $values);
                }
            }
        }
        for ($a=0;$a<count($arr);$a++){
            if (strpos($searchData[$arr[$a]],$keywords)) {
                $result[] = $searchData;
            }
        }
    }
    return $result;
}
/**
 * 生成登陆验证码，并写入session['verification_code']
 *
 * @param int $length
 * @param int $width
 * @param int $height
 */
function create_verification_code($length, $width, $height)
{
    $code = "";
    for ($i = 0; $i < $length; $i++) {
        $code .= rand(0, 9);
    }
    // 4位验证码也可以用rand(1000,9999)直接生成
    // 将生成的验证码写入session，备验证时用
    $_SESSION ["verification_code"] = $code;
    // 创建图片，定义颜色值
    header("Content-type: image/PNG");
    $im = imagecreate($width, $height);
    $black = imagecolorallocate($im, 0, 0, 0);
    $gray = imagecolorallocate($im, 200, 200, 200);
    $bgcolor = imagecolorallocate($im, 255, 255, 255);
    // 填充背景
    imagefill($im, 0, 0, $gray);

    // 画边框
    imagerectangle($im, 0, 0, $width - 1, $height - 1, $black);

    // 随机绘制两条虚线，起干扰作用
    $style = array($black, $black, $black, $black, $black, $gray, $gray, $gray, $gray, $gray);
    imagesetstyle($im, $style);
    $y1 = rand(0, $height);
    $y2 = rand(0, $height);
    $y3 = rand(0, $height);
    $y4 = rand(0, $height);
    imageline($im, 0, $y1, $width, $y3, IMG_COLOR_STYLED);
    imageline($im, 0, $y2, $width, $y4, IMG_COLOR_STYLED);

    // 在画布上随机生成大量黑点，起干扰作用;
    for ($i = 0; $i < 80; $i++) {
        imagesetpixel($im, rand(0, $width), rand(0, $height), $black);
    }
    // 将数字随机显示在画布上,字符的水平间距和位置都按一定波动范围随机生成
    $strx = rand(13, 18);
    for ($i = 0; $i < $length; $i++) {
        $strpos = rand(6, 9);
        imagestring($im, 5, $strx, $strpos, substr($code, $i, 1), $black);
        $strx += rand(8, 12);
    }
    imagepng($im); // 输出图片
    imagedestroy($im); // 释放图片所占内存
}
//下载
function downfile($file)
{
    $filename=$file; //文件名
    $date=date("Ymd-H:i:m");
    Header( "Content-type:  application/octet-stream ");
    Header( "Accept-Ranges:  bytes ");
    Header( "Accept-Length: " .filesize($filename));
    header( "Content-Disposition:  attachment;  filename= {$date}.doc");
    echo file_get_contents($filename);
    readfile($filename);
}
//生成彩虹字
function color_txt($str){
    $len        = mb_strlen($str);
    $colorTxt   = '';
    for($i=0; $i<$len; $i++) {
        $colorTxt .=  '<span style="color:'.rand_color().'">'.mb_substr($str,$i,1,'utf-8').'</span>';
    }
    return $colorTxt;
}
function rand_color(){
    return '#'.sprintf("%02X",mt_rand(0,255)).sprintf("%02X",mt_rand(0,255)).sprintf("%02X",mt_rand(0,255));
}
/**
 * all_external_link 检测字符串是否包含外链
 * @param  string  $text 文字
 * @param  string  $host 域名
 * @return boolean       false 有外链 true 无外链
 */
function all_external_link($text = '', $host = '') {
    if (empty($host)) $host = $_SERVER['HTTP_HOST'];
    $reg = '/http(?:s?):\/\/((?:[A-za-z0-9-]+\.)+[A-za-z]{2,4})/';
    preg_match_all($reg, $text, $data);
    $math = $data[1];
    foreach ($math as $value) {
        if($value != $host) return false;
    }
    return true;
}
/**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @param string $type  类型. normal | mohu | full | ymd | other
 * @param string $alt   已失效
 * @return string
 */
function friendlyDate($sTime,$type = 'normal',$alt = 'false') {
    //sTime=源时间，cTime=当前时间，dTime=时间差
    $cTime        =    time();
    $dTime        =    $cTime - $sTime;
    $dDay        =    intval(date("z",$cTime)) - intval(date("z",$sTime));
    //$dDay        =    intval($dTime/3600/24);
    $dYear        =    intval(date("Y",$cTime)) - intval(date("Y",$sTime));
    //normal：n秒前，n分钟前，n小时前，日期
    if($type=='normal'){
        if( $dTime < 60 ){
            return $dTime."秒前";
        }elseif( $dTime < 3600 ){
            return intval($dTime/60)."分钟前";
            //今天的数据.年份相同.日期相同.
        }elseif( $dYear==0 && $dDay == 0  ){
            //return intval($dTime/3600)."小时前";
            return '今天'.date('H:i',$sTime);
        }elseif($dYear==0){
            return date("m月d日 H:i",$sTime);
        }else{
            return date("Y-m-d H:i",$sTime);
        }
    }elseif($type=='mohu'){
        if( $dTime < 60 ){
            return $dTime."秒前";
        }elseif( $dTime < 3600 ){
            return intval($dTime/60)."分钟前";
        }elseif( $dTime >= 3600 && $dDay == 0  ){
            return intval($dTime/3600)."小时前";
        }elseif( $dDay > 0 && $dDay<=7 ){
            return intval($dDay)."天前";
        }elseif( $dDay > 7 &&  $dDay <= 30 ){
            return intval($dDay/7) . '周前';
        }elseif( $dDay > 30 ){
            return intval($dDay/30) . '个月前';
        }
        //full: Y-m-d , H:i:s
    }elseif($type=='full'){
        return date("Y-m-d , H:i:s",$sTime);
    }elseif($type=='ymd'){
        return date("Y-m-d",$sTime);
    }else{
        if( $dTime < 60 ){
            return $dTime."秒前";
        }elseif( $dTime < 3600 ){
            return intval($dTime/60)."分钟前";
        }elseif( $dTime >= 3600 && $dDay == 0  ){
            return intval($dTime/3600)."小时前";
        }elseif($dYear==0){
            return date("Y-m-d H:i:s",$sTime);
        }else{
            return date("Y-m-d H:i:s",$sTime);
        }
    }
}
//截取字符变省略号
function subtext($text, $length)
{
    if(mb_strlen($text, 'utf8') > $length)
        return mb_substr($text, 0, $length, 'utf8').'...';
    return $text;
}
//相对路劲转绝对
function dirToHttpUrl($file) {
    //判断文件是否存在
    if (!file_exists($file)) {
        return false;
    }
    //域名
    $nowUrl = dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);    //当前域名
    $tempUrl = explode('.', $_SERVER['HTTP_HOST']);
    $dirUrl = 'http://www.'.$tempUrl[1].'.'.$tempUrl[2].'/';                    //主域名
    //文件路径的层次统计
    $tempFile = explode('../', $file);
    $tempNum = array_count_values($tempFile);
    if (array_key_exists('', $tempNum)) {
        $fileNum = $tempNum[''];
        $fileEnd = end($tempFile);
    } else {
        $fileNum = 0;
        $fileEnd = '/'.substr($tempFile[0], 2);
    }
    //域名层次统计
    $tempWeb = explode('/', $nowUrl);
    $tempWeb = array_slice($tempWeb, 3);
    $webNum = count($tempWeb);
    //文件对应的域名
    if ($fileNum > $webNum) {
        $nowUrl = $dirUrl;
    }
    //返回
    return $nowUrl.$fileEnd;
}
//判断是否是手机端
function isMobile() {
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA'])) {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile','MicroMessenger');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}
//微信端
function isWeixin() {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    } else {
        return false;
    }
}
//判断是否在线
function writeover($filename,$data,$method = 'w',$chmod = 0){
    $handle = fopen($filename, $method);
    !handle && die("文件打开失败");
    flock($handle, LOCK_EX);
    fwrite($handle, $data);
    flock($handle, LOCK_UN);
    fclose($handle);
    $chmod && @chmod($filename, 0777);
}
//判断是否在线
function count_online_num($time, $ip) {
    $fileCount = './count.txt';
    $count = 0;
    $gap = 120; //2分钟不刷新页面就
    if (!file_exists($fileCount)) {
        $str = $time . "\t" . $ip . "\r\n";
        writeover($fileCount, $str, 'w', 1);
        $count = 1;
    } else {
        $arr = file($fileCount);
        $flag = 0;
        foreach($arr as $key => $val) {
            $val= trim($val);
            if ($val != "") {
                list($when, $seti) = explode("\t", $val);
                if ($seti ==$ip) {
                    $arr[$key] = $time . "\t" . $seti;
                    $flag = 1;
                } else {
                    $currentTime = time();
                    if ($currentTime - $when > $gap) {
                        unset($arr[$key]);
                    }else{
                        $arr[$key]=$val;
                    }
                }
            }
        }
        if ($flag == 0) {
            array_push($arr, $time . "\t" . $ip);
        }
        $count = count($arr);
        $str = implode("\r\n", $arr);
        $str.="\r\n";
        writeover($fileCount, $str, 'w', 0);
        unset($arr);
    }
    return $count;
}
/**
* 遍历目录，结果存入数组。支持php4及以上。php5以后可用scandir()函数代替while循环。
* @param string $dir
* @return array
*/
function my_scandir($dir)
{
    $files = array();
    if ( $handle = opendir($dir) ) {
        while ( ($file = readdir($handle)) !== false ) 
        {
            if ( $file != ".." && $file != "." ) 
            {
                if ( is_dir($dir . "/" . $file) ) 
                {
                    $files[$file] = my_scandir($dir . "/" . $file);
                }
                else
                {
                    $files[] = $file;
                }
            }
        }
        closedir($handle);
        return $files;
    }
}
 
function my_scandir1($dir)
{
    $files = array();
    $dir_list = scandir($dir);
    foreach($dir_list as $file)
    {
        if ( $file != ".." && $file != "." ) 
        {
            if ( is_dir($dir . "/" . $file) ) 
            {
                $files[$file] = my_scandir1($dir . "/" . $file);
            }
            else
            {
                $files[] = $file;
            }
        }
    }
     
    return $files;
}
/**
 * 创建数据表
 * @param  resource $db 数据库连接资源
 */
function create_tables($db, $prefix = '')
{
    //读取SQL文件
    $sql = file_get_contents(MODULE_PATH . 'Data/install.sql');
    $sql = str_replace("\r", "\n", $sql);
    $sql = explode(";\n", $sql);
    //替换表前缀
    $orginal = C('ORIGINAL_TABLE_PREFIX');
    $sql = str_replace(" `{$orginal}", " `{$prefix}", $sql);
    //开始安装
    show_msg('开始安装数据库...');
    foreach ($sql as $value) {
        $value = trim($value);
        if (empty($value)) {
            continue;
        }
        if (substr($value, 0, 12) == 'CREATE TABLE') {
            $name = preg_replace("/^CREATE TABLE `(\\w+)` .*/s", "\\1", $value);
            $msg = "创建数据表{$name}";
            if (false !== $db->execute($value)) {
                show_msg($msg . '...成功');
            } else {
                show_msg($msg . '...失败！', 'error');
                session('error', true);
            }
        } else {
            $db->execute($value);
        }
    }
}
/**
 * 及时显示提示信息
 * @param  string $msg 提示信息
 */
function show_msg($msg, $class = '')
{
    echo "<script type=\"text/javascript\">showmsg(\"{$msg}\", \"{$class}\")</script>";
    flush();
    ob_flush();
}
function b64dec($b64) { //64进制转换成10进制
    $map = array(
        '0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
        'A'=>10,'B'=>11,'C'=>12,'D'=>13,'E'=>14,'F'=>15,'G'=>16,'H'=>17,'I'=>18,'J'=>19,
        'K'=>20,'L'=>21,'M'=>22,'N'=>23,'O'=>24,'P'=>25,'Q'=>26,'R'=>27,'S'=>28,'T'=>29,
        'U'=>30,'V'=>31,'W'=>32,'X'=>33,'Y'=>34,'Z'=>35,'a'=>36,'b'=>37,'c'=>38,'d'=>39,
        'e'=>40,'f'=>41,'g'=>42,'h'=>43,'i'=>44,'j'=>45,'k'=>46,'l'=>47,'m'=>48,'n'=>49,
        'o'=>50,'p'=>51,'q'=>52,'r'=>53,'s'=>54,'t'=>55,'u'=>56,'v'=>57,'w'=>58,'x'=>59,
        'y'=>60,'z'=>61,'_'=>62,'='=>63
    );
    $dec = 0;
    $len = strlen($b64);
    for ($i = 0; $i < $len; $i++) {
        $b = $map[$b64{$i}];
        if ($b === NULL) {
            return FALSE;
        }
        $j = $len - $i - 1;
        $dec += ($j == 0 ? $b : (2 << (6 * $j - 1)) * $b);
    }
    return $dec;
}
function decb64($dec) { //10进制转换成64进制
    if ($dec < 0) {
        return FALSE;
    }
    $map = array(
        0=>'0',1=>'1',2=>'2',3=>'3',4=>'4',5=>'5',6=>'6',7=>'7',8=>'8',9=>'9',
		10=>'A',11=>'B',12=>'C',13=>'D',14=>'E',15=>'F',16=>'G',17=>'H',18=>'I',19=>'J',
		20=>'K',21=>'L',22=>'M',23=>'N',24=>'O',25=>'P',26=>'Q',27=>'R',28=>'S',29=>'T',
		30=>'U',31=>'V',32=>'W',33=>'X',34=>'Y',35=>'Z',36=>'a',37=>'b',38=>'c',39=>'d',
		40=>'e',41=>'f',42=>'g',43=>'h',44=>'i',45=>'j',46=>'k',47=>'l',48=>'m',49=>'n',
		50=>'o',51=>'p',52=>'q',53=>'r',54=>'s',55=>'t',56=>'u',57=>'v',58=>'w',59=>'x',
		60=>'y',61=>'z',62=>'_',63=>'=',
    );
	$b64 = '';
    do {
        $b64 = $map[($dec % 64)] . $b64;
        $dec /= 64;
    } while ($dec >= 1);
	return $b64;
	
}
//十进制转换成16进制
function tohex($num){
    $chr=[0,1,2,3,4,5,6,7,8,9,'A','B','C','D','E','F'];
    $box=[];
    $pos=0;
    while($num!=0){
        $temp=$num&15;
        $box[$pos++]=$chr[$temp];
        $num=$num>>4;
    }
    $a='';
    for($i=count($box)-1;$i>=0;$i--){
        $a.=$box[$i];
    }
    return $a;
}
//将名字按首字母进行排序
function getFirstChar($s){
$s0 = mb_substr($s,0,3); //获取名字的姓 
$s = iconv('UTF-8','gb2312', $s0); //将UTF-8转换成GB2312编码 
//dump($s0);
if (ord($s0)>128) { //汉字开头，汉字没有以U、V开头的 
$asc=ord($s{0})*256+ord($s{1})-65536; 
if($asc>=-20319 and $asc<=-20284)return "A"; 
if($asc>=-20283 and $asc<=-19776)return "B"; 
if($asc>=-19775 and $asc<=-19219)return "C"; 
if($asc>=-19218 and $asc<=-18711)return "D"; 
if($asc>=-18710 and $asc<=-18527)return "E"; 
if($asc>=-18526 and $asc<=-18240)return "F"; 
if($asc>=-18239 and $asc<=-17760)return "G"; 
if($asc>=-17759 and $asc<=-17248)return "H"; 
if($asc>=-17247 and $asc<=-17418)return "I"; 
if($asc>=-17417 and $asc<=-16475)return "J"; 
if($asc>=-16474 and $asc<=-16213)return "K"; 
if($asc>=-16212 and $asc<=-15641)return "L"; 
if($asc>=-15640 and $asc<=-15166)return "M"; 
if($asc>=-15165 and $asc<=-14923)return "N"; 
if($asc>=-14922 and $asc<=-14915)return "O"; 
if($asc>=-14914 and $asc<=-14631)return "P"; 
if($asc>=-14630 and $asc<=-14150)return "Q"; 
if($asc>=-14149 and $asc<=-14091)return "R"; 
if($asc>=-14090 and $asc<=-13319)return "S"; 
if($asc>=-13318 and $asc<=-12839)return "T"; 
if($asc>=-12838 and $asc<=-12557)return "W"; 
if($asc>=-12556 and $asc<=-11848)return "X"; 
if($asc>=-11847 and $asc<=-11056)return "Y"; 
if($asc>=-11055 and $asc<=-10247)return "Z"; 
}else if(ord($s)>=48 and ord($s)<=57){ //数字开头 
switch(iconv_substr($s,0,1,'utf-8')){ 
case 1:return "Y"; 
case 2:return "E"; 
case 3:return "S"; 
case 4:return "S"; 
case 5:return "W"; 
case 6:return "L"; 
case 7:return "Q"; 
case 8:return "B"; 
case 9:return "J"; 
case 0:return "L"; 
} 
}else if(ord($s)>=65 and ord($s)<=90){ //大写英文开头 
return substr($s,0,1); 
}else if(ord($s)>=97 and ord($s)<=122){ //小写英文开头 
return strtoupper(substr($s,0,1)); 
} 
else 
{ 
return iconv_substr($s0,0,1,'utf-8'); 
//中英混合的词语，不适合上面的各种情况，因此直接提取首个字符即可 
} 
} 
function nongli($riqi)
{
//优化修改 20160807 FXL
    $nian=date('Y',strtotime($riqi));
    $yue=date('m',strtotime($riqi));
    $ri=date('d',strtotime($riqi));

    #源码部分原作者：沈潋(S&S Lab)
    #农历每月的天数
    $everymonth=array(
        0=>array(8,0,0,0,0,0,0,0,0,0,0,0,29,30,7,1),
        1=>array(0,29,30,29,29,30,29,30,29,30,30,30,29,0,8,2),
        2=>array(0,30,29,30,29,29,30,29,30,29,30,30,30,0,9,3),
        3=>array(5,29,30,29,30,29,29,30,29,29,30,30,29,30,10,4),
        4=>array(0,30,30,29,30,29,29,30,29,29,30,30,29,0,1,5),
        5=>array(0,30,30,29,30,30,29,29,30,29,30,29,30,0,2,6),
        6=>array(4,29,30,30,29,30,29,30,29,30,29,30,29,30,3,7),
        7=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,4,8),
        8=>array(0,30,29,29,30,30,29,30,29,30,30,29,30,0,5,9),
        9=>array(2,29,30,29,29,30,29,30,29,30,30,30,29,30,6,10),
        10=>array(0,29,30,29,29,30,29,30,29,30,30,30,29,0,7,11),
        11=>array(6,30,29,30,29,29,30,29,29,30,30,29,30,30,8,12),
        12=>array(0,30,29,30,29,29,30,29,29,30,30,29,30,0,9,1),
        13=>array(0,30,30,29,30,29,29,30,29,29,30,29,30,0,10,2),
        14=>array(5,30,30,29,30,29,30,29,30,29,30,29,29,30,1,3),
        15=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,2,4),
        16=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,3,5),
        17=>array(2,30,29,29,30,29,30,30,29,30,30,29,30,29,4,6),
        18=>array(0,30,29,29,30,29,30,29,30,30,29,30,30,0,5,7),
        19=>array(7,29,30,29,29,30,29,29,30,30,29,30,30,30,6,8),
        20=>array(0,29,30,29,29,30,29,29,30,30,29,30,30,0,7,9),
        21=>array(0,30,29,30,29,29,30,29,29,30,29,30,30,0,8,10),
        22=>array(5,30,29,30,30,29,29,30,29,29,30,29,30,30,9,11),
        23=>array(0,29,30,30,29,30,29,30,29,29,30,29,30,0,10,12),
        24=>array(0,29,30,30,29,30,30,29,30,29,30,29,29,0,1,1),
        25=>array(4,30,29,30,29,30,30,29,30,30,29,30,29,30,2,2),
        26=>array(0,29,29,30,29,30,29,30,30,29,30,30,29,0,3,3),
        27=>array(0,30,29,29,30,29,30,29,30,29,30,30,30,0,4,4),
        28=>array(2,29,30,29,29,30,29,29,30,29,30,30,30,30,5,5),
        29=>array(0,29,30,29,29,30,29,29,30,29,30,30,30,0,6,6),
        30=>array(6,29,30,30,29,29,30,29,29,30,29,30,30,29,7,7),
        31=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,8,8),
        32=>array(0,30,30,30,29,30,29,30,29,29,30,29,30,0,9,9),
        33=>array(5,29,30,30,29,30,30,29,30,29,30,29,29,30,10,10),
        34=>array(0,29,30,29,30,30,29,30,29,30,30,29,30,0,1,11),
        35=>array(0,29,29,30,29,30,29,30,30,29,30,30,29,0,2,12),
        36=>array(3,30,29,29,30,29,29,30,30,29,30,30,30,29,3,1),
        37=>array(0,30,29,29,30,29,29,30,29,30,30,30,29,0,4,2),
        38=>array(7,30,30,29,29,30,29,29,30,29,30,30,29,30,5,3),
        39=>array(0,30,30,29,29,30,29,29,30,29,30,29,30,0,6,4),
        40=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,7,5),
        41=>array(6,30,30,29,30,30,29,30,29,29,30,29,30,29,8,6),
        42=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,9,7),
        43=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,10,8),
        44=>array(4,30,29,30,29,30,29,30,29,30,30,29,30,30,1,9),
        45=>array(0,29,29,30,29,29,30,29,30,30,30,29,30,0,2,10),
        46=>array(0,30,29,29,30,29,29,30,29,30,30,29,30,0,3,11),
        47=>array(2,30,30,29,29,30,29,29,30,29,30,29,30,30,4,12),
        48=>array(0,30,29,30,29,30,29,29,30,29,30,29,30,0,5,1),
        49=>array(7,30,29,30,30,29,30,29,29,30,29,30,29,30,6,2),
        50=>array(0,29,30,30,29,30,30,29,29,30,29,30,29,0,7,3),
        51=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,8,4),
        52=>array(5,29,30,29,30,29,30,29,30,30,29,30,29,30,9,5),
        53=>array(0,29,30,29,29,30,30,29,30,30,29,30,29,0,10,6),
        54=>array(0,30,29,30,29,29,30,29,30,30,29,30,30,0,1,7),
        55=>array(3,29,30,29,30,29,29,30,29,30,29,30,30,30,2,8),
        56=>array(0,29,30,29,30,29,29,30,29,30,29,30,30,0,3,9),
        57=>array(8,30,29,30,29,30,29,29,30,29,30,29,30,29,4,10),
        58=>array(0,30,30,30,29,30,29,29,30,29,30,29,30,0,5,11),
        59=>array(0,29,30,30,29,30,29,30,29,30,29,30,29,0,6,12),
        60=>array(6,30,29,30,29,30,30,29,30,29,30,29,30,29,7,1),
        61=>array(0,30,29,30,29,30,29,30,30,29,30,29,30,0,8,2),
        62=>array(0,29,30,29,29,30,29,30,30,29,30,30,29,0,9,3),
        63=>array(4,30,29,30,29,29,30,29,30,29,30,30,30,29,10,4),
        64=>array(0,30,29,30,29,29,30,29,30,29,30,30,30,0,1,5),
        65=>array(0,29,30,29,30,29,29,30,29,29,30,30,29,0,2,6),
        66=>array(3,30,30,30,29,30,29,29,30,29,29,30,30,29,3,7),
        67=>array(0,30,30,29,30,30,29,29,30,29,30,29,30,0,4,8),
        68=>array(7,29,30,29,30,30,29,30,29,30,29,30,29,30,5,9),
        69=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,6,10),
        70=>array(0,30,29,29,30,29,30,30,29,30,30,29,30,0,7,11),
        71=>array(5,29,30,29,29,30,29,30,29,30,30,30,29,30,8,12),
        72=>array(0,29,30,29,29,30,29,30,29,30,30,29,30,0,9,1),
        73=>array(0,30,29,30,29,29,30,29,29,30,30,29,30,0,10,2),
        74=>array(4,30,30,29,30,29,29,30,29,29,30,30,29,30,1,3),
        75=>array(0,30,30,29,30,29,29,30,29,29,30,29,30,0,2,4),
        76=>array(8,30,30,29,30,29,30,29,30,29,29,30,29,30,3,5),
        77=>array(0,30,29,30,30,29,30,29,30,29,30,29,29,0,4,6),
        78=>array(0,30,29,30,30,29,30,30,29,30,29,30,29,0,5,7),
        79=>array(6,30,29,29,30,29,30,30,29,30,30,29,30,29,6,8),
        80=>array(0,30,29,29,30,29,30,29,30,30,29,30,30,0,7,9),
        81=>array(0,29,30,29,29,30,29,29,30,30,29,30,30,0,8,10),
        82=>array(4,30,29,30,29,29,30,29,29,30,29,30,30,30,9,11),
        83=>array(0,30,29,30,29,29,30,29,29,30,29,30,30,0,10,12),
        84=>array(10,30,29,30,30,29,29,30,29,29,30,29,30,30,1,1),
        85=>array(0,29,30,30,29,30,29,30,29,29,30,29,30,0,2,2),
        86=>array(0,29,30,30,29,30,30,29,30,29,30,29,29,0,3,3),
        87=>array(6,30,29,30,29,30,30,29,30,30,29,30,29,29,4,4),
        88=>array(0,30,29,30,29,30,29,30,30,29,30,30,29,0,5,5),
        89=>array(0,30,29,29,30,29,29,30,30,29,30,30,30,0,6,6),
        90=>array(5,29,30,29,29,30,29,29,30,29,30,30,30,30,7,7),
        91=>array(0,29,30,29,29,30,29,29,30,29,30,30,30,0,8,8),
        92=>array(0,29,30,30,29,29,30,29,29,30,29,30,30,0,9,9),
        93=>array(3,29,30,30,29,30,29,30,29,29,30,29,30,29,10,10),
        94=>array(0,30,30,30,29,30,29,30,29,29,30,29,30,0,1,11),
        95=>array(8,29,30,30,29,30,29,30,30,29,29,30,29,30,2,12),
        96=>array(0,29,30,29,30,30,29,30,29,30,30,29,29,0,3,1),
        97=>array(0,30,29,30,29,30,29,30,30,29,30,30,29,0,4,2),
        98=>array(5,30,29,29,30,29,29,30,30,29,30,30,29,30,5,3),
        99=>array(0,30,29,29,30,29,29,30,29,30,30,30,29,0,6,4),
        100=>array(0,30,30,29,29,30,29,29,30,29,30,30,29,0,7,5),
        101=>array(4,30,30,29,30,29,30,29,29,30,29,30,29,30,8,6),
        102=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,9,7),
        103=>array(0,30,30,29,30,30,29,30,29,29,30,29,30,0,10,8),
        104=>array(2,29,30,29,30,30,29,30,29,30,29,30,29,30,1,9),
        105=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,2,10),
        106=>array(7,30,29,30,29,30,29,30,29,30,30,29,30,30,3,11),
        107=>array(0,29,29,30,29,29,30,29,30,30,30,29,30,0,4,12),
        108=>array(0,30,29,29,30,29,29,30,29,30,30,29,30,0,5,1),
        109=>array(5,30,30,29,29,30,29,29,30,29,30,29,30,30,6,2),
        110=>array(0,30,29,30,29,30,29,29,30,29,30,29,30,0,7,3),
        111=>array(0,30,29,30,30,29,30,29,29,30,29,30,29,0,8,4),
        112=>array(4,30,29,30,30,29,30,29,30,29,30,29,30,29,9,5),
        113=>array(0,30,29,30,29,30,30,29,30,29,30,29,30,0,10,6),
        114=>array(9,29,30,29,30,29,30,29,30,30,29,30,29,30,1,7),
        115=>array(0,29,30,29,29,30,29,30,30,30,29,30,29,0,2,8),
        116=>array(0,30,29,30,29,29,30,29,30,30,29,30,30,0,3,9),
        117=>array(6,29,30,29,30,29,29,30,29,30,29,30,30,30,4,10),
        118=>array(0,29,30,29,30,29,29,30,29,30,29,30,30,0,5,11),
        119=>array(0,30,29,30,29,30,29,29,30,29,29,30,30,0,6,12),
        120=>array(4,29,30,30,30,29,30,29,29,30,29,30,29,30,7,1)
    );
##############################
    #农历天干
    $mten=array("null","甲","乙","丙","丁","戊","己","庚","辛","壬","癸");
    #农历地支
    $mtwelve=array("null","子(鼠)","丑(牛)","寅(虎)","卯(兔)","辰(龙)",
        "巳(蛇)","午(马)","未(羊)","申(猴)","酉(鸡)","戌(狗)","亥(猪)");
    #农历月份
    $mmonth=array("闰","正","二","三","四","五","六",
        "七","八","九","十","十一","十二","月");
    #农历日
    $mday=array("null","初一","初二","初三","初四","初五","初六","初七","初八","初九","初十",
        "十一","十二","十三","十四","十五","十六","十七","十八","十九","二十",
        "廿一","廿二","廿三","廿四","廿五","廿六","廿七","廿八","廿九","三十");
##############################
    #星期
    $weekday = array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
    #阳历总天数 至1900年12月21日
    $total=11;
    #阴历总天数
    $mtotal=0;
##############################
    #获得当日日期
    //$today=getdate(); //获取今天的日期
    if($nian<1901 || $nian>2020) die("年份出错！");
    //$cur_wday=$today["wday"]; //星期中第几天的数字表示
    for($y=1901;$y<$nian;$y++) { //计算到所求日期阳历的总天数-自1900年12月21日始,先算年的和
        $total+=365;
        if ($y%4==0) $total++;
    }
    switch($yue) { //再加当年的几个月
        case 12:
            $total+=30;
        case 11:
            $total+=31;
        case 10:
            $total+=30;
        case 9:
            $total+=31;
        case 8:
            $total+=31;
        case 7:
            $total+=30;
        case 6:
            $total+=31;
        case 5:
            $total+=30;
        case 4:
            $total+=31;
        case 3:
            $total+=28;
        case 2:
            $total+=31;
    }
    if($nian%4 == 0 && $yue>2) $total++; //如果当年是闰年还要加一天
    $total=$total+$ri-1; //加当月的天数
    $flag1=0; //判断跳出循环的条件
    $j=0;
    while ($j<=120){ //用农历的天数累加来判断是否超过阳历的天数
        $i=1;
        while ($i<=13){
            $mtotal+=$everymonth[$j][$i];
            if ($mtotal>=$total){
                $flag1=1;
                break;
            }
            $i++;
        }
        if ($flag1==1) break;
        $j++;
    }
    if($everymonth[$j][0]<>0 and $everymonth[$j][0]<$i){ //原来错在这里，对闰月没有修补
        $mm=$i-1;
    }
    else{
        $mm=$i;
    }
    if($i==$everymonth[$j][0]+1 and $everymonth[$j][0]<>0) {
        $nlmon=$mmonth[0].$mmonth[$mm];#闰月
    }
    else {
        $nlmon=$mmonth[$mm].$mmonth[13];
    }
    #计算所求月份1号的农历日期
    $md=$everymonth[$j][$i]-($mtotal-$total);
    if($md > $everymonth[$j][$i])
        $md-=$everymonth[$j][$i];
    $nlday=$mday[$md];

    //$nowday=date("Y年n月j日 ")."w".$weekday[$cur_wday]." ".$mten[$everymonth[$j][14]].$mtwelve[$everymonth[$j][15]]."年".$nlmon.$nlday;
    $nowday=$mten[$everymonth[$j][14]].$mtwelve[$everymonth[$j][15]]."年 ".$nlmon.$nlday;
    return $nowday;
}
//下载
function DownloadAuth($Path,$DownFile,$isDeleteFile = false)
{
    $downloadfile = $Path."/".$DownFile;
    if (!file_exists($downloadfile)) {
        return -1;
    }
    // 打开文件
    $fd = fopen($downloadfile,"r");
    //输入文件标签
    Header("Content-type: application/octet-stream");
    header("Accept-Ranges: bytes");
    header("Accept-Length: ".filesize($downloadfile));
    Header( "Content-Length: " .filesize($downloadfile));
    Header("Content-Disposition: attachment; filename=$DownFile");

    while (!feof ($fd))
    { echo fread($fd,50000); }
    fclose ($fd);
}
