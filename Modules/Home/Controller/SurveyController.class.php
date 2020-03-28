<?php
namespace Home\Controller;
/**
 * description:
 * author: ff
 * Date: 2020/3/25
 * Time: 14:55
 */

class SurveyController extends CommonController
{
    /**
     * @description：获取问题
     * @date:2020/3/25
     * @author ff
     */
    public function getSurvey(){
        $uid = 1;
        $where['flag'] = 1;
        $where['starttime'] = array('LT',date('Y-m-d H:i:s',time()));
        $where['endtime'] = array('GT',date('Y-m-d H:i:s',time()));
        $topic = M('lionfish_comshop_survey_topic')->order('created desc')->where($where)->field(['id','name'])->find();
        $is_commit = M('lionfish_comshop_survey_user_answer')->where(['topicid'=>$topic['id'],'uid'=>$uid])->find();
        if($is_commit){
            echo json_encode(array('code'=>0,'message'=>'您已经提交过，不再显示'));
            die;
        }
        $question = M('lionfish_comshop_survey_question')->where(['topicid'=>$topic['id']])->field(['id','question'])->select();
        foreach ($question as $key=>&$item){
            $question[$key]['answer'] = M('lionfish_comshop_survey_answer')->where(['topicid'=>$topic['id'],'questionid'=>$item['id']])->order('id')->field(['id as answer_id','answer_option','answer_image'])->select();
        }
        if(empty($question)){
            echo json_encode(array('code'=>0));
            die;
        }
        $question['topicName'] = $topic['name'];
        echo json_encode(array('code'=>1,'data'=>$question));
    }

    /**
     * @description：提交表单
     * @date:2020/3/25
     * @author ff
     */
    public function commit(){
        $data = I('get.member_id');

        echo json_encode($data);die;
    }
}