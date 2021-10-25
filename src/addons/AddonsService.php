<?php
declare(strict_types=1);

namespace think\addons;

use think\Route;
use think\facade\Config;
use think\middleware\Addons;

/**
 * 应用服务
 * Class Service
 * @package think\service
 */
class AddonsService extends \think\Service
{
    protected $addonsPath;

    public function register()
    {
        $this->addonsPath = $this->getAddonsPath();

        // 绑定插件容器
        $this->app->bind('addons', AddonsService::class);
    }

    public function boot()
    {
        $this->registerRoutes(function (Route $route) {
            // 路由脚本
            $execute = '\\think\\addons\\Route@execute';

            // 注册控制器路由
            $route->rule("addons/:addon/:controller/:action$", $execute)->middleware(Addons::class);
            // 自定义路由
            $routes = (array) Config::get('addons.route', []);
            foreach ($routes as $key => $val) {
                if (!$val) {
                    continue;
                }
                if (is_array($val)) {
                    $domain = $val['domain'];
                    $rules = [];
                    foreach ($val['rule'] as $k => $rule) {
                        [$addon, $controller, $action] = explode('/', $rule);
                        $rules[$k] = [
                            'addons'        => $addon,
                            'controller'    => $controller,
                            'action'        => $action,
                            'indomain'      => 1,
                        ];
                    }
                    $route->domain($domain, function () use ($rules, $route, $execute) {
                        // 动态注册域名的路由规则
                        foreach ($rules as $k => $rule) {
                            $route->rule($k, $execute)
                                ->name($k)
                                ->completeMatch(true)
                                ->append($rule);
                        }
                    });
                } else {
                    list($addon, $controller, $action) = explode('/', $val);
                    $route->rule($key, $execute)
                        ->name($key)
                        ->completeMatch(true)
                        ->append([
                            'addons' => $addon,
                            'controller' => $controller,
                            'action' => $action
                        ]);
                }
            }
        });
    }

    /**
     * 获取应用路径
     * @return string
     */
    public function getAddonsPath(): string
    {
        // 初始化插件目录
        $addonsPath = $this->app->getRootPath() . 'addons' . DIRECTORY_SEPARATOR;
        // 如果插件目录不存在则创建
        if (!is_dir($addonsPath)) {
            @mkdir($addonsPath, 0755, true);
        }

        return $addonsPath;
    }

    /**
     * 获取插件的配置信息
     * @return array
     */
    public function getAddonsConfig(): array
    {
        $name = $this->app->request->addon;
        $addon = get_addons_instance($name);
        if (!$addon) {
            return [];
        }

        return $addon->getConfig();
    }

    /**
     * 获取插件的菜单信息
     * @return array
     */
    public function getAddonsMenus(): array
    {
        $name = $this->app->request->addon;
        $addon = get_addons_instance($name);
        if (!$addon) {
            return [];
        }

        return $addon->getMenus();
    }
}