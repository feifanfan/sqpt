<?php
namespace Home\Model;
use Think\Model;
/**
 * 商品分类模型
 * @author fish
 *
 */
class GoodsCategoryModel {
	
	
	/**
		获取首页的商品分类
	**/
	public function get_index_goods_category($pid = 0 ,$cate_type = 'normal', $is_show=1, $is_type_show=0)
	{
		// and pid = {$pid}
		if( empty($pid) )
		{
			$pid = 0;
		}
		
		$where = '';
	    if($is_show==1) {
	    	
			$cate_list = M('lionfish_comshop_goods_category')->where( array('is_show' => 1,'pid' =>$pid,'cate_type' => $cate_type ) )
			->order('sort_order desc, id desc')->select();
	    } else if($is_type_show==1) {
	    	
			$cate_list = M('lionfish_comshop_goods_category')->where( array('is_type_show' => 1,'pid' =>$pid,'cate_type' => $cate_type ) )
			->order('sort_order desc, id desc')->select();
	    }
		
		
		
			
		$need_data = array();	
			
		foreach($cate_list as $key => $cate)		
		{			
			$need_data[$key]['id'] = $cate['id'];			
			$need_data[$key]['name'] = $cate['name'];
			$need_data[$key]['banner'] = $cate['banner'] && !empty($cate['banner']) ? tomedia($cate['banner']) : '';
			$need_data[$key]['logo'] = $cate['logo'] && !empty($cate['logo']) ? tomedia($cate['logo']) : '';
			$need_data[$key]['sort_order'] = $cate['sort_order'];
			
			$sub_cate = M('lionfish_comshop_goods_category')->field('id,name,sort_order')
						->where( array('pid' => $cate['id'], 'is_show' => 1) )->order('sort_order desc, id desc')->select();
			
			$need_data[$key]['sub'] = $sub_cate;
		}				
		
		
		return $need_data;
	}
	
}