<?php
/**
 * description:
 * author: ff
 * Date: 2020/3/21
 * Time: 16:25
 */

namespace Seller\Controller;


class SurveyController extends CommonController
{
    /**
     * 添加投票主题
     */
    public function addSurvey(){
        if(IS_POST){
            $time = I('post.time');
            $data = I('post.data');
            if(empty($data['name'])){
                show_json(0, '请输入主题名称');
                die;
            }
            $data['starttime'] = $time['start'];
            $data['endtime'] = $time['end'];
            $result = M('lionfish_comshop_survey_topic')->add($data);
            show_json(1, array('url' => $_SERVER['HTTP_REFERER'] ));
        }

        $this->display();
    }

    /**
     * 投票主题列表
     */
    public function surveyList(){
        $pindex = I('get.page', 1);
        $psize = 5;
        $count= M('lionfish_comshop_survey_topic')->count();
        $pager = pagination2($count, $pindex, $psize);
        $list = M('lionfish_comshop_survey_topic')->limit(($pindex-1)*$psize,$psize)->select(['name','starttime','endtime']);
        $this->assign('pager',$pager);// 赋值分页输出
        $this->assign('list',$list);
        $this->display();
    }
    public function editSurvey(){
        $topicid = I('get.topicid');
        $item['topicid'] = $topicid;
        $this->assign('item',$item);
        $this->display('addSurvey');
    }
}