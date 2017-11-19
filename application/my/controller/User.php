<?php
namespace app\my\controller;

use app\home\logic\UsersLogic;
use app\home\model\Message;
use app\common\logic\OrderLogic;
use think\Page;
use think\Request;
use think\Verify;
use think\db;

class User extends MobileBase
{

    public $user_id = 0;
    public $user = array();

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
        }
       
        $this->assign('order_status_coment', $order_status_coment);
    }

    /*
     * 用户中心首页
     */
    public function index()
    {
        $user_id =$this->user_id;
        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        $comment_count = M('comment')->where("user_id", $user_id)->count();   // 我的评论数
        $level_name = M('user_level')->where("level_id", $this->user['level'])->getField('level_name'); // 等级名称
        //获取用户信息的数量
        $user_message_count = D('Message')->getUserMessageCount();
        $this->assign('user_message_count', $user_message_count);
        $this->assign('level_name', $level_name);
        $this->assign('comment_count', $comment_count);
        $this->assign('user',$user['result']);
        
       
        
        return $this->fetch();
    }


    public function logout()
    {
        session_unset();
        session_destroy();
        setcookie('uname','',time()-3600,'/');
        setcookie('cn','',time()-3600,'/');
        setcookie('user_id','',time()-3600,'/');
        setcookie('PHPSESSID','',time()-3600,'/');
        //$this->success("退出成功",U('Mobile/Index/index'));
        header("Location:" . U('Mobile/Index/index'));
        exit();
    }

    /*
     * 账户资金
     */
    public function account()
    {
        
        $user_id  =  session('user.user_id');
       $school  = session('user.school');
        if($school == NULL){
            $url  = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $url = urlencode($url);
            $this->redirect('my/user/school', array('url' => $url), 1, '请先选择学校...');
        }
        
        
        
        
        
        
        $user = session('user');
        //获取账户资金记录
        $logic = new UsersLogic();
        $data = $logic->get_account_log($this->user_id, I('get.type'));
        $account_log = $data['result'];

        $this->assign('user', $user);
        $this->assign('account_log', $account_log);
        $this->assign('page', $data['show']);

        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_account_list');
            exit;
        }
        
        $credit = M('users_qiang')->where('user_id',$user_id)->getField('credit');
        if(!$credit){
        	$credit = '不是抢单员';
        }
        $this->assign('credit', $credit);
        
        
        return $this->fetch();
    }

   
    
    public function school(){
        $url = urldecode(I('url'));
        if(!$url){
            $url = 'http://www.yykddn.com/mobile';
        }
        $user_id  =  session('user.user_id');
       $school  =   session('user.school');
        
       $school_list = M('school')->order('id')->select();
       $this->assign('school_list', $school_list);
       
       
        if(IS_POST){
            
            $data['school'] = I('school');
            
            M('users')->where('user_id',$user_id)->save($data);
            //清空session，重新登录
            session_unset();
            session_destroy();
            setcookie('uname','',time()-3600,'/');
            setcookie('cn','',time()-3600,'/');
            setcookie('user_id','',time()-3600,'/');
            setcookie('PHPSESSID','',time()-3600,'/');
            
            $this->success('成功选择学校',$url);
        }
        
        $this->assign('school', $school);
        return $this->fetch();
    }
    
    public function school_qiang(){
        $user_id  =  session('user.user_id');
        $user = M('users_qiang')->where("user_id", $user_id)->find();
        if(!$user){
            $this->redirect('qiangdan/index/index');
            exit;
        }
        
        
        
        $url = urldecode(I('url'));
        if(!$url){
            $url = 'http://www.yykddn.com/qiangdan';
        }
       
        $school  =  M('users_qiang')->where('user_id',$user_id)->getField('school');
    
        $school_list = M('school')->order('id')->select();
        $this->assign('school_list', $school_list);
         
         
        if(IS_POST){
    
            $data['school'] = I('school');
    
            M('users_qiang')->where('user_id',$user_id)->save($data);
            
    
            $this->success('成功选择学校',$url);
        }
    
        $this->assign('school', $school);
        return $this->fetch();
    }
    
}

