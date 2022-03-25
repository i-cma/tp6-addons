<?php

declare(strict_types=1);

namespace think;

abstract class Addons
{
    // app 容器
    protected $app;

    // 请求对象
    protected $request;

    // 当前插件标识
    protected $name;

    // 插件路径
    protected $addonPath;

    // 视图模型
    protected $view;

    // 插件配置
    protected $addonConfig;

    // 插件菜单
    protected $addonMenus;

    //插件配置和菜单
    public $addonInfo;

    /**
     * 插件构造函数
     * Addons constructor.
     * @param \think\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->name = $this->getName();
        $this->addonPath   = $app->addons->getAddonsPath() . $this->name . DIRECTORY_SEPARATOR;

        $this->view = clone \think\facade\View::engine('Think');
        $this->view->config([
            'view_path' => $this->addonPath . 'view' . DIRECTORY_SEPARATOR
        ]);

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        $menusPath = $this->addonPath . 'menus.php';
        if (is_file($menusPath)) {
            $this->addonInfo['menus'] = $this->addonMenus = include $menusPath;
        }

        $configPath = $this->addonPath . 'config.php';
        if (is_file($configPath)) {
            $this->addonInfo['config'] = $this->addonConfig = include $configPath;
        }
    }

    /**
     * 获取插件标识
     * @return mixed|null
     */
    final protected function getName()
    {
        $class = get_class($this);
        list(, $name, ) = explode('\\', $class);
        $this->request->addon = $name;

        return $name;
    }

    /**
     * 加载模板输出
     * @param string $template
     * @param array $vars           模板文件名
     * @return false|mixed|string   模板输出变量
     * @throws \think\Exception
     */
    protected function fetch($template = '', $vars = [])
    {
        return $this->view->fetch($template, $vars);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param  string $content 模板内容
     * @param  array  $vars    模板输出变量
     * @return mixed
     */
    protected function display($content = '', $vars = [])
    {
        return $this->view->display($content, $vars);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign([$name => $value]);

        return $this;
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param  array|string $engine 引擎参数
     * @return $this
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);

        return $this;
    }

    /**
     * 获取插件配置信息
     * @return array|false|mixed|string
     */
    public function getConfig()
    {
        return $this->addonInfo['config'];
    }

    /**
     * 获取应用插件
     * @return array|false|mixed|string
     */
    final public function getMenus()
    {
        return $this->addonInfo['menus'];
    }

    /**
     * 获取安装SQL
     * @return false|string
     */
    public function getInstallSql()
    {
        return file_get_contents($this->addonPath . 'install.sql');
    }

    /**
     * 获取卸载SQL
     * @return false|string
     */
    public function getUninstallSql()
    {
        return file_get_contents($this->addonPath . 'uninstall.sql');
    }
}