<?php

namespace think\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option as InputOption;
use think\console\Output;

class Addons extends Command
{

    /**
     * 应用基础目录
     * @var string
     */
    protected $basePath;

    public function configure()
    {
        $this->setName('addons')
            ->addArgument('app', Argument::REQUIRED, 'app name .')
            ->addOption('--platform', '-p', InputOption::VALUE_OPTIONAL, 'The support platform.')
            ->setHelp(<<<EOT
<info>php think addons demo</info>
<info>php think addons demo --platform mp,wxapp,web</info>
<info>php think addons demo -p mp,wxapp,web</info>
EOT
            )
            ->setDescription('Build Application Demo');
    }

    public function execute(Input $input, Output $output)
    {
        $this->basePath = $this->app->getRootPath() . 'addons' . DIRECTORY_SEPARATOR;
        $app            = $input->getArgument('app') ?: '';
        $platform       = $input->getOption('platform');

        //判断应用是否存在
        if (is_dir($this->basePath . DIRECTORY_SEPARATOR . $app)) {
            throw new \InvalidArgumentException(sprintf('The application "%s" already exists', $app));
        }

        $list = [
//            '__dir__' => ['controller.admin', 'controller.index', 'controller.api', 'model', 'view'],
            '__dir__' => ['controller', 'model', 'view'],
        ];

        $this->buildApp($app, $list);
        $this->buildConfig($app, $platform);

        $output->writeln("<info>Successed</info>");
    }

    /**
     * 创建应用
     * @access protected
     * @param  string $app  应用名
     * @param  array  $list 目录结构
     * @return void
     */
    protected function buildApp(string $app, array $list = []): void
    {
        if (!is_dir($this->basePath . $app)) {
            // 创建应用目录
            mkdir($this->basePath . $app);
        }

        $appPath   = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '');
        $namespace = 'addons' . ($app ? '\\' . $app : '');

        foreach ($list as $path => $file) {
            if ('__dir__' == $path) {
                // 生成子目录
                foreach ($file as $dir) {
                    if(false !== strpos($dir , '.')){
                        $dirs = explode('.' , $dir);
                        $newDir = $appPath;
                        foreach ($dirs as $_dir){
                            $newDir .= $_dir . DIRECTORY_SEPARATOR;
                            $this->checkDirBuild($newDir);
                        }
                    }else{
                        $this->checkDirBuild($appPath . $dir);
                    }
                }
            } elseif ('__file__' == $path) {
                // 生成（空白）文件
                foreach ($file as $name) {
                    if (!is_file($appPath . $name)) {
                        file_put_contents($appPath . $name, 'php' == pathinfo($name, PATHINFO_EXTENSION) ? '<?php' . PHP_EOL : '');
                    }
                }
            } else {
                // 生成相关MVC文件
                foreach ($file as $val) {
                    $val      = trim($val);
                    $filename = $appPath . $path . DIRECTORY_SEPARATOR . $val . '.php';
                    $space    = $namespace . '\\' . $path;
                    $class    = $val;
                    switch ($path) {
                        case 'controller': // 控制器
                            if ($this->app->config->get('route.controller_suffix')) {
                                $filename = $appPath . $path . DIRECTORY_SEPARATOR . $val . 'Controller.php';
                                $class    = $val . 'Controller';
                            }
                            $content = "<?php" . PHP_EOL . "namespace {$space};" . PHP_EOL . PHP_EOL . "class {$class}" . PHP_EOL . "{" . PHP_EOL . PHP_EOL . "}";
                            break;
                        case 'model': // 模型
                            $content = "<?php" . PHP_EOL . "namespace {$space};" . PHP_EOL . PHP_EOL . "use think\Model;" . PHP_EOL . PHP_EOL . "class {$class} extends Model" . PHP_EOL . "{" . PHP_EOL . PHP_EOL . "}";
                            break;
                        case 'view': // 视图
                            $filename = $appPath . $path . DIRECTORY_SEPARATOR . $val . '.html';
                            $this->checkDirBuild(dirname($filename));
                            $content = '';
                            break;
                        default:
                            // 其他文件
                            $content = "<?php" . PHP_EOL . "namespace {$space};" . PHP_EOL . PHP_EOL . "class {$class}" . PHP_EOL . "{" . PHP_EOL . PHP_EOL . "}";
                    }

                    if (!is_file($filename)) {
                        file_put_contents($filename, $content);
                    }
                }
            }
        }

        // 创建公共文件
        $this->buildCommon($app);
        //创建菜单配置文件
        $this->buildMenus($app);
        //创建菜单配置文件
        $this->buildHello($app , $namespace);
    }

    /**
     * 创建应用模块的欢迎页面
     * @access protected
     * @param  string $app 目录
     * @return void
     */
    protected function buildHello(string $app , string $namespace): void
    {
//        $suffix   = $this->app->config->get('route.controller_suffix') ? 'Controller' : '';
//
//        foreach (['admin' => '后台' ,'index' => '前台' ,'api' => '接口' ,] as $key => $item){
//            $filename = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '') . 'controller' . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . 'Index' . $suffix . '.php';
//
//            if (!is_file($filename)) {
//                $content = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'controller.stub');
//                $content = str_replace(['{%name%}', '{%app%}', '{%layer%}', '{%suffix%}'], [$item, $namespace, 'controller\\' . $key, $suffix], $content);
//                $this->checkDirBuild(dirname($filename));
//
//                file_put_contents($filename, $content);
//            }
//        }

        $suffix   = $this->app->config->get('route.controller_suffix') ? 'Controller' : '';

        $filename = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '') . 'controller' . DIRECTORY_SEPARATOR . 'Index' . $suffix . '.php';

        if (!is_file($filename)) {
            $content = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'controller.stub');
            $content = str_replace(['{%name%}', '{%app%}', '{%layer%}', '{%suffix%}'], ['默认', $namespace, 'controller', $suffix], $content);
            $this->checkDirBuild(dirname($filename));

            file_put_contents($filename, $content);
        }
    }

    /**
     * 创建应用的配置页面
     * @access protected
     * @param  string $app 目录
     * @param  string $namespace 类库命名空间
     * @return void
     */
    protected function buildConfig(string $app, $platform = ''): void
    {
        $appPath = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '');

        $filename = $appPath . 'config.php';

        if (!is_file($filename)) {
            $content = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'config.stub');

            if($platform){
                $platform = explode(',' , $platform);
            }else{
                $platform = [];
            }

            $replace = [
                $app,
                in_array('mp' , $platform) ? 'true' : 'false',
                in_array('wxapp' , $platform) ? 'true' : 'false',
                in_array('web' , $platform) ? 'true' : 'false',
                in_array('app' , $platform) ? 'true' : 'false'
            ];

            $content = str_replace(['{%identifie%}', '{%mp%}', '{%wxapp%}', '{%web%}', '{%app%}'], $replace , $content);

            $this->checkDirBuild(dirname($filename));

            file_put_contents($filename, $content);

            //创建公众号事件接受文件
            if(in_array('mp' , $platform )){
                $filename = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '') . 'event.php';

                if (!is_file($filename)) {
                    $content = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'event.stub');
                    $content = str_replace(['{%app%}'], [$app], $content);
                    $this->checkDirBuild(dirname($filename));

                    file_put_contents($filename, $content);
                }
            }

            //创建md
            $from = __DIR__ . DIRECTORY_SEPARATOR . 'readme.md';

            $to = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '') . 'README.md';
            copy($from , $to);

            //创建图标
            $from = __DIR__ . DIRECTORY_SEPARATOR. 'stubs' .DIRECTORY_SEPARATOR . 'icon.png';

            $to = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '') . 'icon.png';
            copy($from , $to);
        }
    }

    /**
     * 创建应用的菜单配置页面
     * @access protected
     * @param  string $app 目录
     * @param  string $namespace 类库命名空间
     * @return void
     */
    protected function buildMenus(string $app): void
    {
        $filename = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '') . 'menus.php';

        if (!is_file($filename)) {
            $content = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'menus.stub');

            $this->checkDirBuild(dirname($filename));
            file_put_contents($filename, $content);
        }
    }

    /**
     * 创建应用的公共文件
     * @access protected
     * @param  string $app 目录
     * @return void
     */
    protected function buildCommon(string $app): void
    {
        $appPath = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '');

        if (!is_file($appPath . 'common.php')) {
            file_put_contents($appPath . 'common.php', "<?php" . PHP_EOL . "// 公共函数库" . PHP_EOL);
        }

        if (!is_file($appPath . 'install.sql')) {
            file_put_contents($appPath . 'install.sql', PHP_EOL);
        }

        if (!is_file($appPath . 'uninstall.sql')) {
            file_put_contents($appPath . 'uninstall.sql', PHP_EOL);
        }

        //生成入口文件
        $filename = $this->basePath . ($app ? $app . DIRECTORY_SEPARATOR : '') . 'Init.php';

        if (!is_file($filename)) {
            $content = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'init.stub');
            $content = str_replace(['{%app%}'], [$app] , $content);

            $this->checkDirBuild(dirname($filename));
            file_put_contents($filename, $content);
        }
    }

    /**
     * 创建目录
     * @access protected
     * @param  string $dirname 目录名称
     * @return void
     */
    protected function checkDirBuild(string $dirname): void
    {
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }
    }
}