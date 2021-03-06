<?php

/**
 * MyQEE 核心类
 *
 * @author     jonwang(jonwang@myqee.com)
 * @category   MyQEE
 * @package    System
 * @subpackage Core
 * @copyright  Copyright (c) 2008-2012 myqee.com
 * @license    http://www.myqee.com/license.html
 */
abstract class MyQEE_Core extends Bootstrap
{
    /**
     * MyQEE版本号
     * @var string
     */
    const VERSION = '2.0.2';

    /**
     * 项目开发者
     * @var string
     */
    const CODER = 'jonwang(jonwang@myqee.com)';

    /**
     * 缓冲区包含数
     * @var int
     */
    protected static $buffer_level = 0;

    /**
     * 页面编码
     * @var string
     */
    public static $charset;

    /**
     * 页面传入的PATHINFO参数
     * @var array
     */
    public static $arguments;

    /**
     * 页面输出内容
     *
     * @var string
     */
    public static $output = '';

    /**
     * 自动加载时各个文件夹后缀
     *
     * 例如，classes目录为.class，则文件实际后缀为.class.php
     * 注意，只有对EXT常理设置的后缀文件有效，默认情况下EXT=.php
     *
     * @var array
     */
    public static $autoload_dir_ext = array
    (
        'config'       => '.config',
        'classes'      => '.class',
        'controllers'  => '.controller',
        'models'       => '.model',
        'orm'          => '.orm',
        'views'        => '.view',
    );

    /**
     * 页面在关闭前需要执行的方法列队
     * 通过Core::register_shutdown_function()设置
     * @var array
     */
    protected static $shutdown_function = array();

    /**
     * getFactory获取的对象寄存器
     * @var array
     */
    protected static $instances = array();

    /**
     * 执行Core::close_all_connect()方法时会关闭链接的类和方法名的列队，可通过Core::add_close_connect_class()方法进行设置增加
     *
     *   array(
     *       'Database' => 'close_all_connect',
     *   );
     *
     * @var array
     */
    protected static $close_connect_class_list = array();

    /**
     * 系统启动
     * @param string $pathinfo
     */
    public static function setup($auto_run = true)
    {
        static $run = null;
        if ( true === $run )
        {
            if ( $auto_run )
            {
                Core::run();
            }
            return;
        }
        $run = true;

        Core::$charset = Core::$config['core']['charset'];

        # 注销Bootstrap的自动加载类
        spl_autoload_unregister(array('Bootstrap', 'auto_load'));

        # 注册自动加载类
        spl_autoload_register(array('Core', 'auto_load'));

        if ( !IS_CLI )
        {
            # 输出powered by信息
            header('X-Powered-By: PHP/' . PHP_VERSION . ' MyQEE/' . Core::VERSION );
        }

        # 检查Bootstrap版本
        if ( version_compare(Bootstrap::VERSION, '1.9' ,'<') )
        {
            Core::show_500('系统Bootstrap版本太低，请先升级Bootstrap。');
            exit();
        }

        Core::debug()->info('当前项目：' . INITIAL_PROJECT_NAME);

        if ( IS_DEBUG )
        {
            Core::debug()->group('系统加载目录');
            foreach ( Core::$include_path as $value )
            {
                Core::debug()->log(Core::debug_path($value));
            }
            Core::debug()->groupEnd();
        }

        if ( (IS_CLI || IS_DEBUG ) && class_exists('ErrException', true) )
        {
            # 注册脚本
            register_shutdown_function(array('ErrException', 'shutdown_handler'));
            # 捕获错误
            set_exception_handler(array('ErrException', 'exception_handler'));
            set_error_handler(array('ErrException', 'error_handler'), error_reporting());
        }
        else
        {
            # 注册脚本
            register_shutdown_function(array('Core', 'shutdown_handler'));
            # 捕获错误
            set_exception_handler(array('Core', 'exception_handler'));
            set_error_handler(array('Core', 'error_handler'), error_reporting());
        }

        # 初始化 HttpIO 对象
        HttpIO::setup();

        # 注册输出函数
        register_shutdown_function(array('Core', 'output'));

        # 初始化类库
        Core::ini_library();

        if ( true===IS_SYSTEM_MODE )
        {
            if (false===Core::check_system_request_allow() )
            {
                # 内部请求验证不通过
                Core::show_500('system request hash error');
            }
        }

        if ( IS_DEBUG && isset($_REQUEST['debug']) && class_exists('Profiler', true) )
        {
            Profiler::setup();
        }

        if ( $auto_run )
        {
            Core::run();
        }
    }

    /**
     * 系统执行
     * 本方法只运行一次
     */
    public static function run()
    {
        static $run = null;
        if ( true === $run )
        {
            return;
        }
        $run = true;
        # 加入debug信息
        Core::debug()->log(Core::$path_info, 'PathInfo');

        Core::$arguments = explode('/', trim(Core::$path_info, '/ '));

        # 执行
        $output = HttpIO::execute(Core::$path_info, false);

        if ( false===$output )
        {
            # 抛出404错误
            Core::show_404();
            exit();
        }

        Core::$output = $output;
    }

    /**
     * 内容输出函数，只执行一次
     *
     * @param string $output
     */
    public static function output()
    {
        static $run = null;
        if ( true === $run ) return true;
        $run = true;

        # 发送header数据
        HttpIO::send_headers();

        if ( IS_DEBUG && isset($_REQUEST['debug']) && class_exists('Profiler', true) )
        {
            # 调试打开时不缓存页面
            HttpIO::set_cache_header(0);
        }

        ob_start();
        # 执行注册的关闭方法
        Core::run_shutdown_function();
        $output = ob_get_clean();

        # 在页面输出前关闭所有的连接
        Core::close_all_connect();

        # 输出内容
        echo Core::$output, $output;
    }

    /**
     * 输出执行跟踪信息
     * 注意：本方法仅用于本地跟踪代码使用，调试完毕后请移除相关调用
     *
     * @param string $msg
     * @param int $code
     */
    public static function trace($msg = 'Trace Tree', $code = E_NOTICE)
    {
        if ( IS_DEBUG )
        {
            throw new Exception($msg, $code);
            exit();
        }
    }

    /**
     * 执行注册的关闭方法
     */
    protected static function run_shutdown_function()
    {
        static $run = null;
        if ( null!==$run )
        {
            return true;
        }
        $run = true;

        if ( Core::$shutdown_function )
        {
            foreach ( Core::$shutdown_function as $item )
            {
                try
                {
                    call_user_func_array($item[0], (array)$item[1]);
                }
                catch ( Exception $e )
                {

                }
            }
        }
    }

    /**
     * 自动加载类
     *
     * @param string $class 类名称
     */
    public static function auto_load($class)
    {
        if ( class_exists($class, false) ) return true;
        $strpos = strpos($class, '_');
        if ( $strpos !== false )
        {
            $prefix = strtolower(substr($class, 0, $strpos));
        }
        else
        {
            $prefix = '';
        }
        $dir = 'classes';

        if ( $prefix )
        {
            # 处理类的前缀
            if ( $prefix=='model' )
            {
                $dir = 'models';
            }
            elseif( $prefix=='orm' )
            {
                $dir = 'orm';
            }
            elseif ( $prefix=='controller' )
            {
                # 控制器会因为环境都不同而在不同目录，有shell,controllers,admin 三个，分别为命令行下执行的，正常访问的，后台管理的
                $dir = 'controllers';
            }

            if ( $dir!='classes' )
            {
                $class = substr($class, strlen($prefix) + 1);
            }
        }

        return Core::find_file($dir, $class, null, true);
    }

    /**
     * 查找文件
     *
     * @param string $dir 目录
     * @param string $file 文件
     * @param string $ext 后缀 例如：.html
     * @param boolean $auto_require 是否自动加载上来，对config,i18n无效
     * @param string $project 跨项目读取文件
     */
    public static function find_file($dir, $file, $ext = null, $auto_require = false, $project = null)
    {
        # 寻找到的文件
        $found_files = array();

        # 处理后缀
        if ( null === $ext )
        {
            $ext = EXT;
        }
        elseif ( false === $ext || ''===$ext )
        {
            $ext = '';
        }
        elseif ( $ext[0] != '.' )
        {
            $ext = '.' . $ext;
        }

        if ( $ext === EXT && isset(Core::$autoload_dir_ext[$dir]) )
        {
            $ext = Core::$autoload_dir_ext[$dir] . EXT;
        }

        # 是否只需要寻找到第一个文件
        $only_need_one_file = true;

        if ( $dir == 'classes' || $dir == 'models' )
        {
            $file = str_replace('_', '/', $file);
        }
        elseif ( $dir == 'controllers' )
        {
            if ( IS_SYSTEM_MODE )
            {
                $dir .= '/[system]';
            }
            elseif ( IS_CLI )
            {
                $dir .= '/[shell]';
            }
            elseif ( CORE::$is_admin_url )
            {
                $dir .= '/[admin]';
            }
            $file = strtolower(str_replace('__', '/', $file));
        }
        elseif ( $dir == 'i18n' || $dir == 'config' )
        {
            $only_need_one_file = false;
        }
        elseif ( $dir == 'views' )
        {
            $file = strtolower($file);
        }
        elseif ( $dir == 'orm' )
        {
            #orm
            $file = preg_replace('#^(.*)_[a-z0-9]+$#i', '$1', $file);
            $file = str_replace('_', '/', $file);
        }

        if ( isset(Core::$file_list[Core::$project]) )
        {
            # 读取优化文件列表
            if ( isset(Core::$file_list[Core::$project][$dir . '/' . $file . $ext]) )
            {
                $found_files[] = Core::$file_list[Core::$project][$dir . '/' . $file . $ext];
            }
            elseif ( in_array($dir, array('classes', 'models', 'i18n', 'config', 'views', 'controllers', 'controllers/[shell]', 'controllers/[system]', 'controllers/[admin]')) )
            {
                return null;
            }
        }
        if ( ! $found_files )
        {
            if ( is_string($project) )
            {
                # 获取指定项目目录
                $include_path = Core::project_include_path($project);
            }
            else
            {
                # 采用当前项目目录
                $include_path = Core::$include_path;
            }

            foreach ( $include_path as $path )
            {
                if ( $dir == 'config' && Core::$config['core']['debug_config'] )
                {
                    # config 在 debug开启的情况下读取debug
                    $tmpfile_debug = $path . $dir . DS . $file . '.debug' . $ext;
                    if ( is_file($tmpfile_debug) )
                    {
                        $found_files[] = $tmpfile_debug;
                    }
                }
                $tmpfile = $path . $dir . DS . $file . $ext;
                if ( is_file($tmpfile) )
                {
                    $found_files[] = $tmpfile;
                    if ( $only_need_one_file ) break;
                }
            }
        }

        if ( $found_files )
        {
            if ( $only_need_one_file )
            {
                if ( $auto_require )
                {
                    require $found_files[0];
                }
                return $found_files[0];
            }
            else
            {
                return $found_files;
            }
        }
    }

    /**
     * 获取指定key的配置
     *
     * 若不传key，则返回Core_Config对象，可获取动态配置，例如Core::config()->get();
     *
     * @param string $key
     * @param string $project 跨项目读取配置，若本项目内的不需要传
     * @return Core_Config
     * @return array
     */
    public static function config($key = null, $project = null)
    {
        if ( null===$key )
        {
            return Core::factory('Core_Config');
        }

        if ( $project && $project!=Core::$project )
        {
            # 指定了项目且和当前项目不相同
            return Core::project_config($key, $project);
        }

        $c = explode('.', $key);
        $cname = array_shift($c);
        if ( ! array_key_exists($cname, Core::$config) )
        {
            $config = array();
            $thefiles = Core::find_file('config', $cname, null);
            if ( is_array($thefiles) )
            {
                if ( count($thefiles) > 1 )
                {
                    krsort($thefiles); //逆向排序
                }
                foreach ( $thefiles as $thefile )
                {
                    if ( $thefile )
                    {
                        Core::_include_config_file($config, $thefile);
                    }
                }
            }
            if ( ! isset(Core::$config[$cname]) )
            {
                Core::$config[$cname] = $config;
            }
        }
        $v = Core::$config[$cname];
        foreach ( $c as $i )
        {
            if ( ! isset($v[$i]) ) return null;
            $v = $v[$i];
        }
        return $v;
    }

    /**
     * Cookie
     * @return Core_Cookie
     */
    public static function cookie()
    {
        return Core::factory('Core_Cookie');
    }

    /**
     * 路由处理
     *
     * @return Core_Route
     */
    public static function route()
    {
        return Core::factory('Core_Route');
    }

    /**
     * @return Core_I18n
     */
    public static function i18n($lang = null)
    {
        return Core::factory('Core_I18n', $lang);
    }

    /**
     * 返回URL对象
     *
     * @param string $url URL，若不传，则返回的是Core_Url
     * @return Core_Url
     * @return string
     */
    public static function url($url = null)
    {
        $obj = Core::factory('Core_Url');
        if (null!==$url)
        {
            return $obj->site($url);
        }
        return $obj;
    }

    /**
     * 记录日志
     *
     * @param string $msg 日志内容
     * @param string $type 类型，例如：log,error,debug 等
     * @return boolean
     */
    public static function log($msg , $type = 'log')
    {
        # log配置
        $log_config = Core::config('log');

        # 不记录日志
        if ( isset($log_config['use']) && !$log_config['use'] )return true;

        if ($log_config['file'])
        {
        $file = date($log_config['file']);
        }
        else
        {
            $file = date('Y/m/d/');
        }
        $file .= $type.'.log';

        $dir = trim(dirname($file),'/');

        # 如果目录不存在，则创建
        if (!is_dir(DIR_LOG.$dir))
        {
            $temp = explode('/', str_replace('\\', '/', $dir) );
            $cur_dir = '';
            for( $i=0; $i<count($temp); $i++ )
            {
                $cur_dir .= $temp[$i] . "/";
                if ( !is_dir(DIR_LOG.$cur_dir) )
                {
                    @mkdir(DIR_LOG.$cur_dir,0755);
                }
            }
        }

        # 内容格式化
        if ($log_config['format'])
        {
            $format = $log_config['format'];
        }
        else
        {
            # 默认格式
            $format = ':time - :host::port - :url - :msg';
        }

        # 获取日志内容
        $data = Core::log_format($msg,$type,$format);

        if (IS_DEBUG)
        {
            # 如果有开启debug模式，输出到浏览器
            Core::debug()->log($data,$type);
        }

        # 保存日志
        return Core::write_log($file, $data, $type);
    }

    /**
    * 写入日志
    *
    * 若有特殊写入需求，可以扩展本方法（比如调用数据库类克写到数据库里）
    *
    * @param string $file
    * @param string $data
    * @param string $type 日志类型
    * @return boolean
    */
    protected static function write_log($file , $data , $type = 'log')
    {
        return @file_put_contents(DIR_LOG.$file, $data.CRLF , FILE_APPEND)?true:false;
    }

    /**
    * 用于保存日志时格式化内容，如需要特殊格式可以自行扩展
    *
    * @param string $msg
    * @param string $format
    * @return string
    */
    protected static function log_format($msg,$type,$format)
    {
        $value = array
        (
            ':time'    => date('Y-m-d H:i:s'),            //当前时间
            ':url'     => $_SERVER['SCRIPT_URI'],          //请求的URL
            ':msg'     => $msg,                            //日志信息
            ':type'    => $type,                           //日志类型
            ':host'    => $_SERVER["SERVER_ADDR"],         //服务器
            ':port'    => $_SERVER["SERVER_PORT"],         //端口
            ':ip'      => HttpIO::IP,                     //请求的IP
            ':agent'   => $_SERVER["HTTP_USER_AGENT"],     //客户端信息
            ':referer' => $_SERVER["HTTP_REFERER"],        //来源页面
        );

        return strtr($format,$value);
    }

    /**
     * 获取debug对象
     * 可安全用于生产环境，在生产环境下将忽略所有debug信息
     * @return Debug
     */
    public static function debug()
    {
        static $debug = null;
        if ( null === $debug )
        {
            if ( ! IS_CLI && IS_DEBUG && class_exists('Debug', true) )
            {
                $debug = Debug::instance();
            }
            else
            {
                $debug = new MyQEE_Core_NoDebug();
            }
        }
        return $debug;
    }

    /**
     * 将真实路径地址输出为调试地址
     *
     * 显示结果类似 SYSPATH/libraries/Database.php
     *
     * @param   string  path to debug
     * @return  string
     */
    public static function debug_path($file)
    {
        $file = str_replace('\\', DS, $file);
        if ( strpos($file, DIR_BULIDER) === 0 )
        {
            $file = 'BULIDER/' . substr($file, strlen(DIR_BULIDER));
        }
        elseif ( strpos($file, DIR_DATA) === 0 )
        {
            $file = 'DATA/' . substr($file, strlen(DIR_DATA));
        }
        elseif ( strpos($file, DIR_LIBRARY) === 0 )
        {
            $file = 'LIBRARY/' . substr($file, strlen(DIR_LIBRARY));
        }
        elseif ( strpos($file, DIR_PROJECT) === 0 )
        {
            $file = 'PROJECT/' . substr($file, strlen(DIR_PROJECT));
        }
        elseif ( strpos($file, DIR_WWWROOT) === 0 )
        {
            $file = 'WWWROOT/' . substr($file, strlen(DIR_WWWROOT));
        }
        elseif ( strpos($file, DIR_SYSTEM) === 0 )
        {
            $file = 'SYSTEM/' . substr($file, strlen(DIR_SYSTEM));
        }
        $file = str_replace('\\', '/', $file);
        return $file;
    }

    /**
     * Closes all open output buffers, either by flushing or cleaning all
     * open buffers, including the Kohana output buffer.
     *
     * @param   boolean  disable to clear buffers, rather than flushing
     * @return  void
     */
    public static function close_buffers($flush = TRUE)
    {
        if ( ob_get_level() > Core::$buffer_level )
        {
            // Set the close function
            $close = ($flush === TRUE) ? 'ob_end_flush' : 'ob_end_clean';
            while ( ob_get_level() > Core::$buffer_level )
            {
                $close();
            }
            // Reset the buffer level
            Core::$buffer_level = ob_get_level();
        }
    }

    /**
     * 404，可直接将Exception对象传给$msg
     * @param string/Exception $msg
     */
    public static function show_404($msg = null)
    {
        Core::close_buffers(false);
        HttpIO::$status = 404;
        HttpIO::send_headers();

        if ( null === $msg )
        {
            $msg = __('Page Not Found');
        }

        if ( IS_DEBUG && class_exists('ErrException', false) )
        {
            if ( $msg instanceof Exception )
            {
                throw $msg;
            }
            else
            {
                throw new Exception($msg, 43);
            }
        }

        if ( IS_CLI )
        {
            echo $msg . CRLF;
            exit();
        }

        try
        {
            $view = new View('error/404');
            $view->message = $msg;
            $view->render(true);
        }
        catch ( Exception $e )
        {
            list ( $REQUEST_URI ) = explode('?', $_SERVER['REQUEST_URI'], 2);
            $REQUEST_URI = htmlspecialchars(rawurldecode($REQUEST_URI));
            echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' .
            CRLF . '<html>' .
            CRLF . '<head>' .
            CRLF . '<title>404 Not Found</title>' .
            CRLF . '</head>'.
            CRLF . '<body>' .
            CRLF . '<h1>Not Found</h1>' .
            CRLF . '<p>The requested URL ' . $REQUEST_URI . ' was not found on this server.</p>' .
            CRLF . '<hr>' .
            CRLF . $_SERVER['SERVER_SIGNATURE'] .
            CRLF . '</body>' .
            CRLF . '</html>';
        }
        exit();
    }

    /**
     * 系统错误，可直接将Exception对象传给$msg
     * @param string/Exception $msg
     */
    public static function show_500($msg = null)
    {
        Core::close_buffers(false);
        HttpIO::$status = 500;
        HttpIO::send_headers();

        if ( null === $msg )
        {
            $msg = __('Internal Server Error');
        }

        if ( IS_DEBUG && class_exists('ErrException', false) )
        {
            if ( $msg instanceof Exception )
            {
                throw $msg;
            }
            else
            {
                throw new Exception($msg, 0);
            }
        }

        if ( IS_CLI )
        {
            echo $msg . CRLF;
            exit();
        }

        try
        {
            $view = new View('error/500');
            $error = '';
            if ( $msg instanceof Exception )
            {
                $error .= 'Msg :' . $msg->getMessage() . CRLF . "Line:" . $msg->getLine() . CRLF . "File:" . Core::debug_path($msg->getFile());
            }
            else
            {
                $error .= $msg;
            }
            $view->error = $error;

            $view->render(true);
        }
        catch ( Exception $e )
        {
            list ( $REQUEST_URI ) = explode('?', $_SERVER['REQUEST_URI'], 2);
            $REQUEST_URI = htmlspecialchars(rawurldecode($REQUEST_URI));
            echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' .
            CRLF . '<html>' .
            CRLF . '<head>' .
            CRLF . '<title>Internal Server Error</title>' .
            CRLF . '</head>' .
            CRLF . '<body>' .
            CRLF . '<h1>'.__('Internal Server Error').'</h1>' .
            CRLF . '<p>The requested URL ' . $REQUEST_URI . ' was error on this server.</p>' .
            CRLF . '<hr>' .
            CRLF . $_SERVER['SERVER_SIGNATURE'] .
            CRLF . '</body>' .
            CRLF . '</html>';
    }

        # 执行注册的shutdown方法，并忽略输出的内容
        ob_start();
        Core::run_shutdown_function();
        ob_end_clean();

        exit();
    }

    /**
     * 返回一个用.表示的字符串的key对应数组的内容
     *
     * 例如
     *    $arr = array(
     *        'a' => array(
     *        	  'b' => 123,
     *            'c' => array(
     *                456,
     *            ),
     *        ),
     *    );
     *    Core::key_string($arr,'a.b');  //返回123
     *
     *    Core::key_string($arr,'a');
     *    // 返回
     *    array(
     *       'b' => 123,
     *       'c' => array(
     *          456,
     *        ),
     *    );
     *
     *    Core::key_string($arr,'a.c.0');  //返回456
     *
     *    Core::key_string($arr,'a.d');  //返回null
     *
     * @param array $arr
     * @param string $key
     * @return fixed
     */
    public static function key_string($arr, $key)
    {
        if ( !is_array($arr) ) return null;
        $keyArr = explode('.', $key);
        foreach ( $keyArr as $key )
        {
            if ( isset($arr[$key]) )
            {
                $arr = $arr[$key];
            }
            else
            {
                return null;
            }
        }
        return $arr;
    }

    /**
     * 添加页面在关闭前执行的列队
     * 将利用call_user_func或call_user_func_array回调
     * 类似 register_shutdown_function
     * @param array $function 方法名，可以是数组
     * @param array $param_arr 参数，可空
     */
    public static function register_shutdown_function($function, $param_arr = null)
    {
        Core::$shutdown_function[] = array($function, $param_arr);
    }

    public static function shutdown_handler()
    {
        $error = error_get_last();
        if ( $error )
        {
            static $run = null;
            if ( $run === true ) return;
            $run = true;
            if ( ((E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR) & $error['type']) !== 0 )
            {
                $error['file'] = Core::debug_path($error['file']);
                Core::show_500(var_export($error, true));
                exit();
            }
        }
    }

    public static function exception_handler(Exception $e)
    {
        $code = $e->getCode();
        if ( $code !== 8 )
        {
            Core::show_500($e);
            exit();
        }
    }

    public static function error_handler($code, $error, $file = null, $line = null)
    {
        if ( (error_reporting() & $code) !== 0 )
        {
            throw new ErrorException( $error, $code, 0, $file, $line );
        }
        return true;
    }

    /**
     * 获取指定项目的指定key的配置
     *
     * @param string $key
     * @param string $project 跨项目读取配置，若本项目内的不需要传
     * @return 返回配置
     */
    protected static function project_config($key, $project)
    {
        $c = explode('.', $key);
        $cname = array_shift($c);
        if ( ! isset(Core::$config_projects[$project]) || ! array_key_exists($cname, Core::$config_projects[$project]) )
        {
            $config = array();
            $thefiles = Core::find_file('config', $cname, null, false, $project);
            if ( is_array($thefiles) )
            {
                if ( count($thefiles) > 1 )
                {
                    krsort($thefiles); //逆向排序
                }
                foreach ( $thefiles as $thefile )
                {
                    if ( $thefile )
                    {
                        include $thefile;
                    }
                }
            }
            if ( ! isset(Core::$config_projects[$project][$cname]) )
            {
                Core::$config_projects[$project][$cname] = $config;
            }
        }
        $v = Core::$config_projects[$project][$cname];
        foreach ( $c as $i )
        {
            if ( ! isset($v[$i]) ) return null;
            $v = $v[$i];
        }
        return $v;
    }

    /**
     * 获取指定项目包含的目录
     *
     * @param string $project
     */
    public static function project_include_path($project)
    {
        static $project_include_path = array();
        if ( isset($project_include_path[$project]) ) return $project_include_path[$project];

        $project_config = Core::config('core.projects.' . $project);
        if ( ! $project_config )
        {
            $project_include_path[$project] = array();
            return $project_include_path[$project];
        }

        $project_dir = realpath(DIR_PROJECT . $project_config['dir']);
        if ( ! is_dir($project_dir) )
        {
            $project_include_path[$project] = array();
            return $project_include_path[$project];
        }

        $included = array();
        $project_dir .= DS;
        $project_config_file = $project_dir . 'config' . EXT;
        if ( is_file($project_config_file) )
        {
            $config = array();
            Core::_include_config_file( $config, $project_config_file);
            if ( isset($config['autoload']) && $config['autoload'] )
            {
                $included = (array)$config['autoload'];
            }
        }
        # 自动加载配置
        if ( isset($project_config['autoload']) && is_array($project_config['autoload']) && $project_config['autoload'] )
        {
            $included = array_merge($included, $project_config['autoload']);
        }
        if ( isset($config['excluded']) && $config['excluded'] )
        {
            # 排除的目录
            if ( ! is_array($config['excluded']) )
            {
                $config['excluded'] = array($config['excluded']);
            }
            $included = array_diff($included, $config['excluded']);
        }
        $library_dir = array($project_dir);

        foreach ( $included as $path )
        {
            if ( $path[0] == '/' || preg_match('#^[a-z]:(\\|/).*$#', $path) )
            {
                $path = realpath($path);
            }
            else
            {
                $path = realpath(DIR_LIBRARY . $path);
            }
            if ( $path )
            {
                $library_dir[] = $path . DS;
            }
        }
        # 系统核心库
        $core_path = realpath(DIR_LIBRARY . 'MyQEE/Core');
        if ( $core_path )
        {
            $library_dir[] = $core_path . DS;
        }
        # 排除重复路径
        $library_dir = array_unique($library_dir);
        $project_include_path[$project] = $library_dir;
        return $project_include_path[$project];
    }

    /**
     * 根据$objName返回一个实例化并静态存储的对象
     *
     * @param string $objName
     * @param string $key
     */
    public static function factory($objName, $key = '')
    {
        if ( ! isset(Core::$instances[$objName][$key]) )
        {
            Core::$instances[$objName][$key] = new $objName($key);
        }
        return Core::$instances[$objName][$key];
    }

    /**
     * 释放对象以释放内存
     *
     * 通常在批处理后操作，可有效的释放getFactory静态缓存的对象
     *
     * @param string $objNamen 对象名称 不传的话则清除全部
     * @param string $key 对象关键字 不传的话则清除$objName里的所有对象
     */
    public static function factory_release($objName = null, $key = null)
    {
        $old_memory = memory_get_usage();
        if ( null === $objName )
        {
            Core::$instances = array();
        }
        elseif ( isset(Core::$instances[$objName]) )
        {
            if ( null === $key )
            {
                unset(Core::$instances[$objName]);
            }
            else
            {
                unset(Core::$instances[$objName][$key]);
            }
        }

        if ( IS_CLI )
        {
            echo '本次释放内存：' . ( memory_get_usage() - $old_memory ) . "\n";
        }
        else if ( IS_DEBUG )
        {
            Core::debug()->info('本次释放内存：' . ( memory_get_usage() - $old_memory) );
        }
    }

    /**
     * 包含一个指定的项目
     *
     * 会合并待加入项目的配置（当前项目配置优先），本方法只允许执行一次
     *
     * [!!] 请确保加入的项目和当前项目没有冲突，否则会出现程序异常
     *
     * @param string $project
     */
    public static function include_project( $project )
    {
        static $run = null;
        if ( null!==$run )return false;
        $run = true;

        /*
        下面主要合并两个项目的配置，和重新整理2个项目的包含目录
        例如，当前项目包含目录为
        array(
        	'a',	//通常第一个都是项目目录
        	'b',
        	'c',	//Core目录
        )
        待加入的项目包含目录为
        array(
        	'aa',
        	'bb',
        	'cc',	//Core目录
        	'c'		//Core目录
        )
        则整理后目录为
        array(
        	'a',
        	'b',
        	'aa',
        	'bb',
        	'c',
        	'cc,
        )
        若当前项目的目录和待加入项目的目录优先级存在差异时，则按当前项目优先级处理
         */

        # 切换到指定项目
        Core::set_project($project);
        $p_libs = Core::$include_path;

        $p_project_dir = array(array_shift($p_libs));
        $p_core_dir = array();
        if ( self::$project_config['libraries']['core'] && is_array(self::$project_config['libraries']['core']) )
        {
            foreach ( self::$project_config['libraries']['core'] as $item )
            {
                $core_path = realpath( DIR_LIBRARY . $item );
                if ( $core_path )
                {
                    $p_core_dir[] = $core_path . DS;
                }
            }
        }
        # 将核心的类库排除掉
        $p_libs = array_diff($p_libs, $p_core_dir);
        $p_config = Core::$project_config;

        # 返回项目
        Core::reset_project();

        # 将新项目的配置合并到当前项目，当前项目的配置优先
        Core::$project_config = Core::_merge_project_config( Core::$project_config , $p_config );

        $core_dir = array();
        if ( self::$project_config['libraries']['core'] && is_array(self::$project_config['libraries']['core']) )
        {
            foreach ( self::$project_config['libraries']['core'] as $item )
            {
                $core_path = realpath( DIR_LIBRARY . $item );
                if ( $core_path )
                {
                    $core_dir[] = $core_path . DS;
                }
            }
        }
        # 将核心的类库排除掉
        $new_libs = array_diff(Core::$include_path, $core_dir);

        //                      移除掉核心目录 , 待加入项目目录   ， 项目核心目录 ，待加入项目和当前项目差异的核心目录
        $new_libs = array_merge($new_libs    , $p_project_dir ,  $core_dir  , array_diff($p_core_dir,$core_dir) );

        # 排除重复路径
        $new_libs = array_unique( $new_libs );

        Core::$include_path = $new_libs;

        return true;
    }

    /**
     * 特殊的合并项目配置
     *
     * 相当于一维数组之间相加，这里支持多维
     *
     * @param array $c1
     * @param array $c2
     * @return array
     */
    protected static function _merge_project_config( $c1, $c2 )
    {
        foreach ( $c2 as $k=>$v )
        {
            if (!isset($c1[$k]))
            {
                $c1[$k] = $v;
            }
            elseif ( is_array($c1[$k]) && is_array($v) )
            {
                $c1[$k] = Core::_merge_project_config($c1[$k] , $v );
            }
            elseif (is_numeric($k) && is_array($c1[$k]))
            {
                $c1[$k][] = $v;
            }
        }
        return $c1;
    }

    /**
     * 动态加入指定类库
     *
     *    Core::import_library('com.myqee.test');
     *
     * [!!] 类库被载入后不可移除，类库将被加在项目目录之下的最高优先级，若已经包含了目录则不会加入，且不会调整原先的优先级
     *
     * 例如，原来的目录是
     * array('a','b','c'); 其中a是项目目录，则如果加入d的话，最后的结果将是array('a','d','b','c');
     *
     * @param string $lib
     */
    public static function import_library($lib)
    {
        $dir = DIR_LIBRARY . str_replace('.',DS,substr(trim($lib),4));
        $lib_dir = realpath( $dir );
        if ( !$lib_dir )
        {
            return false;
        }
        $lib_dir .= DS;
        $old_include_path = Core::$include_path;
        if ( in_array($lib_dir, $old_include_path ) )
        {
            # 已经存在
            return true;
        }
        $new_include_path = array( array_shift($old_include_path) );
        $new_include_path[] = $lib_dir;
        $new_include_path = array_merge($new_include_path, $old_include_path);

        self::$include_path = $new_include_path;

        # 加载类库初始化文件
        Core::ini_library();

        return true;
    }

    /**
     * 执行初始化类库
     */
    protected static function ini_library()
    {
        foreach (Core::$include_path as $path)
        {
            $file = $path . 'ini' . EXT;
            if (is_file($file))
            {
                Core::_include_file($file,true);
            }
        }
    }

    /**
     * 包含文件
     *
     * @param string $file
     * @param boolean $is_once
     */
    protected static function _include_file($file,$is_once=true)
    {
        if ($is_once)
        {
            include_once $file;
        }
        else
        {
            include $file;
        }
    }

    /**
     * 关闭所有可能的外部链接，比如Database,Memcache等连接
     */
    public static function close_all_connect()
    {
        foreach ( Core::$close_connect_class_list as $class_name=>$fun )
        {
            try
            {
                call_user_func_array( array($class_name,$fun), array() );
            }
            catch (Exception $e)
            {
                Core::debug()->error( 'close_all_connect error:'.$e->getMessage() );
            }
        }
    }

    /**
     * 增加执行Core::close_all_connect()时会去关闭的类
     *
     *    Core::add_close_connect_class('Database','close_all_connect');
     *    Core::add_close_connect_class('Cache_Driver_Memcache');
     *    Core::add_close_connect_class('TestClass','close');
     *    //当执行 Core::close_all_connect() 时会调用 Database::close_all_connect() 和 Cache_Driver_Memcache::close_all_connect() 和 TestClass::close() 方法
     *
     * @param string $class_name
     * @param string $fun
     */
    public static function add_close_connect_class($class_name,$fun='close_all_connect')
    {
        Core::$close_connect_class_list[$class_name] = $fun;
    }

    /**
     * 检查内部调用HASH是否有效
     *
     * @return boolean
     */
    protected static function check_system_request_allow()
    {
        $hash = $_SERVER['HTTP_X_MYQEE_SYSTEM_HASH']; // 请求验证HASH
        $time = $_SERVER['HTTP_X_MYQEE_SYSTEM_TIME']; // 请求验证时间
        $rstr = $_SERVER['HTTP_X_MYQEE_SYSTEM_RSTR']; // 请求随机字符串
        if ( !$hash || !$time || !$rstr ) return false;

        // 请求时效检查
        if ( microtime(1) - $time > 600 )
        {
            Core::log('system request timeout', 'system-request');
            return false;
        }

        // 验证IP
        if ( '127.0.0.1' != HttpIO::IP && HttpIO::IP != $_SERVER["SERVER_ADDR"] )
        {
            $allow_ip = Core::config('core.system_exec_allow_ip');

            if ( is_array($allow_ip) && $allow_ip )
            {
                $allow = false;
                foreach ( $allow_ip as $ip )
                {
                    if ( HttpIO::IP == $ip )
                    {
                        $allow = true;
                        break;
                    }

                    if ( strpos($allow_ip, '*') )
                    {
                        // 对IP进行匹配
                        if ( preg_match('#^' . str_replace('\\*', '[^\.]+', preg_quote($allow_ip, '#')) . '$#', HttpIO::IP) )
                        {
                            $allow = true;
                            break;
                        }
                    }
                }

                if ( ! $allow )
                {
                    Core::log('system request not allow ip:' . HttpIO::IP, 'system-request');
                    return false;
                }
            }
        }

        $body = http_build_query(HttpIO::POST(null, HttpIO::PARAM_TYPE_OLDDATA));

        // 系统调用密钥
        $system_exec_pass = Core::config('core.system_exec_key');

        if ( $system_exec_pass && strlen($system_exec_pass) >= 10 )
        {
            // 如果有则使用系统调用密钥
            $newhash = sha1($body . $time . $system_exec_pass . $rstr);
        }
        else
        {
            // 没有，则用系统配置和数据库加密
            $newhash = sha1($body . $time . serialize(Core::config('core')) . serialize(Core::config('database')) . $rstr);
        }

        if ( $newhash == $hash )
        {
            return true;
        }
        else
        {
            Core::log('system request hash error', 'system-request');
            return false;
        }
    }
}