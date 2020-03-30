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
        $uid = I('request.member_id');
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
        $member_id = I('request.member_id');
        $questionStr =I('request.data');
        $questionStr = str_replace("\"",'',$questionStr);
        $questionStr=str_replace('{','',$questionStr);
        $questionStr = str_replace('}','',$questionStr);

        $questionArr = explode(',',$questionStr);
        $data =[];
        foreach ($questionArr as $item){
           $itemx =  explode(':',$item);
           if(empty(substr($itemx[0],15,-6))){continue;}
            $data[]= ['questionid'=>substr($itemx[0],15,-6),
                       'answerid'=>substr($itemx[1],6,-6)];
        }
        $need = [];
        if(!empty($data)){
            $topicid = M('lionfish_comshop_survey_question')->where(['id'=>$data[0]['questionid']])->getField('topicid');
            foreach ($data as $item){
                $need['questionid'] = $item['questionid'];
                $need['answerid'] = $item['answerid'];
                $need['uid'] = $member_id;
                $need['topicid'] = $topicid;
                M('lionfish_comshop_survey_user_answer')->add($need);
            }
        }

        echo json_encode(array('code'=>1,'message'=>'提交成功'));die;
    }
}