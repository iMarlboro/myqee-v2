<?php
/**
 * 启动时间
 * @var mixed
 */
define( 'START_TIME', microtime( TRUE ) );

/**
 * 启动内存
 * @var int 启动所用内存
 */
define( 'START_MEMORY', memory_get_usage() );

/**
 * PHP文件后缀
 * @var string
 */
define( 'EXT', '.php' );

/**
 * 当前时间
 * @var integer
 */
define( 'TIME', time() );

/**
 * 目录分隔符
 * @var string
 */
define( 'DS', DIRECTORY_SEPARATOR );

/**
 * 是否WIN系统
 * @var string
 */
define( 'IS_WIN', DS === '\\' ? true : false );

/**
 * 换行符
 * @var string
 */
define( 'CRLF', "\r\n" );

/**
 * 服务器是否支持mbstring
 *
 * @var boolean
 */
define('IS_MBSTRING',extension_loaded('mbstring')?true:false);

/**
 * 是否命令行执行
 *
 * @var boolean
 */
define('IS_CLI',(PHP_SAPI==='cli'));

/**
 * 是否系统调用模式
 *
 * @var boolean
 */
define('IS_SYSTEM_MODE', !IS_CLI && isset($_SERVER['HTTP_X_MYQEE_SYSTEM_HASH']) ? true : false);


if (false) $dir_system = $dir_project = $dir_wwwroot = $dir_data = $dir_library = $dir_bulider = $dir_shell = $dir_temp = $dir_assets = $dir_log = null;
if (!isset($dir_system)) $dir_system = dirname( __FILE__ ) . '/../';

/**
 * 系统目录
 * @var string
 */
define( 'DIR_SYSTEM', realpath( $dir_system ) . DS );

if ( !isset( $dir_project ) ) $dir_project = DIR_SYSTEM . 'projects/';
if ( !isset( $dir_wwwroot ) ) $dir_wwwroot = DIR_SYSTEM . 'wwwroot/';
if ( !isset( $dir_data    ) ) $dir_data    = DIR_SYSTEM . 'data/';
if ( !isset( $dir_library ) ) $dir_library = DIR_SYSTEM . 'libraries/';
if ( !isset( $dir_bulider ) ) $dir_bulider = DIR_SYSTEM . 'bulider/';
if ( !isset( $dir_shell   ) ) $dir_shell   = DIR_SYSTEM . 'shell/';
if ( !isset( $dir_temp    ) ) $dir_temp    = DIR_SYSTEM . 'temp/';
if ( !isset( $dir_assets  ) ) $dir_assets  = $dir_wwwroot.'assets/';
if ( !isset( $dir_log     ) ) $dir_log     = $dir_data  . 'log/';

if ( ! realpath( $dir_project ) )
{
    $dir_project = dirname( __FILE__ ) . DS . '../';
}
/**
 * 项目目录
 * @var string
 */
define( 'DIR_PROJECT', (realpath( $dir_project ) ? realpath( $dir_project ) : DIR_SYSTEM . 'projects') . DS );

/**
 * WWWROOR根目录
 * @var string
 */
define( 'DIR_WWWROOT', (realpath( $dir_wwwroot ) ? realpath( $dir_wwwroot ) : DIR_SYSTEM . 'wwwroot') . DS );

/**
 * 数据目录
 * @var string
 */
define( 'DIR_DATA', (realpath( $dir_data ) ? realpath( $dir_data ) : DIR_SYSTEM . 'data') . DS );

/**
 * 库文件目录
 * @var string
 */
define( 'DIR_LIBRARY', (realpath( $dir_library ) ? realpath( $dir_library ) : DIR_SYSTEM . 'libraries') . DS );

/**
 * 系统构建数据目录
 * @var string
 */
define( 'DIR_BULIDER', (realpath( $dir_bulider ) ? realpath( $dir_bulider ) : DIR_SYSTEM . 'bulider') . DS );

/**
 * 系统构建数据目录
 * @var string
 */
define( 'DIR_SHELL', (realpath( $dir_shell ) ? realpath( $dir_shell ) : DIR_SYSTEM . 'shell') . DS );

/**
 * 临时文件目录
 * @var string
 */
define( 'DIR_TEMP', (realpath( $dir_temp ) ? realpath( $dir_temp ) : DIR_SYSTEM . 'temp') . DS );

/**
 * 临时文件目录
 * @var string
 */
define( 'DIR_ASSETS', (realpath( $dir_assets ) ? realpath( $dir_assets ) : DIR_WWWROOT . 'assets') . DS );

/**
 * LOG目录
 * @var string
 */
define( 'DIR_LOG', (realpath( $dir_log ) ? realpath( $dir_log ) : DIR_DATA . 'log') . DS );


unset( $dir_system, $dir_project, $dir_wwwroot, $dir_data, $dir_library, $dir_shell, $dir_bulider,$dir_assets );

function __load_boot__()
{
    if ( !Bootstrap::$include_path )
    {
        # 当在项目初始化之前发生错误（比如项目不存在），调用系统Core类库
        Bootstrap::$include_path = array
        (
        	DIR_LIBRARY . 'MyQEE' . DS . 'Core' . DS,
    	);

    	# 注册自动加载类
        spl_autoload_register( array( 'Bootstrap', 'auto_load' ) );
    }
}


/**
 * 语言包
 * [strtr](http://php.net/strtr) is used for replacing parameters.
 *
 * __('Welcome back, :user', array(':user' => $username));
 *
 * @uses	I18n::get
 * @param	string  text to translate
 * @param	array   values to replace in the translated text
 * @param	string  target language
 * @return	string
 */
function __( $string, array $values = NULL )
{
    static $have_core = false;
    if ( false===$have_core )
    {
        __load_boot__();
        $have_core = (boolean)class_exists('Core',true);
    }
    if ($have_core)
    {
        $string = Core::i18n()->get( $string );
    }

    return empty( $values ) ? $string : strtr( $string, $values );
}

/**
 * 是否对传入参数转义，建议系统关闭自动转义功能
 * @var boolean
 */
define( 'MAGIC_QUOTES_GPC', get_magic_quotes_gpc() );

if ( MAGIC_QUOTES_GPC )
{

    function _stripcslashes( $string )
    {
        if ( is_array( $string ) )
        {
            foreach ( $string as $key => $val )
            {
                $string[$key] = _stripcslashes( $val );
            }
        }
        else
        {
            $string = stripcslashes( $string );
        }
        return $string;
    }
    $_GET = _stripcslashes( $_GET );
    $_POST = _stripcslashes( $_POST );
    $_COOKIE = _stripcslashes( $_COOKIE );
    $_REQUEST = _stripcslashes( $_REQUEST );
}

/**
 * 系统基础初始化类
 *
 * @author     jonwang(jonwang@myqee.com)
 * @category   MyQEE
 * @package    System
 * @subpackage Core
 * @copyright  Copyright (c) 2008-2012 myqee.com
 * @license    http://www.myqee.com/license.html
 */
abstract class Bootstrap
{

    /**
     * 版本号
     *
     * @var float
     */
    const VERSION = '1.9.1.1';

    /**
     * 系统所在的根目录
     *
     * @var string
     */
    public static $base_url = null;

    /**
     * 系统配置
     *
     * @var array
     */
    protected static $config = array();

    /**
     * 所有项目的config配置
     *
     * array('projectName'=>array(...))
     * @var array
     */
    protected static $config_projects = array();

    /**
     * 当前URL的PATH_INFO
     *
     * @var string
     */
    public static $path_info = null;

    /**
     * 当前项目
     *
     * @var string
     */
    public static $project;

    /**
     * 当前项目配置
     *
     * @var array
     */
    public static $project_config;

    /**
     * 当前项目目录
     *
     * @var string
     */
    public static $project_dir;

    /**
     * 当前项目的URL
     *
     * @var string
     */
    public static $project_url;

    /**
     * 系统文件列表
     *
     * @var array('project_name'=>array(...))
     */
    public static $file_list = array();

    /**
     * 当前请求是否admin类型请求
     *
     * @var boolean
     */
    public static $is_admin_url = false;

    /**
     * 当前请求的URL的路径索引
     *
     * @var int
     */
    public static $curren_uri_index = 0;

    /**
     * 包含目录
     *
     * array(
     * 	 'test1',
     * 	 'test2',
     * )
     *
     * @var array
     */
    public static $include_path;

    /**
     * 自动加载类
     * @param string $class 类名称
     */
    public static function auto_load( $class )
    {
        if ( class_exists( $class, false ) ) return true;
        static $core_loaded = false;
        if ( ! $core_loaded )
        {
            $core_loaded = class_exists( 'Core', false );
        }
        if ( $core_loaded )
        {
            # 如果Core已加载则采用Core的auto_load方法
            return Core::auto_load( $class );
        }

        $file = 'classes/' . str_replace( '_','/', $class ) . '.class' . EXT;
        if ( isset(self::$file_list[self::$project]) )
        {
            # 读取优化文件列表
            if ( isset( self::$file_list[self::$project][$file] ) )
            {
                require self::$file_list[self::$project][$file];
                return true;
            }
            else
            {
                return false;
            }
        }

        foreach ( self::$include_path as $path )
        {
            $tmpfile = $path . $file;
            if ( is_file( $tmpfile ) )
            {
                require $tmpfile;
                return true;
            }
        }
        return false;
    }

    /**
     * 系统启动函数
     */
    public static function setup( $auto_run = true )
    {
        static $run = null;
        if ( true === $run )
        {
            if ( true === $auto_run )
            {
                Core::setup( true );
            }
            return true;
        }
        $run = true;

        # 读取系统配置
        if ( !is_file( DIR_SYSTEM . 'config' . EXT ) )
        {
            self::_throw_sys_error_msg( __('Please rename the file config.new:EXT to config:EXT' , array(':EXT'=>EXT)) );
        }
        self::_include_config_file( self::$config['core'] , DIR_SYSTEM.'config'.EXT );

        # 本地调试模式
        if ( isset( self::$config['core']['local_debug_cfg'] ) && self::$config['core']['local_debug_cfg'] )
        {
            # 判断是否开启了本地调试
            if ( function_exists('get_cfg_var') )
            {
                $open_debug = get_cfg_var( self::$config['core']['local_debug_cfg'] ) ? 1 : 0;
            }
            else
            {
                $open_debug = 0;
            }
        }
        else
        {
            $open_debug = 0;
        }

        # 读Debug配置
        if ( $open_debug && is_file(DIR_SYSTEM.'debug.config'.EXT) )
        {
            # 本地debug配置打开
            self::_include_config_file( self::$config['core'] , DIR_SYSTEM.'debug.config'.EXT );
        }

        # 在线调试
        if ( self::is_online_debug() )
        {
            $open_debug = 1<<1 | $open_debug;
        }

        /**
         * 是否开启DEBUG模式
         *
         *     if (IS_DEBUG>>1)
             *     {
         *         //开启了在线调试
         *     }
         *
         *     if (IS_DEBUG & 1)
             *     {
         *         //本地调试打开
         *     }
         *
         *     if (IS_DEBUG)
             *     {
         *         // 开启了调试
         *     }
         *
         * @var int
         */
        define('IS_DEBUG', $open_debug);


        if ( !IS_CLI )
        {
            # 输出文件头
            header( 'Content-Type: text/html;charset=' . self::$config['core']['charset'] );
        }

        if ( !isset( self::$config['core']['projects'] ) || !self::$config['core']['projects'] )
        {
            self::_throw_sys_error_msg( __('Please create a new project.') );
        }

        if ( isset(self::$config['core']['base_url']) && null!==self::$config['core']['base_url'] )
        {
            self::$base_url = self::$config['core']['base_url'];
        }

        if ( isset(self::$config['core']['url']['assets']) )
        {
            $assets_url = self::$config['core']['url']['assets'];
        }
        else
        {
            $assets_url = '/asstes/';
        }
        define('URL_ASSETS', $assets_url);
        unset($assets_url);

        $now_project = null;
        if ( IS_CLI )
        {
            if ( isset($_SERVER['OS']) && $_SERVER['OS']=='Windows_NT' )
            {
                # 切换到UTF-8编码显示状态
                exec('chcp 65001');
            }

            if ( ! isset( $_SERVER["argv"] ) )
            {
                exit( 'Err Argv' );
            }
            $argv = $_SERVER["argv"];
            //$argv[0]为文件名
            if ( isset( $argv[1] ) )
            {
                $project = $argv[1];
                if ( isset( self::$config['core']['projects'][$project] ) )
                {
                    $now_project = $project;
                    $project = self::$config['core']['projects'][$project];
                }
                else
                {
                    $project = false;
                }
            }
            else
            {
                $project = false;
            }

            array_shift( $argv ); //将文件名移除
            array_shift( $argv ); //将项目名移除
            self::$path_info = trim( implode( '/', $argv ) );
        }
        else
        {
            self::$path_info = self::_get_pathinfo();
            $project_url = false;
            foreach ( self::$config['core']['projects'] as $k => &$item )
            {
                if (!isset($item['url']))$item['url'] = array();
                if ( !is_array($item['url']) )
                {
                    $item['url'] = array((string)$item['url']);
                }
                $tmp_pathinfo = self::$path_info;
                foreach ( $item['url'] as $index=>$u )
                {
                    if ( self::_check_is_this_url( $u, self::$path_info ) )
                    {
                        $project_url = $u;
                        self::$curren_uri_index = $index;
                        break;
                    }
                }

                if ( false !== $project_url )
                {
                    if ( isset($item['url_admin']) && $item['url_admin'] )
                    {
                        if ( !strpos($item['url_admin'],'://') )
                        {
                            $tmp_pathinfo = self::$path_info;
                        }
                        if ( self::_check_is_this_url( $item['url_admin'], $tmp_pathinfo ) )
                        {
                            self::$path_info = $tmp_pathinfo;
                            self::$is_admin_url = true;
                        }
                    }

                    self::$project_url = $project_url;
                    $project_config = $item;
                    $now_project = $k;
                    break;
                }
            }
        }

        if ( !$now_project )
        {
            if ( IS_CLI )
            {
                # 命令行下执行
                echo 'use:'.CRLF;
                foreach ( self::$config['core']['projects'] as $k=>$item )
                {
                    if ( isset($item['isuse']) && !$item['isuse'] )continue;
                    echo "    ".$k.CRLF;
                }
                return true;
            }

            if ( isset( self::$config['core']['projects']['default'] ) )
            {
                $now_project = 'default';
            }
            else
            {
                self::_throw_sys_error_msg( __('not found the project: :project',array(':project'=>$now_project)) );
            }
        }

        /**
         * 初始项目名
         * @var string
         */
        define( 'INITIAL_PROJECT_NAME', $now_project );

        self::set_project( $now_project );

        # 注册自动加载类
        spl_autoload_register( array( 'Bootstrap', 'auto_load' ) );

        # 加载系统核心
        Core::setup( $auto_run );
    }

    /**
     * 设置项目
     * 可重新设置新项目已实现程序内项目切换，但需谨慎使用
     * @param string $project
     */
    public static function set_project( $project )
    {
        if ( self::$project == $project )
        {
            return true;
        }
        static $core_config = null;
        if ( null===$core_config )
        {
            # 记录原始Core配置
            $core_config = self::$config['core'];
        }

        if ( ! isset( $core_config['projects'][$project] ) )
        {
            self::_throw_sys_error_msg( __('not found the project: :project.',array(':project'=>$project) ) );
        }
        if ( ! $core_config['projects'][$project]['isuse'] )
        {
            self::_throw_sys_error_msg( __('the project: :project is not open.' , array(':project'=>'$project') ) );
        }

        # 获取core里项目配置
        $project_config = $core_config['projects'][$project];

        # 项目路径
        $project_dir = realpath( DIR_PROJECT . $project_config['dir'] );
        if ( ! $project_dir || ! is_dir( $project_dir ) )
        {
            self::_throw_sys_error_msg( __('the project dir :dir is not exist.' , array(':dir'=>$project_config['dir'])) );
        }
        $project_dir .= DS;
        self::$project_dir = $project_dir;

        # 记录所有项目设置，当切换回项目时，使用此设置还原
        static $all_prjects_setting = array();

        if ( self::$project )
        {
            # 记录上一个项目设置
            $all_prjects_setting[self::$project] = array
            (
                'config'         => self::$config,
                'project_config' => self::$project_config,
                'include_path'   => self::$include_path,
                'file_list'      => self::$file_list,
            );
        }

        # 设为当前项目
        self::$project = $project;

        # 记录debug信息
        if ( class_exists( 'Core', false ) )
        {
            Core::debug()->info( '程序已切换到了新项目：' . $project );
        }

        if ( isset($all_prjects_setting[$project]) )
        {
            # 还原配置
            self::$config         = $all_prjects_setting[$project]['config'];
            self::$project_config = $all_prjects_setting[$project]['project_config'];
            self::$include_path   = $all_prjects_setting[$project]['include_path'];
            self::$file_list      = $all_prjects_setting[$project]['file_list'];
        }
        else
        {
            # 合并配置
            $config = $core_config['projects'][$project] + self::$config['core'];

            # 读取项目配置
            if ( is_file( $project_dir . 'config' . EXT ) )
            {
                self::_include_config_file( $config, $project_dir . 'config' . EXT );
            }
            # 读取DEBUG配置
            if ( isset(self::$config['core']['debug_config']) && self::$config['core']['debug_config'] && is_file($project_dir.'debug.config'.EXT) )
            {
                self::_include_config_file( $config , $project_dir.'debug.config'.EXT );
            }

            # 清理项目配置
            self::$project_config = $config;
            self::$config = array
            (
            	'core' => & self::$project_config,
            );
            unset($config);

            # Builder构建，处理 self::$file_list
            if ( self::$project_config['use_bulider'] === 'auto' )
            {
                if ( IS_DEBUG )
                {
                    $usebulider = false;
                }
                else
                {
                    $usebulider = true;
                }
            }
            else
            {
                $usebulider = (boolean)self::$project_config['use_bulider'];
            }

            $project_filelist = DIR_BULIDER . self::$project . DS . 'project_all_files_list' . EXT;
            if ( true === $usebulider && ! IS_CLI && is_file( $project_filelist ) )
            {
                # 读取文件列表
                self::_include_config_file( self::$file_list, $project_filelist );
            }


            # 设置包含目录
            self::$include_path = self::get_project_include_path($project);
        }

        if ( isset( self::$project_config['error_reporting'] ) )
        {
            error_reporting( self::$project_config['error_reporting'] );
        }

        # 时区设置
        if ( isset( self::$project_config['timezone'] ) )
        {
            date_default_timezone_set( self::$project_config['timezone'] );
        }

        if ( class_exists('Core',false) )
        {
            # 输出调试信息
            if ( IS_DEBUG )
            {
                Core::debug()->group( '当前加载目录' );
                foreach ( self::$include_path as $value )
                {
                    Core::debug()->log( Core::debug_path( $value ) );
                }
                Core::debug()->groupEnd();
            }

            Core::ini_library();
        }
    }

    /**
     * 获取指定项目的include_path
     *
     * @param string $project
     * @return array
     */
    protected static function get_project_include_path( $project )
    {
        # 项目目录排第一个
        $library_dir = array( self::$project_dir );

        if ( IS_DEBUG )
        {
            # 调试类库目录
            $debug_libraries = null;
            if ( isset( self::$project_config['libraries']['debug'] ) )
            {
                $debug_libraries = self::$project_config['libraries']['debug'];
            }
            elseif ( isset( self::$config['core']['libraries']['debug'] ) )
            {
                $debug_libraries = self::$config['core']['libraries']['debug'];
            }
            if ( $debug_libraries )
            {
                if ( ! is_array( $debug_libraries ) )
                {
                    $debug_libraries = array( (string) $debug_libraries );
                }
                $debug_path = array();
                foreach ( $debug_libraries as $path )
                {
                    $path = str_replace('.','/',substr($path,4));
                    if ( $path[0] == '/' || preg_match( '#^[a-z]:(\\|/).*$#', $path ) )
                    {
                        $path = realpath( $path );
                    }
                    else
                    {
                        $path = realpath( DIR_LIBRARY . $path );
                    }
                    if ( $path )
                    {
                        $debug_path[] = $path . DS;
                    }
                }
                if ( $debug_path )
                {
                    # 合并
                    $library_dir = array_merge( $library_dir , $debug_path );
                }
            }
        }

        # 自动加载类库
        if ( isset( self::$project_config['libraries']['autoload'] ) )
        {
            $included = (array)self::$project_config['libraries']['autoload'];
        }
        else
        {
        	$included = array();
        }

        if ( self::$is_admin_url )
        {
            # 后台管理加载项
            if ( isset( self::$project_config['libraries']['admin'] ) && is_array( self::$project_config['libraries']['admin'] ) )
            {
                $included = array_merge($included,self::$project_config['libraries']['admin']);
            }
        }

        # 扩展配置
        if ( isset( self::$project_config['excluded'] ) && self::$project_config['excluded'] )
        {
            # 排除的目录
            if ( ! is_array( self::$project_config['excluded'] ) )
            {
                self::$project_config['excluded'] = array( self::$project_config['excluded'] );
            }
            $included = array_diff( $included, self::$project_config['excluded'] );
        }

        foreach ( $included as $path )
        {
            $path = str_replace('.','/',substr($path,4));
            if ( $path[0] == '/' || preg_match( '#^[a-z]:(\\|/).*$#', $path ) )
            {
                $path = realpath( $path );
            }
            else
            {
                $path = realpath( DIR_LIBRARY . $path );
            }
            if ( $path )
            {
                $library_dir[] = $path . DS;
            }
        }

        # 系统核心库
        $core_dir = array();
        if ( self::$project_config['libraries']['core'] && is_array(self::$project_config['libraries']['core']) )
        {
            foreach ( self::$project_config['libraries']['core'] as $path )
            {
                $path = str_replace('.','/',substr($path,4));
                $core_path = realpath( DIR_LIBRARY . $path );
                if ( $core_path )
                {
                    $core_dir[] = $core_path . DS;
                }
            }
        }
        if ( ! $core_dir )
        {
            self::_throw_sys_error_msg( __('Core library lost.<br/><br/>Please check the core config.') );
        }
        $library_dir = array_merge($library_dir,$core_dir);

        # 排除重复路径
        $library_dir = array_values( array_unique($library_dir ) );

        return $library_dir;
    }

    /**
     * 将项目切换回初始项目
     *
     * 当使用Core::set_project()设置切换过项目后，可使用此方法返回初始化时的项目
     */
    public static function reset_project()
    {
        if ( defined( 'INITIAL_PROJECT_NAME' ) && INITIAL_PROJECT_NAME != self::$project )
        {
            self::set_project( INITIAL_PROJECT_NAME );
        }
    }

    /**
     * 返回协议类型
     * 当在命令行里执行，则返回null
     * @return null/http/https
     */
    public static function protocol()
    {
        if ( IS_CLI )
        {
            return null;
        }
        elseif ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' )
        {
            return 'https';
        }
        else
        {
            return 'http';
        }
    }

    /**
     * 判断是否开启了在线调试
     *
     * @return boolean
     */
    public static function is_online_debug()
    {
        if ( IS_SYSTEM_MODE )
        {
            if ( isset($_SERVER['HTTP_X_MYQEE_SYSTEM_DEBUG']) && $_SERVER['HTTP_X_MYQEE_SYSTEM_DEBUG']=='1' )
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        if ( !isset( $_COOKIE['_debug_open'] ) ) return false;
        if ( !isset( self::$config['core']['debug_open_password'] ) ) return false;
        if ( !is_array( self::$config['core']['debug_open_password'] ) ) self::$config['core']['debug_open_password'] = array( ( string ) self::$config['core']['debug_open_password'] );
        foreach ( self::$config['core']['debug_open_password'] as $item )
        {
            if ( $_COOKIE['_debug_open'] == self::get_debug_hash( $item ) )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * 根据密码获取一个hash
     *
     * @param string $password
     * @return string
     */
    public static function get_debug_hash( $password )
    {
        static $config_str = null;
        if ( null === $config_str ) $config_str = var_export( self::$config['core']['debug_open_password'], true );
        return md5( $config_str . '_open$&*@debug' . $password );
    }

    /**
     * 获取指定config文件的数据
     *
     * @param string $file
     * @return array $config
     */
    protected static function _include_config_file( &$config , $file )
    {
        include $file;

        return $config;
    }

    /**
     * 获取path_info
     *
     * @return string
     */
    private static function _get_pathinfo()
    {
        # 处理base_url
        if ( null === self::$base_url && isset($_SERVER["SCRIPT_NAME"]) && $_SERVER["SCRIPT_NAME"] )
        {
            $base_url_len = strrpos($_SERVER["SCRIPT_NAME"],'/');
            if ( $base_url_len )
            {
                $base_url = substr($_SERVER["SCRIPT_NAME"], 0 , $base_url_len);
                if ( preg_match('#^(.*)/wwwroot$#', $base_url , $m) )
                {
                    # 特殊处理wwwroot目录
                    $base_url = $m[1];
                    $base_url_len = strlen($base_url);
                }
                if ( strtolower(substr($_SERVER['REQUEST_URI'],0,$base_url_len)) == strtolower($base_url) )
                {
                    self::$base_url = $base_url;
                }
            }
        }

        if ( isset($_SERVER['PATH_INFO']) )
        {
            $pathinfo = $_SERVER["PATH_INFO"];
        }
        else
        {
            if ( isset($_SERVER['REQUEST_URI']) )
            {
                $request_uri = $_SERVER['REQUEST_URI'];
                if ( self::$base_url )
                {
                    $request_uri = substr($request_uri, strlen(self::$base_url));
                }
                // 移除查询参数
                list ( $pathinfo ) = explode( '?', $request_uri, 2 );
            }
            elseif ( isset($_SERVER['PHP_SELF']) )
            {
                $pathinfo = $_SERVER['PHP_SELF'];
            }
            elseif ( isset($_SERVER['REDIRECT_URL']) )
            {
                $pathinfo = $_SERVER['REDIRECT_URL'];
            }
            else
            {
                $pathinfo = false;
            }
        }

        # 过滤pathinfo传入进来的服务器默认页
        if ( false !== $pathinfo && ($indexpagelen = strlen( self::$config['core']['server_index_page'] )) && substr( $pathinfo, - 1 - $indexpagelen ) == '/' . self::$config['core']['server_index_page'] )
        {
            $pathinfo = substr( $pathinfo, 0, - $indexpagelen );
        }
        if ( !isset($_SERVER["PATH_INFO"]) )
        {
            $_SERVER["PATH_INFO"] = $pathinfo;
        }
        $pathinfo = trim( $pathinfo );

        return $pathinfo;
    }

    /**
     * 检查给定的pathinfo是否属于给的的项目内的URL
     *
     * @param string $u 项目的URL路径
     * @param string $pathinfo 给定的Pathinfo
     * @return boolean
     */
    private  static function _check_is_this_url( $u, & $pathinfo )
    {
        if ( $u=='/' )
        {
            return true;
        }
        $u = rtrim( $u, '/' );
        if ( strpos( $u, '://' ) )
        {
            $tmppath = self::protocol() . '://' . $_SERVER["HTTP_HOST"] . '/' . ltrim( $pathinfo, '/' );
        }
        else
        {
            $tmppath = $pathinfo;
        }
        $len = strlen( $u );
        if ( $len > 0 && substr( $tmppath, 0, $len ) == $u )
        {
            $pathinfo = substr( $tmppath, $len );
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 抛出系统启动时错误信息
     * @param string $msg
     */
    private static function _throw_sys_error_msg( $msg )
    {
        __load_boot__();

        # 尝试加载Core类
        if ( class_exists('Core',true) )
        {
            Core::show_500($msg);
        }

        header( 'Content-Type: text/html;charset=utf-8' );

        if ( isset( $_SERVER['SERVER_PROTOCOL'] ) )
        {
            $protocol = $_SERVER['SERVER_PROTOCOL'];
        }
        else
        {
            $protocol = 'HTTP/1.1';
        }

        // HTTP status line
        header( $protocol . ' 500 Internal Server Error' );

        echo $msg;

        exit();
    }
}