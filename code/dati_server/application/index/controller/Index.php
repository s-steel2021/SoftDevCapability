<?php
namespace app\index\controller;


use think\facade\Env;
use app\http\model\People;
use think\Controller;
use think\Session;
use think\Db;
use app\wxapi\model\SettingModel;

class Index extends Controller
{
    public function index()
    {
        //return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
        session_start();
        trace('username:' .session('username'));
        if (session('username')=='admin'){
        	$ret= Db::query('select * from (select count(t_user.user_id) user_num from t_user) as a,'.
			 '(select count(t_group.group_id) group_num from t_group) as b,'.
			 '(select count(t_question.question_id) ques_num from t_question) as c,'.
			 '(select sum(t_result.num)  result_num from t_result) as d,'.
			 '(select switch  train_num from t_setting where type_id=3) as e,'.
        	'(select switch  gamestatus from t_setting where type_id=2) as f;');
        	$this->assign('user_num',$ret[0]['user_num']);
        	$this->assign('group_num',$ret[0]['group_num']);
        	$this->assign('ques_num',$ret[0]['ques_num']);
        	$this->assign('result_num',$ret[0]['result_num']);
        	$this->assign('train_num',$ret[0]['train_num']);
        	$this->assign('gamestatus',$ret[0]['gamestatus']);
        	return $this->fetch('');
        }
        else
        	$this->redirect('@index/login');
        
        //$username = session('username');
        //$username = session('username1');
        //trace('username:' .$username);
        
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }
    public function test(){
    	//return Env::get('runtime_path') . 'worker.pid';
    	$people = new People();
    	return $people->getall();
    }
    public function logout(){
    	session('username','xxx');
    	$this->redirect('@index/login');
    }
    public function cleardb(){
    	 $ret= Db::execute('delete from t_result');
    	 $ret= Db::execute('delete from t_user');
    	 return $ret;
    }
    public function switchGameStart(){
 
    	error_log("switchGameStart start:");
    	//TODO: TONY  create a bug
    	$x = SettingModel1::SettingModel();
    	
		 $setting = SettingModel::getByType_id(2);
		 if ($setting){
		 	$status = $setting->switch;
		 	//echo 'game status before switch:'. $status;
		 	$setting->switch = $status? 0 : 1 ;
		 	$setting->isUpdate()->save();
		 }
		 return 0;
    }
}
