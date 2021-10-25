<?php

namespace think\addons;

use think\exception\HttpException;
use think\facade\Config;
use \think\facade\Event;
use think\helper\Str;

class Route
{
    /**
     * 插件路由请求
     * @param null $addon
     * @param null $controller
     * @param null $action
     * @return mixed
     */
    public static function execute($addon = null, $controller = null, $action = null)
    {
        $app = app();
        $request = $app->request;

        Event::trigger('addons_begin', $request);

        if (empty($addon) || empty($controller) || empty($action)) {
            throw new HttpException(500, lang('Addon can not be empty'));
        }

        $request->addon = $addon;
        // 设置当前请求的控制器、操作
        $request->setController($controller)->setAction($action);

        // 获取插件基础信息
        $info = get_addons_info($addon);
        if (!$info) {
            throw new HttpException(404, lang('Addon %s not found', [$addon]));
        }

        // 监听addon_module_init
        Event::trigger('addon_module_init', $request);
        $class = get_addons_class($addon, 'controller', $controller);
        if (!$class) {
            throw new HttpException(404, lang('Addon controller %s not found', [Str::studly($controller)]));
        }

        // 生成控制器对象
        $instance = new $class($app);
        $vars = [];
        if (is_callable([$instance, $action])) {
            // 执行操作方法
            $call = [$instance, $action];
        } elseif (is_callable([$instance, '_empty'])) {
            // 空操作
            $call = [$instance, '_empty'];
            $vars = [$action];
        } else {
            // 操作不存在
            throw new HttpException(404, lang('Addon action %s not found', [get_class($instance).'->'.$action.'()']));
        }
        Event::trigger('addons_action_begin', $call);

        return call_user_func_array($call, $vars);
    }
}