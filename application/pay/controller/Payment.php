<?php
namespace app\pay\controller;
use app\home\logic\UsersLogic;
use think\Controller;
use think\Request;
use think\Session;
//MobileBase
class Payment extends Controller{
    
    public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code
 
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();      
        // tpshop 订单支付提交
       // $pay_radio = $_REQUEST['pay_radio'];
        $pay_radio = "pay_code=weixin";
        if(!empty($pay_radio)) 
        {                         
            $pay_radio = parse_url_param($pay_radio);
            $this->pay_code = $pay_radio['pay_code']; // 支付 code
        }
        else // 第三方 支付商返回
        {            
            //$_GET = I('get.');            
            //file_put_contents('./a.html',$_GET,FILE_APPEND);    
            $this->pay_code = I('get.pay_code');
            unset($_GET['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }                        
        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];    
        if(empty($this->pay_code))
            exit('pay_code 不能为空');        
        // 导入具体的支付类文件                
        include_once  "plugins/payment/{$this->pay_code}/{$this->pay_code}.class.php"; // D:\wamp\www\svn_tpshop\www\plugins\payment\alipay\alipayPayment.class.php                       
        $code = '\\'.$this->pay_code; // \alipay
        $this->payment = new $code();
        
        Session::start();
        header("Cache-control: private");  // history.back返回后输入框值丢失问题 参考文章 http://www.tp-shop.cn/article_id_1465.html  http://blog.csdn.net/qinchaoguang123456/article/details/29852881
        $this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
        
        
    }
   
    /*
     * 订单支付页面
     */
    public function jijian(){
        $user_id = session('user.user_id');
        if(empty($user_id)){
            $this->error('登录已失效，请重新操作');
        }
    
        if(is_weixin()==false){
            //微信浏览器
            $this->error('请在微信中打开，请联系我们处理');
        }
    
        $order_id = I('order_id/d');
        $type = I('type');
    
        if(empty($order_id)){
            $this->error('订单参数出错，请联系我们处理');
        }
    
        $order = M('kd_order_ji')->where("order_id", $order_id)->find();
         
        //订单不存在或者订单不是本人
        if(!$order || $order['user_id'] !== $user_id){
            $this->error('订单不存在或者订单不是本人或者因未付款被删除，请联系我们处理');
        }
    
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("kuaidi/order/order_detail",array('id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }
    
        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        $this->assign('user',$user['result']);
    
        $this->assign('order',$order);
        $this->assign('type',$type);
    
        return $this->fetch();
    }
    
    /*
     * 订单支付页面
     */
    public function box(){
        
        $user_id = session('user.user_id');
        if(empty($user_id)){
            $this->error('登录已失效，请重新操作');
        }
    
        if(is_weixin()==false){
            //微信浏览器
            $this->error('请在微信中打开，请联系我们处理');
        }
    
        $order_id = I('order_id/d');
        $type = I('type');
    
        if(empty($order_id)){
            $this->error('订单参数出错，请联系我们处理');
        }
    
        $order = M('kd_order_box')->where("order_id", $order_id)->find();
         
        //订单不存在或者订单不是本人
        if(!$order || $order['user_id'] !== $user_id){
            $this->error('订单不存在或者订单不是本人或者因未付款被删除，请联系我们处理');
        }
    
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("box/order/detail",array('order_id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }
    
        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        $this->assign('user',$user['result']);
    
        $this->assign('order',$order);
        $this->assign('type',$type);
    
        return $this->fetch();
    }

    public function box_balance_pay(){
        $user_id = session('user.user_id');
        if(empty($user_id)){
            $this->error('登录已失效，请重新操作');
        }
    
        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        if(!user){
            $this->error('登录已失效，请重新操作');
        }
        $this->assign('user',$user['result']);
    
    
        $order_id = I('order_id/d');
        if(empty($order_id)){
            $this->error('订单参数出错，请联系我们处理');
        }
    
        $order = M('kd_order_box')->where("order_id", $order_id)->find();
         
        //订单不存在或者订单不是本人
        if(!$order || $order['user_id'] !== $user_id){
            $this->error('订单不存在或者订单不是本人或者因未付款被删除，请联系我们处理');
        }
    
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("box/order/detail",array('order_id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }
    
        if((int)$user['result']['user_money'] <  (int)$order['order_amount']){
            $this->error('抱歉，余额不足!');
        }
        
        $this->assign('order',$order);
    
        return $this->fetch();
    
    }
    public function box_balance_pay_action(){
        $user_id = session('user.user_id');
        if(empty($user_id)){
            $this->error('登录已失效，请重新操作');
        }

        $user = session('user'); //当前登录用户信息
        if(!user){
            $this->error('登录已失效，请重新操作');
        }
    
        $order_id = I('order_id/d');
        if(empty($order_id)){
            $this->error('订单参数出错，请联系我们处理');
        }
    
        $order = M('kd_order_box')->where("order_id", $order_id)->find();
         
        //订单不存在或者订单不是本人
        if(!$order || $order['user_id'] !== $user_id){
            $this->error('订单不存在或者订单不是本人或者因未付款被删除，请联系我们处理');
        }
    
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("box/send/chenggong",array('order_id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }
    
        //下面是余额处理代码
        // 修改订单的支付方式
        M('kd_order_box')->where("order_id", $order_id)->save(array('pay_code'=>'balance','pay_name'=>'余额支付'));
         
        if($order['pay_status'] == 1){
            $this->error('此订单，已完成支付!');
        }
 
       
    
        $payresult = accountLog($user_id,-$order['order_amount'],-$order['order_amount'],"余额支付{$order['order_amount']}元,扣除{$order['order_amount']}积分");

        if($payresult == true){
            update_pay_status_diy($order['order_sn'],'box',array('transaction_id'=>$data["transaction_id"])); // 修改订单支付状态
            $order_detail_url = U("box/send/chenggong",array('order_id'=>$order_id));
            header("Location: $order_detail_url");
        }else{
            $this->error('服务器开小差了，支付失败!要不换个支付方式吧');
        }

    }
    
    
    /*
     * 订单支付页面
     */
    public function kuaidi(){
        $user_id = $_SESSION['user']['user_id'];
        if(empty($user_id)){
            $this->error('登录已失效，请重新操作。');
        }
        
        if(is_weixin()==false){
            //微信浏览器
            $this->error('请在微信中打开，请联系我们处理');
        }
        
        $order_id = I('order_id/d');
        $type = I('type');
        
        if(empty($order_id)){
            $this->error('订单参数出错，请联系我们处理');
        }
        
       $order = M('kd_order')->where("order_id", $order_id)->find();
     
        //订单不存在或者订单不是本人
        if(!$order || $order['user_id'] !== $user_id){
           $this->error('订单不存在或者订单不是本人或者因未付款被删除，请联系我们处理');
        }
      
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("kuaidi/order/detail",array('order_id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }

        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        $this->assign('user',$user['result']);
 
        $this->assign('order',$order);
        $this->assign('type',$type);

        return $this->fetch();
    }
    
    public function kuaidi_balance_pay(){
        $user_id = session('user.user_id');
        if(empty($user_id)){
            $this->error('登录已失效，请重新操作');
        }

        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        if(!user){
            $this->error('登录已失效，请重新操作');
        }
        $this->assign('user',$user['result']);
 
        
        $order_id = I('order_id/d');
        if(empty($order_id)){
            $this->error('订单参数出错，请联系我们处理');
        }
        
        $order = M('kd_order')->where("order_id", $order_id)->find();
         
        //订单不存在或者订单不是本人
        if(!$order || $order['user_id'] !== $user_id){
            $this->error('订单不存在或者订单不是本人或者因未付款被删除，请联系我们处理');
        }
        
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("kuaidi/order/detail",array('order_id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }
        
        
        
        if((int)$user['result']['user_money'] <  (int)$order['order_amount']){
            $this->error('抱歉，余额不足!');
        }
        
        $this->assign('order',$order);
        
        return $this->fetch();
        
    }
    
    public function kuaidi_balance_pay_action(){
        $user_id = session('user.user_id');
        if(empty($user_id)){
            $this->error('登录已失效，请重新操作');
        }
    
       
        $user = session('user'); //当前登录用户信息
        if(!user){
            $this->error('登录已失效，请重新操作');
        }

    
        $order_id = I('order_id/d');
        if(empty($order_id)){
            $this->error('订单参数出错，请联系我们处理');
        }
    
        $order = M('kd_order')->where("order_id", $order_id)->find();
         
        //订单不存在或者订单不是本人
        if(!$order || $order['user_id'] !== $user_id){
            $this->error('订单不存在或者订单不是本人或者因未付款被删除，请联系我们处理');
        }
    
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("kuaidi/send/chenggong",array('order_id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }
    
        //下面是余额处理代码
        // 修改订单的支付方式
        M('kd_order')->where("order_id", $order_id)->save(array('pay_code'=>'balance','pay_name'=>'余额支付'));
       
        if($order['pay_status'] == 1){
            $this->error('此订单，已完成支付!');
        }
        
        
        
        
        
        $payresult = accountLog($user_id,-$order['order_amount'],-$order['order_amount'],"余额支付{$order['order_amount']}元,扣除{$order['order_amount']}积分");
     
       
        
        
        
        if($payresult == true){
            update_pay_status_diy($order['order_sn'],'kuaidi',array('transaction_id'=>$data["transaction_id"])); // 修改订单支付状态
            $order_detail_url = U("kuaidi/send/chenggong",array('order_id'=>$order_id));
            header("Location: $order_detail_url");
        }else{
            $this->error('服务器开小差了，支付失败!要不换个支付方式吧');
        }
        
       
    
    }
    
    
    /**
     * tpshop 提交支付方式
     */
    public function getCode(){     
        
            //C('TOKEN_ON',false); // 关闭 TOKEN_ON
            header("Content-type:text/html;charset=utf-8");            
            $order_id = I('order_id/d'); // 订单id
            // 修改订单的支付方式
            $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");                        
            M('order')->where("order_id", $order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
            $order = M('order')->where("order_id", $order_id)->find();
            if($order['pay_status'] == 1){
            	$this->error('此订单，已完成支付!');
            }
            //tpshop 订单支付提交
            $pay_radio = $_REQUEST['pay_radio'];
            $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
            //微信JS支付
          if($this->pay_code == 'weixin' && session('user.openid') && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
               $code_str = $this->payment->getJSAPI($order);
              
             
               //dump($pay_radio);
               
              exit($code_str);
           }else{
           	$code_str = $this->payment->get_code($order,$config_value);
           	
           }
            $this->assign('code_str', $code_str); 
            $this->assign('order_id', $order_id); 
            return $this->fetch('payment');  // 分跳转 和不 跳转
    }

    /* 顺职的查询结果 */
    
    public function payresult(){
    	$order_id = I('order_id/d');
    	
    	$order = M('kd_order_sz')->where("order_id",$order_id)->find();
    	//field("order_id,order_sn,pay_status,pay_code,pay_name,pay_time,order_omount,transaction_id,out_trade_no")
    	$json = json_encode($order);
    	
    	echo $json;
    	
    }
    
    
    /* 顺职的支付系统 */
    
    public function shunzhi(){
    	$order_sn = I('order_sn/d');
    	$openid = I('openid');
    	
    	if(!$openid){
    		$openid = $this->GetOpenid();
    		//$this->error('openid参数为空');
    		exit;
    	}else{
    		session('user.openid',$openid);
    	}
    	
    	
    	
    	
    	
    	$url = "http://v.yykddn.com/pay/payment/order?order_sn=".$order_sn;
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	$out = curl_exec($ch);
    	curl_close($ch);
    	
    
    	$order = json_decode($out,true);
    	
    	if(!$order){
    		$this->error("点击太快了，请再试一次");
    		exit;
    	}
    	$order_id = $order['order_id'];
    	
    	
    	if($order['pay_status'] == 1){
    		$url = 'http://v.yykddn.com/kuaidi/send/check?order_id='.$order_id.'&source=sz';
    		$this->redirect($url);
    		exit;
    	}
    	
    	
    	
    	
    	$cunzai = M("kd_order_sz")->where("order_id",$order_id)->find();
    	if(!$cunzai){
    		M("kd_order_sz")->data($order)->save();
    	}else {
    		M("kd_order_sz")->where(array("order_id"=>$order_id))->data($order)->save();
    	}
    	
    	$payurl = "/pay/payment/pay?order_id=".$order_id."&source=sz";
    	$this->redirect($payurl);
    	
    }
    
    
    
    //统一支付类
 public function pay(){     
    
           //C('TOKEN_ON',false); // 关闭 TOKEN_ON
            header("Content-type:text/html;charset=utf-8");    
            $order_id = I('order_id/d'); // 订单id
            $source = I('source');
              if($source == 'kuaidi'){
                $database = M('kd_order');
            }elseif($source == 'ji'){
                $database = M('kd_order_ji');
            }elseif($source == 'sz'){
            	$database = M('kd_order_sz');
            }
            else
            {
                $this->error('支付出错了，source参数错误，请联系我们');
            }
            // 修改订单的支付方式   
            $database->where("order_id", $order_id)->save(array('pay_code'=>'weixin','pay_name'=>'微信支付'));
            $order = $database->where("order_id", $order_id)->find();
            if($order['pay_status'] == 1){
            	$this->error('此订单，已完成支付!');
            }
            
            $pay_radio = "pay_code=weixin";
            $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
            //微信JS支付
          if(session('user.openid') == NULL){
              $this->error('用户openid不存在');
              exit;
          }
          
           if($this->pay_code == 'weixin'  && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){

           //	dump('5555');
               $code_str = $this->payment->getpay($order_id,$source);
               
              exit($code_str);
           }else{
           	$code_str = $this->payment->get_code($order,$config_value);
           	
           }
            $this->assign('code_str', $code_str); 
            $this->assign('order_id', $order_id); 
            return $this->fetch('payment');  // 分跳转 和不 跳转
    }
    
    // 服务器点对点 // http://www.tp-shop.cn/index.php/Home/Payment/notifyUrl
    public function notifyUrl(){
        $order_id = I('order_id');
        $source = I('source');
        $this->payment->response($order_id,$source);
        exit();
    }
    
    
    
    
    
    
    
    
    //下面的都是垃圾
    
    
    
    
    
    //外卖支付，，，1饭6
    public function wmpay(){
    
        //C('TOKEN_ON',false); // 关闭 TOKEN_ON
        header("Content-type:text/html;charset=utf-8");
        // $order_id = '10727'; // 订单id
        $order_id = I('order_id/d'); // 订单id
        
        $shop = I('shop/d');
   
        $foodorder = M('wm_foodorder');
        $type = 'wm';
  
        // 修改订单的支付方式
        $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
        M('wm_foodorder')->where("oid", $order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
        $order = $foodorder -> where("oid", $order_id)->find();
        if($order['pay_status'] == 1){
            $this->error('此订单，已完成支付!');
        }
        //tpshop 订单支付提交
        // $pay_radio = $_REQUEST['pay_radio'];
        $pay_radio = "pay_code=weixin";
        $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        //微信JS支付
    
       
       
        if($this->pay_code == 'weixin' && session('user.openid') && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $code_str = $this->payment->getJSAPIKD($order,$type);
            //dump($pay_radio);
             
            exit($code_str);
        }else{
            $code_str = $this->payment->get_code($order,$config_value);
    
        }
        $this->assign('code_str', $code_str);
        $this->assign('order_id', $order_id);
        return $this->fetch('payment');  // 分跳转 和不 跳转
    }
    
    //外卖支付----1饭5
    public function wmpay2(){

        header("Content-type:text/html;charset=utf-8");
       
        $order_id = I('order_id/d'); // 订单id
    
        $shop = 2;
   // 修改订单的支付方式
        $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
        M('wm2_foodorder')->where("oid", $order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
        $order =  M('wm2_foodorder') -> where("oid", $order_id)->find();
        if($order['pay_status'] == 1){
            $this->error('此订单，已完成支付!');
            exit;
        }
        //tpshop 订单支付提交
        // $pay_radio = $_REQUEST['pay_radio'];
        $pay_radio = "pay_code=weixin";
        $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        //微信JS支付
       
        if($this->pay_code == 'weixin' && session('user.openid') && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $code_str = $this->payment->getJSAPIWAIMAI($order,$shop);
        
            exit($code_str);
        }else{
            $code_str = $this->payment->get_code($order,$config_value);
    
        }
        $this->assign('code_str', $code_str);
        $this->assign('order_id', $order_id);
        return $this->fetch('payment');  // 分跳转 和不 跳转
    }
    //外卖支付----1饭4
    public function wmpay4(){
    
        header("Content-type:text/html;charset=utf-8");
         
        $order_id = I('order_id/d'); // 订单id
    
        $shop = 4;
        // 修改订单的支付方式
        $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
        M('wm4_foodorder')->where("oid", $order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
        $order =  M('wm4_foodorder') -> where("oid", $order_id)->find();
        if($order['pay_status'] == 1){
            $this->error('此订单，已完成支付!');
            exit;
        }
        //tpshop 订单支付提交
        // $pay_radio = $_REQUEST['pay_radio'];
        $pay_radio = "pay_code=weixin";
        $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        //微信JS支付
         
        if($this->pay_code == 'weixin' && session('user.openid') && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $code_str = $this->payment->getJSAPIWAIMAI($order,$shop);
    
            exit($code_str);
        }else{
            $code_str = $this->payment->get_code($order,$config_value);
    
        }
        $this->assign('code_str', $code_str);
        $this->assign('order_id', $order_id);
        return $this->fetch('payment');  // 分跳转 和不 跳转
    }
    public function getPay(){
    	//手机端在线充值
        //C('TOKEN_ON',false); // 关闭 TOKEN_ON 
        header("Content-type:text/html;charset=utf-8");
        $order_id = I('order_id/d'); //订单id
        $user = session('user');
        $data['account'] = I('account');
        if($order_id>0){
        	M('recharge')->where(array('order_id'=>$order_id,'user_id'=>$user['user_id']))->save($data);
        }else{
        	$data['user_id'] = $user['user_id'];
        	$data['nickname'] = $user['nickname'];
        	$data['order_sn'] = 'recharge'.get_rand_str(10,0,1);
        	$data['ctime'] = time();
        	$order_id = M('recharge')->add($data);
        }
        if($order_id){
        	$order = M('recharge')->where("order_id", $order_id)->find();
        	if(is_array($order) && $order['pay_status']==0){
        		$order['order_amount'] = $order['account'];
        		$pay_radio = $_REQUEST['pay_radio'];
        		$config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        		$payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
        		M('recharge')->where("order_id", $order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
        		//微信JS支付
        		if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
        			$code_str = $this->payment->getJSAPI($order);
        			
        			exit($code_str);
        		}else{
        			$code_str = $this->payment->get_code($order,$config_value);
        		}
        	}else{
        		$this->error('此充值订单，已完成支付!');
        	}
        }else{
        	$this->error('提交失败,参数有误!');
        }
        
       
        
        $this->assign('code_str', $code_str); 
        $this->assign('order_id', $order_id); 
    	return $this->fetch('recharge'); //分跳转 和不 跳转
    }
   
    public function notifyUrl_kd(){
        $type = I('type');
        $this->payment->response_kd($type);
        exit();
    }
    public function notifyUrl_waimai(){
        $shop = I('shop');
        $this->payment->response_waimai($shop);
        exit();
    }
    
    
    
    
    
    
        // 页面跳转 // http://www.tp-shop.cn/index.php/Home/Payment/returnUrl        
   public function returnUrl(){
            $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';  
            if(stripos($result['order_sn'],'recharge') !== false)
            {
            	$order = M('recharge')->where("order_sn", $result['order_sn'])->find();
            	$this->assign('order', $order);
            	if($result['status'] == 1)
            		return $this->fetch('recharge_success');
            	else
            		return $this->fetch('recharge_error');
            	exit();
            }          
            $order = M('order')->where("order_sn", $result['order_sn'])->find();
            $this->assign('order', $order);
            
            $logic = new Pushgood();
            $data = $logic->success($order);

            $user = $this->all_shangjia();
            $c = count($user);
            for ($i = 0; $i < $c; $i++){
                $openid =  $user[$i]['openid'];
             $data = $logic->push_shangjia($openid,$order);
            }

            if($result['status'] == 1)
                return $this->fetch('success');
            else
                return $this->fetch('error');
    
  }    
  
  public function all_shangjia(){

      $user = M('admin_sj')->field('openid')->where(array('shangjia_name' => 'dcx' , 'is_validated' => 1 ))->select();
      
      return $user;
  }

  /////////////////////////////////////////////
  // 仅仅获取 OpendId
  public function GetOpenid()
  {
  	if($_SESSION['openid'])
  		return $_SESSION['openid'];
  		//通过code获得openid
  		if (!isset($_GET['code'])){
  			//触发微信返回code码
  			$baseUrl = urlencode($this->get_url_openid());
  			$url = $this->__CreateOauthUrlForCode($baseUrl); // 获取 code地址
  			Header("Location: $url"); // 跳转到微信授权页面 需要用户确认登录的页面
  			exit();
  		} else {
  			//上面获取到code后这里跳转回来
  			$code = $_GET['code'];
  			$data = $this->getOpenidFromMp($code);//获取网页授权access_token和用户openid
  			return $data['openid'];
  		}
  }
  /**
   * 获取当前的url 地址
   * @return type
   */
  private function get_url_openid() {
  	$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
  	$php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
  	$path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
  	$relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
  	return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
  }
  /**
   *
   * 通过code从工作平台获取openid机器access_token
   * @param string $code 微信跳转回来带上的code
   *
   * @return openid
   */
  public function GetOpenidFromMp($code)
  {
  	//通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。
  	//1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；
  	//2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。
  	$url = $this->__CreateOauthUrlForOpenid($code);
  	$ch = curl_init();//初始化curl
  	curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
  	curl_setopt($ch, CURLOPT_URL, $url);
  	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
  	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
  	curl_setopt($ch, CURLOPT_HEADER, FALSE);
  	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  	$res = curl_exec($ch);//运行curl，结果以jason形式返回
  	$data = json_decode($res,true);
  	curl_close($ch);
  	return $data;
  }
  /**
   * 构造获取code的url连接
   * @param string $redirectUrl 微信服务器回跳的url，需要url编码
   * @return 返回构造好的url
   */
  private function __CreateOauthUrlForCode($redirectUrl)
  {
  	$urlObj["appid"] = "wx9a1f0bef4dbb9da0";
  	//$urlObj["appid"] = $this->weixin_config['appid'];
  	$urlObj["redirect_uri"] = "$redirectUrl";
  	$urlObj["response_type"] = "code";
  	$urlObj["scope"] = "snsapi_base";
  	//$urlObj["scope"] = "snsapi_userinfo";
  	$urlObj["state"] = "STATE"."#wechat_redirect";
  	$bizString = $this->ToUrlParams($urlObj);
  	return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
  }
  /**
   * 构造获取open和access_toke的url地址
   * @param string $code，微信跳转带回的code
   * @return 请求的url
   */
  private function __CreateOauthUrlForOpenid($code)
  {
  	$urlObj["appid"] = "wx9a1f0bef4dbb9da0";
  	$urlObj["secret"] = "";
  	//$urlObj["appid"] = $this->weixin_config['appid'];
  	//$urlObj["secret"] = $this->weixin_config['appsecret'];
  	$urlObj["code"] = $code;
  	$urlObj["grant_type"] = "authorization_code";
  	$bizString = $this->ToUrlParams($urlObj);
  	return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
  }
  /**
   *
   * 拼接签名字符串
   * @param array $urlObj
   *
   * @return 返回已经拼接好的字符串
   */
  private function ToUrlParams($urlObj)
  {
  	$buff = "";
  	foreach ($urlObj as $k => $v)
  	{
  		if($k != "sign"){
  			$buff .= $k . "=" . $v . "&";
  		}
  	}
  	$buff = trim($buff, "&");
  	return $buff;
  }
  /////////////////////////////////获取openid结束
  
  
}
