<?php
namespace app\anyile\logic;
use think\Model;
use think\Db;

class KuaidiLogic extends Model
{


    //判断下单时间，返回true这可以下单，false则不可以下单
    public function check_time(){
            $hour=(int)date("G");
            
           // if($hour == 12){
              //  $ii=(int)date("i");
               // if($ii >= 15){
                  //  return false;
                //}
                 
            //}
            
           // if($hour == 13){
               // return false;
           // }
            
           // if($hour == 14){
               // return false;
           // }
            
            
           // if($hour == 18){
               // $i=(int)date("i");
                //if($i >= 30){
                 // return false;
                //}
           // }
            
           // if($hour >=18){
                // return false;
           // }
     
            return true;
    }
    
    
    /**
     * 取消订单 lxl 2017-4-29
     * @param $user_id  用户ID
     * @param $order_id 订单ID
     * @param string $action_note 操作备注
     * @return array
     */
    public function cancel_order($user_id,$order_id,$action_note='您取消了订单'){
        $order = M('anyile_kd_order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->find();
        //检查是否未支付订单 已支付联系客服处理退款
        if(empty($order))
            return array('status'=>-1,'msg'=>'订单不存在','result'=>'');
        //检查是否未支付的订单
        if( $order['order_status'] > 0)
            return array('status'=>-1,'msg'=>'订单状态不允许','result'=>'');
        //获取记录表信息
        //$log = M('account_log')->where(array('order_id'=>$order_id))->find();
       
        $row = M('anyile_kd_order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->save(array('order_status'=>3));
    
        $data['order_id'] = $order_id;
        $data['action_user'] = 0;
        $data['action_note'] = $action_note;
        $data['order_status'] = 3;
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = '用户取消订单';
        M('anyile_kd_order_action')->add($data);//订单操作记录
    
        if(!$row)
            return array('status'=>-1,'msg'=>'操作失败','result'=>'');
        return array('status'=>1,'msg'=>'操作成功','result'=>'');
    
    }
    
}