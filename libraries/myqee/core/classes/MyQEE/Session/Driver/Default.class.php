<?php

/**
 * MyQEE Session 缓存驱动器
 *
 * @author     jonwang(jonwang@myqee.com)
 * @category   MyQEE
 * @package    System
 * @subpackage Core
 * @copyright  Copyright (c) 2008-2012 myqee.com
 * @license    http://www.myqee.com/license.html
 */
class MyQEE_Session_Driver_Default
{

    public function __construct()
    {
        static $run = null;
        if (null===$run)
        {
            $run = true;
            @ini_set('session.gc_probability', (int)Session::$config['gc_probability']);
            @ini_set('session.gc_divisor', 100);
            @ini_set('session.gc_maxlifetime', (Session::$config['expiration'] == 0) ? 86400 : Session::$config['expiration']);

            // session保存接口
            if (isset(Session::$config['save_handler']) && Session::$config['save_handler'])
            {
                @ini_set('session.save_handler',Session::$config['save_handler']);
            }

            // session 保存目录
            if (isset(Session::$config['save_path']) && Session::$config['save_path'])
            {
                session_save_path(Session::$config['save_path']);
            }
        }

        $this->create();
    }

    /**
     * 创建Session
     *
     * @param   array  variables to set after creation
     * @return  void
     */
    public function create()
    {
        if ( preg_match('#^(?=.*[a-z])[a-z0-9_]++$#iD', Session::$config['name']) )
        {
            // Name the session, this will also be the name of the cookie
            session_name(Session::$config['name']);
        }
        $this->destroy();

        $cookieconfig = Core::config('cookie');

        # 这里对IP+非80端口的需要特殊处理下，经试验，当这种情况下，设置session id的cookie的话会失败
        if (preg_match('#^([0-9]+.[0-9]+.[0-9]+.[0-9]+):[0-9]+$#',$cookieconfig['domain'],$m))
        {
            # IP:PORT 方式
            $cookieconfig['domain'] = $m[1];
        }

        // Set the session cookie parameters
        session_set_cookie_params(Session::$config['expiration'], $cookieconfig['path'], $cookieconfig['domain'], $cookieconfig['secure'], $cookieconfig['httponly']);

        // Start the session!
        session_start();
    }

    /**
     * 获取SESSION ID
     */
    public function session_id()
    {
        return session_id();
    }

    /**
     * 回收当前Session
     *
     * @return  void
     */
    public function destroy()
    {
        if ( session_id() !== '' )
        {
            // Get the session name
            $name = session_name();

            // Destroy the session
            session_destroy();

            // Re-initialize the array
            $_SESSION = array();

            // Delete the session cookie
            Core::cookie()->delete($name,'/');
        }
    }

    /**
     * 保存Session数据
     *
     * @return  void
     */
    public function write_close()
    {
        session_write_close();
    }
}