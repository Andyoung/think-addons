<?php
// +----------------------------------------------------------------------
// | thinkphp5 Addons [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.zzstudio.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Byron Sampson <xiaobo.sun@qq.com>
// +----------------------------------------------------------------------

use think\Hook;
use think\Config;
use think\Loader;

// 插件目录
define('ADDON_PATH', ROOT_PATH . 'addons' . DS);

// 定义路由
\think\Route::get('addons/execute', "\\think\\addons\\AddonsController@execute");

// 如果插件目录不存在则创建
if (!is_dir(ADDON_PATH)) {
    mkdir(ADDON_PATH, 0777, true);
}

// 注册类的根命名空间
\think\Loader::addNamespace('addons', ADDON_PATH);

// 闭包初始化行为
Hook::add('action_begin', function () {
    // 获取系统配置
    $data = \think\Config::get('app_debug') ? [] : cache('hooks1');
    if (empty($data)) {
        $addons = (array)Config::get('addons');
        foreach ($addons as $key => $values) {
            if (is_string($values)) {
                $values = explode(',', $values);
            } else {
                $values = (array)$values;
            }
            $addons[$key] = array_map('get_addon_class', $values);
            \think\Hook::add($key, $addons[$key]);
        }
        cache('hooks', $addons);
    } else {
        Hook::import($data, false);
    }
});


/**
 * 处理插件钩子
 * @param string $hook 钩子名称
 * @param mixed $params 传入参数
 * @return void
 */
function hook($hook, $params = [])
{
    \think\Hook::listen($hook, $params);
}

/**
 * 获取插件类的类名
 * @param $name 插件名
 * @param string $type 返回命名空间类型
 * @return string
 */
function get_addon_class($name, $type = 'hook')
{
    switch ($type) {
        case 'controller':
            $namespace = "\\addons\\" . strtolower($name) . "\\controller";
            break;
        default:
            $namespace = "\\addons\\" . strtolower($name) . "\\" . ucfirst(strtolower($name));
    }

    return $namespace;
}

/**
 * 获取插件类的配置文件数组
 * @param string $name 插件名
 * @return array
 */
function get_addon_config($name)
{
    $class = get_addon_class($name);
    if (class_exists($class)) {
        $addon = new $class();
        return $addon->getConfig();
    } else {
        return [];
    }
}

/**
 * 插件显示内容里生成访问插件的url
 * @param $url
 * @param array $param
 * @return bool|string
 */
function addon_url($url, $param = [])
{
    $url = parse_url($url);
    $case = config('url_convert');
    $addons = $case ? Loader::parseName($url['scheme']) : $url['scheme'];
    $controller = $case ? Loader::parseName($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');

    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    /* 基础参数 */
    $params = array(
        '_addon' => $addons,
        '_controller' => $controller,
        '_action' => $action,
    );
    $params = array_merge($params, $param); //添加额外参数

    return url('@addons/execute', $params);
}