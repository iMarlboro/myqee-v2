<?php
chdir(dirname(__FILE__));

# 系统目录
$dir_system  = './';

# 项目目录
$dir_project = './projects/';

# www根目录
$dir_wwwroot = './wwwroot/';

# 数据目录
$dir_data    = './data/';

# 类库目录
$dir_library = './libraries/';

# SHELL目录
$dir_shell   = './shell/';

# 临时数据目录
$dir_temp    = './temp/';


# LOG目录

$dir_log     = $dir_data.'log/';








/**

 * 服务器负载保护函数，本方法目前不支持window系统

 *

 * 最大负载不要超过3*N核，例如有16核（含8核超线程）则 16*3=48

 *

 * @see http://php.net/manual/en/function.sys-getloadavg.php

 */
function _load_protection($max_load_avg=24)
{
    global $dir_log,$dir_wwwroot;
    if ( !function_exists('sys_getloadavg') )
    {
        return false;
    }

    $load = sys_getloadavg();

    if ( !isset($load[0]) )
    {
        return false;
    }

    if ( $load[0] <= $max_load_avg )
    {
        // 未超过负载，则跳出
        return false;
    }

    $msg_tpl = "[%s] HOST:%s LOAD:%s ARGV/URI:%s\n";
    $time = @date(DATE_RFC2822);
    $host = php_uname('n');
    $load = sprintf('%.2f', $load[0]);
    if ( php_sapi_name() == "cli" || empty($_SERVER['PHP_SELF']) )
    {
        $argv_or_uri = implode(',', $argv);
    }
    else
    {
        $argv_or_uri = $_SERVER['REQUEST_URI'];
    }

    $msg = sprintf($msg_tpl, $time, $host, $load, $argv_or_uri);

    if ( @is_dir($dir_log) )
    {
        @file_put_contents( $dir_log."php-server-overload.log", $msg, FILE_APPEND );
    }

    # exit with 500 page
    header("HTTP/1.1 500 Internal Server Error");
    header("Expires: " . gmdate("D, d M Y H:i:s", time()-99999) . " GMT");
    header("Cache-Control: private");
    header("Pragma: no-cache");

    exit( file_get_contents( $dir_wwwroot.'errors/server_overload.html') );
}
_load_protection();

include $dir_library . 'bootstrap.php';

Bootstrap::setup();