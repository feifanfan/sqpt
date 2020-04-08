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

    /**
     * 添加问题
     */
    public function addSurveyQuestion(){
        if(empty(I('get.topicid'))){
            show_json(0, '未定义主题id');
            die;
        }
        if(IS_POST){
            $data = I('post.');
            foreach ($data['data'] as $item){
                $question['topicid'] = I('get.topicid');
                $question['question'] = $item['name'];
                M('lionfish_comshop_survey_question')->add($question);
            }
            show_json(1,array('url' => '/seller.php?s=/Survey/addSurveyQuestion/list'));
        }
        $this->assign('topicid',I('get.topicid'));
        $this->display();
    }

    /**
     * 问题列表
     */
    public function surveyQuestionList(){
        $topicid = I('get.topicid');
        $list = M('lionfish_comshop_survey_question')->where(['topicid'=>$topicid])->select();
        $this->assign('topicid',$topicid);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 编辑问题
     */
    public function editSurveyQuestion(){

    }
    public function delSurveyQuestion(){
        $questionid = I('get.questionid');
        M('lionfish_comshop_survey_question')->where(['id'=>$questionid])->delete();
        show_json(1,array('url' => '/seller.php?s=/Survey/surveyQuestionList/questionid/'.$questionid));
    }
    /**
     * 添加答案
     */
    public function addSurveyAnswer(){
        if(IS_POST){
            $questionid = I('get.questionid');
            $answer['topicid'] = M('lionfish_comshop_survey_question')->where(['id'=>$questionid])->getField('topicid');
            $data = I('post.');

            foreach ($data['data'] as $key=>$item){
                $answer['questionid'] = I('get.questionid');
                $answer['answer_option'] = $item['answer_option'];
                $answer['answer_image'] = $data['thumbs'][$key];
                M('lionfish_comshop_survey_answer')->add($answer);
            }
            show_json(1,array('url' => '/seller.php?s=/Survey/surveyAnswerList/questionid/'.$questionid));
        }
        $this->display();
    }

    /**
     * 答案列表
     */
    public function surveyAnswerList(){
        $questionid = I('get.questionid');
        $list = M('lionfish_comshop_survey_answer')->where(['questionid'=>$questionid])->select();
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 编辑答案
     */
    public function editSurveyAnswer(){

    }

    /**
     * @description：查看数据统计
     * @date:2020/3/24
     * @author ff
     */
    public function statistics(){
        $this->display();
    }
}