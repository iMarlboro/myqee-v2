<?php

/**
 * 后台管理功能基础控制器
 *
 * @author jonwang
 *
 */
class Controller_MyQEE__Admin extends Controller
{
    /**
     * 页面标题
     *
     * @var string
     */
    protected $page_title;

    /**
     * 导航目录
     *
     * 数组形式
     *   array(
     *   	'目录1',
     *   	array(
     *   		'innerHTML'=>'目录2',
     *   	),
     *   	array(
     *   		'innerHTML'=>'目录3',
     *   		'href' => 'url2',
     *   	),
     *   	'目录4',
     *   )
     *
     * @var array
     */
    protected $location;

    /**
     * 快速菜单
     *
     * array(
     *     'test/url1' => '测试菜单',
     *     'test/url12' => '测试菜单2',
     * )
     *
     * @var array
     */
    protected $quick_menu;

    function __construct()
    {
        $this->check_login();
    }

    /**
     * 检查是否登录
     */
    protected function check_login()
    {
        $session = $this->session();
        $member = $session->member();

        try
        {
            if ( !$member->id>0 )
            {
                throw new Exception('请先登录');
            }

            # 超时时间
            if ( ( ($admin_login_expired_time = Core::config('admin.core.admin_login_expired_time'))>0 && TIME-$admin_login_expired_time>$session->last_actived_time() ) )
            {
                throw new Exception('登录超时，请重新登录');
            }

            if ( $member->password!=$_SESSION['member_password'] )
            {
                throw new Exception('此号密码已更新，请重新登录');
            }

            if ( !$member->setting['only_self_login'] && $session->id()!=$member->last_login_session_id )
            {
                # 如果设置为仅仅可单人登录，若发现最后登录session id和当前登录session不一致，则取消此用户登录，并输出相应信息
                throw new Exception('此号已在其它地方登录，登录IP:'.$member->last_login_ip . ' (' .IpSource::get($member->last_login_ip) .')，登录时间:'.date('Y-m-d H:i:s',$member->last_login_time));
            }

        }
        catch (Exception $e)
        {
            if ( HttpIO::IS_AJAX )
            {
                # AJAX 请求
                $this->message($e->getMessage(),-1);
            }
            else
            {
                # 正常页面请求

                # 记录错误消息
                $session->set_flash('admin_member_login_message',$e->getMessage());

                # 页面跳转
                $this->redirect( Core::url('login/?forward='.urlencode($_SERVER['REQUEST_URI'].($_SERVER['QUERY_STRING']?'?'.$_SERVER['QUERY_STRING']:''))) );
            }
            exit;
        }

    }

    public function before()
    {
        # 记录访问日志
        if ( HttpIO::METHOD=='POST' )
        {
            Database::instance(Model_Admin::DATABASE)->insert( Core::config('admin/log.tablename'),
                array(
                    'uri'      => $_SERVER["REQUEST_URI"],
                	'type'     => 'log',
                    'ip'       => HttpIO::IP,
                    'referer'  => $_SERVER["HTTP_REFERER"],
                    'post'     => serialize($_POST),
                    'admin_id' => $this->session()->member()->id,
                )
            );
        }

        if ( !is_file(DIR_DATA . Core::$project . '/install.lock') && $install_file = Core::find_file('controllers', 'install') )
        {
            self::message('为保证系统安全请在data目录下创建安装锁文件：'.Core::$project.'/install.lock<br><br>或删除后台安装文件：'.Core::debug_path($install_file).'<br><br>设置完毕后方可进入后台',-1);
        }

        # 不允许非超管跨项目访问
        if ( $this->session()->member()->project!=Core::$project && !$this->session()->member()->is_super_admin )
        {
            self::message('通道受限，您不具备此项目的操作权限，请联系管理员',-1);
        }

        ob_start();
    }

    public function after()
    {
        $output = ob_get_clean();

        if ( !HttpIO::IS_AJAX )
        {
            $this->run_header();
            echo $output;
            $this->run_bottom();
        }
        else
        {
            echo $output;
        }
    }

    /**
     * 输出头部视图
     */
    protected function run_header()
    {
        $menu = array();
        $admin_menu = Core::config('admin/menu/'.$this->session()->member()->get_menu_config(),$this->project?$this->project:null);
        $url = Core::url( HttpIO::$uri );
        $page_title = $this->page_title;
        $location = $this->location;

        $this->header_check_perm($admin_menu);
        $menu = $this->header_get_sub_menu($admin_menu,$url);
        if ( !$menu )
        {
            # 如果还是没有，则获取首页面
            $tmp_default = current($admin_menu);
            $menu = $this->header_get_sub_menu($admin_menu,$tmp_default['href']);
            if (!$page_title)$page_title = '管理首页';
        }
        if ( !$menu ) $menu = array();
        $top_menu = current($menu);

        if (!$location || !is_array($location))
        {
            $location = array();
        }

        $this_key_len = count($menu) + count($location);

        if ( $page_title )
        {
            $location[] = $page_title;
            $this_key_len += 1;
        }
        elseif( $location )
        {
            end($location);
            $tmp_menu = current($location);
            $page_title = is_array($tmp_menu)?$tmp_menu['innerHTML']:(string)$tmp_menu;
        }
        else
        {
            $i=0;
            $tmp_menu = $admin_menu;
            foreach ($menu as $key){
                $i++;
                $tmp_menu = $tmp_menu[$key];
                if ($i==$this_key_len)
                {
                    # 获取标题
                    $page_title = strip_tags($tmp_menu['innerHTML'],'');
                }
            }
        }

        $view = new View('admin/header');
        $view->menu       = $menu;
        $view->top_menu   = $top_menu;
        $view->page_title = $page_title;
        $view->location   = $location;
        $view->admin_menu = $admin_menu;
        $view->quick_menu = $this->quick_menu;
        $view->url        = $url;

        $view->render(true);
    }

    /**
     * 输出尾部视图
     */
    protected function run_bottom()
    {
        $view = new View('admin/bottom');
        $view->render(true);
    }

    public function message($msg,$code=0,$outdata=null)
    {
        if (HttpIO::IS_AJAX)
        {
            if ( is_array($msg) )
            {
                $data = $msg;
            }
            else
            {
                $data = array(
                    'code' => $code,
                    'msg' => (string)$msg,
                );
            }
            if (is_array($outdata))foreach ($outdata as $k=>$v)
            {
                $data[$k] = $v;
            }
            @header('Content-Type:application/json');
            echo json_encode($data);
        }
        else
        {
            echo '<div style="padding:6px">';
            echo $msg;
            echo '</div>';
        }
        $this->after();
        exit;
    }

    /**
     * 获取子目录
     *
     * @param array $admin_menu
     * @param string $url
     * @param int $found
     */
    protected function header_get_sub_menu( array $admin_menu , $url , & $found=-1 )
    {
        $menu = array();
        $sub_menu = false;
        foreach ($admin_menu as $k=>$v)
        {
            if ( is_array($v) )
            {
                if ( isset($v['href']) && $v['href']==$url )
                {
                    # 如果当前URL和$v['href']的设置完全相同，则返回
                    $menu = array($k);
                    $found = true;
                    break;
                }
                else
                {
                    $url_len = $v['href']?strlen($v['href']):0;
                    if( (!isset($v['href']) && null===$url) || (isset($v['href']) && substr($url,0,$url_len)==$v['href']) )
                    {
                        # 如果当前URL和$v['href']的前部分相同，则记录下来
                        if ( $url_len>$found )
                        {
                            $found = $url_len;
                            $sub_menu = array($k);
                        }
                    }

                    $submenu = $this->header_get_sub_menu( $v, $url,$found );
                    if ( $submenu )
                    {
                        if ( true===$found )
                        {
                            $menu = array($k);
                            $menu = array_merge($menu,$submenu);
                            break;
                        }
                        else
                        {
                            $sub_menu = array_merge(array($k),$submenu);
                        }
                    }
                }
            }
        }
        if ( $menu )
        {
            return $menu;
        }
        elseif( $sub_menu )
        {
            return $sub_menu;
        }
        else
        {
            return false;
        }
    }

    /**
     * 检查权限，将没有权限的菜单移出
     *
     * @param array $admin_menu
     */
    protected function header_check_perm( & $admin_menu)
    {
        $perm = $this->session()->member()->perm();
        $havearr = false;
        foreach ( $admin_menu as $k=>&$v )
        {
            if ( is_array($v) )
            {
                if (isset($v['perm']))
                {
                    $perm_key = $v['perm'];
                    unset($v['perm']);
                    if ( false!==strpos($perm_key,'||') )
                    {
                        $perm_key = explode('||', $perm_key);
                        $have_perm = false;
                        foreach ($perm_key as $p)
                        {
                            if ( $perm->is_own($p) )
                            {
                                $have_perm = true;
                                continue;
                            }
                        }
                        if (!$have_perm)
                        {
                            unset($admin_menu[$k]);
                            continue;
                        }
                    }
                    elseif ( false!==strpos($perm_key,'&&') )
                    {
                        $perm_key = explode('&&', $perm_key);
                        foreach ($perm_key as $p)
                        {
                            if ( !$perm->is_own($p) )
                            {
                                unset($admin_menu[$k]);
                                continue 2;
                            }
                        }
                    }
                    else
                    {
                        # 检查权限
                        if ( !$perm->is_own($perm_key) )
                        {
                            unset($admin_menu[$k]);
                            continue;
                        }
                    }
                }
                if ( false===$this->header_check_perm( $v ) )
                {
                    unset($admin_menu[$k]);
                }
                else
                {
                    $havearr = true;
                }
            }
            elseif ( $k=='href' )
            {
                if ( $v !='#' && !preg_match('#^[a-z0-9]+://.*$#', $v) )
                {
                    $v = (string)Core::url($v);
                }
            }

        }
        if ( false==$havearr && (!isset($admin_menu['href']) || $admin_menu['href']=='#' ) )
        {
            return false;
        }
    }
}