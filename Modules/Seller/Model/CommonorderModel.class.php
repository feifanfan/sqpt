<?php
/**
 * lionfish 商城系统
 *
 * ==========================================================================
 * @link      http://www.liofis.com/
 * @copyright Copyright (c) 2015 liofis.com. 
 * @license   http://www.liofis.com/license.html License
 * ==========================================================================
 *
 * @author    fish
 *
 */
namespace Seller\Model;

class CommonorderModel{
	
	/**
		获取一个订单中，商品的数量，
	**/
	public function get_order_goods_quantity( $order_id,$order_goods_id=0)
	{
		
		$where = "";
		
		if( !empty($order_goods_id) && $order_goods_id >0 )
		{
			$where .= " and order_goods_id={$order_goods_id} ";
		}
		
		//原来有的数量				
		$total_quantity = M('lionfish_comshop_order_goods')->where( " order_id ={$order_id} {$where} " )->sum('quantity');					
							
		
		$total_quantity = empty($total_quantity) ? 0 : $total_quantity;
		
		$refund_quantity = $this->refund_order_goods_quantity( $order_id,$order_goods_id);
		 
		 $surplus_quantity = $total_quantity - $refund_quantity;
		 
		 return $surplus_quantity;
	}
	
	/**
		已经退掉的订单商品数量
	**/
	public function refund_order_goods_quantity( $order_id,$order_goods_id=0 )
	{
		
		$where = "";
		
		if( !empty($order_goods_id) && $order_goods_id >0 )
		{
			$where .= " and order_goods_id={$order_goods_id} ";
		}
		
		
		$refund_quantity =  M('lionfish_comshop_order_goods_refund')->where( "order_id ={$order_id} {$where}" )->sum('quantity');
		
		$refund_quantity = empty($refund_quantity) ? 0 : $refund_quantity;
		
		return $refund_quantity;
	}
	
	/**
		该笔子订单已经退款了多少钱
	**/
	public function get_order_goods_refund_money( $order_id,$order_goods_id )
	{
		
		$where = "";
		
		if( !empty($order_goods_id) && $order_goods_id >0 )
		{
			$where .= " and order_goods_id={$order_goods_id} ";
		}
		
		
		$refund_money = M('lionfish_comshop_order_goods_refund')->where( "order_id ={$order_id} {$where}"  )->sum('money');
		
		$refund_money = empty($refund_money) ? 0 : $refund_money;
		
		return $refund_money;
		
	}
	
	/**
		插入子订单退款
	**/
	public function ins_order_goods_refund($order_id, $order_goods_id,$total_quantity, $refund_quantity,$refund_money, $is_back_sellcount)
	{
		//计算需要抵扣多少佣金 ims_ lionfish_comshop_order
		$commiss_info = M('lionfish_community_head_commiss_order')->where( array('order_id' => $order_id, 'order_goods_id' => $order_goods_id, 'type' => 'orderbuy'  )  )->find();
		
		// lionfish_comshop_order_goods
			
		$order_goods_info = M('lionfish_comshop_order_goods')->where( array('order_goods_id' => $order_goods_id ) )->find();
		
		
		//order_status_id
		
		$order_info = M('lionfish_comshop_order')->where( array('order_id' => $order_id ) )->find();
		
		
		$refund_data = array();
		$refund_data['order_goods_id'] = $order_goods_id;
		$refund_data['order_id'] = $order_id;
		$refund_data['uniacid'] = $_W['uniacid'];
		$refund_data['quantity'] = $refund_quantity;
		$refund_data['money'] = $refund_money;
		$refund_data['order_status_id'] = $order_info['order_status_id'];
		$refund_data['is_back_quantity'] = $is_back_sellcount;
		
		//---  以下需要计算了 refundorder
		$refund_data['back_score_for_money'] = 0;//退还积分兑换商品的积分 orderbuy
		$refund_data['back_send_score'] = 0; //退还赠送积分 goodsbuy
		$refund_data['back_head_orderbuycommiss'] = 0; //退还团长佣金
		$refund_data['back_head_supplycommiss'] = 0; //退还供应商佣金
		$refund_data['back_head_commiss_1'] = 0; //退1级团长佣金
		$refund_data['back_head_commiss_2'] = 0; //退2级团长佣金
		$refund_data['back_head_commiss_3'] = 0; //退3级团长佣金
		$refund_data['back_member_commiss_1'] = 0; //退会员1级佣金
		$refund_data['back_member_commiss_2'] = 0; //退会员2级佣金
		$refund_data['back_member_commiss_3'] = 0; //退会员3级佣金
		$refund_data['addtime'] = time(); //添加时间
		
		
		if( !empty($commiss_info) && $commiss_info['state'] == 1 )
		{
			//已经结算了
			
		}else{
			//未结算的
			$score_for_money_info = M('lionfish_comshop_member_integral_flow')->where( array('order_id' => $order_id, 'order_goods_id' => $order_goods_id, 'type' => 'orderbuy'  ) )->find();
			
			if( !empty($score_for_money_info) )
			{
				$refund_data['back_score_for_money'] =  intval( ($refund_quantity / $total_quantity ) * $score_for_money_info['score'] );
				//退回去给用户
				D('Admin/Member')->sendMemberPointChange($order_info['member_id'],$refund_data['back_score_for_money'], 0 ,'退款'.$refund_quantity.'个商品，增加积分','refundorder', $order_info['order_id'] ,$order_goods_id );
				
			}
			
			
			$send_score_info = M('lionfish_comshop_member_integral_flow')->where( array('order_id' => $order_id, 'order_goods_id' => $order_goods_id ,'type' => 'goodsbuy' ) )->find();
			
			if( !empty($send_score_info) )
			{
				$refund_data['back_send_score'] =  intval( ($refund_quantity / $total_quantity ) * $send_score_info['score'] );
				
				$refund_data['back_send_score'] = $refund_data['back_send_score'] <= 0 ? 0 : $refund_data['back_send_score'];
				//减去相应的分数，然后插入
				
				M('lionfish_comshop_member_integral_flow')->where( array('order_id' => $order_id, 'type' => 'goodsbuy', 'order_goods_id' => $order_goods_id ) )->setInc('score', -$refund_data['back_send_score'] );
			}
			//$refund_data['back_head_orderbuycommiss'] = 0; //退还团长佣金
			
			$head_commisslist = M('lionfish_community_head_commiss_order')->where(" type in ('orderbuy','commiss') and order_id={$order_id} and order_goods_id={$order_goods_id} and state=0 ")->select();
			
			if( !empty($head_commisslist) )
			{
				foreach( $head_commisslist as $val )
				{
					if( $val['type'] == 'orderbuy' )
					{
						$head_orderbuycommiss = round( ($refund_quantity / $total_quantity ) * $val['money'] , 2);
						
						$head_orderbuycommiss = $head_orderbuycommiss <= 0 ? 0 : $head_orderbuycommiss;
						
						
						M('lionfish_community_head_commiss_order')->where( array('id' => $val['id'] ) )->setInc('money', -$head_orderbuycommiss);
						
						$refund_data['back_head_orderbuycommiss'] = $head_orderbuycommiss;
					}
					if( $val['type'] == 'commiss' )
					{
						if( $val['level'] == 1 )
						{
							$head_levelcommiss = round( ($refund_quantity / $total_quantity ) * $val['money'] , 2);
						
							$head_levelcommiss = $head_levelcommiss <= 0 ? 0 : $head_levelcommiss;
							
							M('lionfish_community_head_commiss_order')->where( array('id' => $val['id'] ) )->setInc('money', -$head_levelcommiss);
							
							$refund_data['back_head_commiss_1'] = $head_levelcommiss;
						}
						if( $val['level'] == 2 )
						{
							$head_levelcommiss = round( ($refund_quantity / $total_quantity ) * $val['money'] , 2);
						
							$head_levelcommiss = $head_levelcommiss <= 0 ? 0 : $head_levelcommiss;
							
							
							M('lionfish_community_head_commiss_order')->where( array('id' => $val['id'] ) )->setInc('money', -$head_levelcommiss);
							
							$refund_data['back_head_commiss_2'] = $head_levelcommiss;
						}
						if( $val['level'] == 3 )
						{
							$head_levelcommiss = round( ($refund_quantity / $total_quantity ) * $val['money'] , 2);
						
							$head_levelcommiss = $head_levelcommiss <= 0 ? 0 : $head_levelcommiss;
							
							
							M('lionfish_community_head_commiss_order')->where( array('id' => $val['id'] ) )->setInc('money', -$head_levelcommiss);
							
							
							$refund_data['back_head_commiss_3'] = $head_levelcommiss;
						}
					}
				}
			}
			
			//back_head_supplycommiss    ims_lionfish_supply_commiss_order ims_ 
			
			$supply_commisslist = M('lionfish_supply_commiss_order')->where( array('order_id' => $order_id, 'order_goods_id' => $order_goods_id, 'state' => 0  ) )->find();
			
			if( !empty($supply_commisslist) )
			{
				$supply_orderbuycommiss = round( ($refund_quantity / $total_quantity ) * $supply_commisslist['total_money'] , 2);
				$supply_orderbuycommiss_money = round( ($refund_quantity / $total_quantity ) * $supply_commisslist['money'] , 2);
						
				$supply_orderbuycommiss = $supply_orderbuycommiss <= 0 ? 0 : $supply_orderbuycommiss;
				$supply_orderbuycommiss_money = $supply_orderbuycommiss_money <= 0 ? 0 : $supply_orderbuycommiss_money;
				
				M('lionfish_supply_commiss_order')->where( array('id' => $supply_commisslist['id'] ) )->setInc('money', -$supply_orderbuycommiss_money);
				M('lionfish_supply_commiss_order')->where( array('id' => $supply_commisslist['id'] ) )->setInc('total_money', -$supply_orderbuycommiss);
				
				
				$refund_data['back_head_supplycommiss'] = $supply_orderbuycommiss;
			}
			
			// 
			
			//$refund_data['back_member_commiss_1'] = 0; //退会员1级佣金
			//$refund_data['back_member_commiss_2'] = 0; //退会员2级佣金
			//$refund_data['back_member_commiss_3'] = 0; //退会员3级佣金
			
			$member_commisslist = M('lionfish_comshop_member_commiss_order')->where( array('order_id' => $order_id, 'order_goods_id' => $order_goods_id, 'state' => 0  ) )->select();
			
			if( !empty($member_commisslist) )
			{
				foreach( $member_commisslist as $val )
				{
						if( $val['level'] == 1 )
						{
							$member_levelcommiss = round( ($refund_quantity / $total_quantity ) * $val['money'] , 2);
						
							$member_levelcommiss = $member_levelcommiss <= 0 ? 0 : $member_levelcommiss;
							
							M('lionfish_comshop_member_commiss_order')->where( array('id' => $val['id'] ) )->setInc('money', -$member_levelcommiss);
				
							
							$refund_data['back_member_commiss_1'] = $member_levelcommiss;
						}
						if( $val['level'] == 2 )
						{
							$member_levelcommiss = round( ($refund_quantity / $total_quantity ) * $val['money'] , 2);
						
							$member_levelcommiss = $member_levelcommiss <= 0 ? 0 : $member_levelcommiss;
							
							M('lionfish_comshop_member_commiss_order')->where( array('id' => $val['id'] ) )->setInc('money', -$member_levelcommiss);
				
							
							$refund_data['back_member_commiss_2'] = $member_levelcommiss;
						}
						if( $val['level'] == 3 )
						{
							$member_levelcommiss = round( ($refund_quantity / $total_quantity ) * $val['money'] , 2);
						
							$member_levelcommiss = $member_levelcommiss <= 0 ? 0 : $member_levelcommiss;
							
							M('lionfish_comshop_member_commiss_order')->where( array('id' => $val['id'] ) )->setInc('money', -$member_levelcommiss);
				
							
							$refund_data['back_member_commiss_3'] = $member_levelcommiss;
						}
				}
			}
			
			//INSERT
			$id = M('lionfish_comshop_order_goods_refund')->add( $refund_data );
			
			
			M('lionfish_comshop_order_goods')->where( array('order_goods_id' => $order_goods_id ) )->setInc('has_refund_money', $refund_money);
							
			//has_refund_money
			
			return $id;
		}
		
	}
	
	public function check_refund_order_goods_status($order_id, $order_goods_id, $refund_money,$is_back_sellcount, $refund_quantity,$is_refund_shippingfare)
	{
		//   quantity
		$refund_total_quantity = M('lionfish_comshop_order_goods_refund')->where( array('order_id' => $order_id, 'order_goods_id' => $order_goods_id  )  )->sum('quantity');
		
		$order_goods_info = M('lionfish_comshop_order_goods')->where( array('order_goods_id' => $order_goods_id ) )->find();
		
		$order_info =  M('lionfish_comshop_order')->where( array('order_id' => $order_id ) )->find();
		
		
		if( $refund_total_quantity >= $order_goods_info['quantity'] || $order_goods_info['has_refund_money'] >= $order_goods_info['total'])
		{
			M('lionfish_comshop_order_goods')->where( array('order_goods_id' => $order_goods_id  ) )->save( array('is_refund_state' => 1 ) );
			
		
			$order_goods_list =  M('lionfish_comshop_order_goods')->where( array('order_id' => $order_id )  )->select();
			
			$is_all_refund = true;
			
			foreach($order_goods_list as $val )
			{
				if($val['is_refund_state'] != 1)
				{
					$is_all_refund = false;
				}
			}
			
			if($is_all_refund)
			{
				$comment = '后台操作立即退款,退款金额:'.$refund_money.'元';
			
				if( $order_info['type'] == 'integral' )
				{
					if( $order_info['shipping_fare'] > 0 )
					{
						$comment = '后台操作子订单退款,退款金额:'.$order_info['shipping_fare'].'元，积分:'.$order_info['total'];
					}else{
						$comment = '后台操作子订单退款,退还积分:'.$order_info['total'];
					}
				}
				
				if($is_refund_shippingfare == 1)
				{
					$comment .= '. 退配送费：'.$order_goods_info['shipping_fare'].'元';
				}
				
				if($is_back_sellcount == 1)
				{
					$comment .= '. 退库存：'.$refund_quantity;
				}else{
					$comment .= '. 不退库存';
				}
				
				
				$order_history = array();
				$order_history['uniacid'] = $_W['uniacid'];
				$order_history['order_id'] = $order_id;
				$order_history['order_status_id'] = 7;
				$order_history['notify'] = 0;
				$order_history['comment'] =  $comment;
				$order_history['date_added'] = time();
			
				M('lionfish_comshop_order_history')->add( $order_history );
				
				M('lionfish_comshop_order')->where(  array('order_id' => $order_id) )->save( array('order_status_id' => 7) );
				
				//$data[''] = isset($data['is_print_admin_cancleorder']) ? $data['is_print_admin_cancleorder'] : 0;
				$is_print_admin_cancleorder = D('Home/Front')->get_config_by_name('is_print_admin_cancleorder');
				
				if( isset($is_print_admin_cancleorder) && $is_print_admin_cancleorder == 1 )
				{
					D('Seller/Printaction')->check_print_order($order_id,$_W['uniacid'],'后台操作取消订单');
				}
				
			}
			
		}else{
			
				
		}
		
		
		
	}
	
	
	
}
?>