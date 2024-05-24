<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
//use think\Log;
use think\facade\Log;
use app\wxapi\model\User as UserModel;

// 测试数据库链接
// http://localhost/dati_server/public/index.php/index/test
class Test extends Controller{
	
	public function index(){
		//return 'test index';
		echo 'display all data in people table<br/>';
		$ret = Db::query('select * from t_group');
		dump($ret);
		 
	}
	
	
	public function test1(){
		//return 'test index';
		echo 'insert group <br/>';
		$ret = Db::execute("insert into t_group (group_name) values ('分组3')");
		dump($ret);
			
	}	
	public function test2(){
		$info = Db::name('t_setting');
		$info->where(array('type_id' => 3))->setInc('switch',1);//hit字段+1
		$arr=$info->find(3); //在showInfo.html输出
		dump($arr);
	}
	public function test3(){
		//return 'test index';
		echo 'display all data in question table<br/>';
		$ret = Db::query('select * from t_question');
		dump($ret);
	}	
	public function test4(){
		//return 'test index';
		echo 'display all data in setting table<br/>';
		$ret = Db::query('select * from t_setting');
		dump($ret);
	}	  
	public function test5(){
		//return 'test index';
		echo 'display all data in v_rating1 table<br/>';
		$ret = Db::query('select * from v_rating1');
		dump($ret);
	}	
	public function test6(){
		//return 'test index';
		echo '删除test生成的日志<br/>';
		$ret = Db::execute('delete from t_log where openid="test"');
		dump($ret);
	}	
	public function test7(){
		//return 'test index';
		echo 'set group_id =0<br/>';
		//$ret = Db::execute('delete from t_user where user_id=13');
		$ret = Db::execute('update t_user set user_id=13 where user_id=18');
		//'update t_user set group_id=5 where user_id=14'
		dump($ret);
	}	
	public function test8(){
		//return 'test index';
// 		Log::write('abc');
		Log::error('错误信息1');
		Log::error('错误信息2');
		Log::error('错误信息3');
		Log::info('小心小心1');
		Log::info('小心小心2');
		Log::info('小心小心3');
		Log::record('测试日志信息');
		Log::record("== xxx更新失败 ==", 'DEBUG'); 
		echo 'set group_id =0<br/>';
		$ret = Db::execute('delete from t_user where user_id<=11');
		//'update t_user set group_id=5 where user_id=14'
		dump($ret);
	}
	public function test9(){
		//return 'test index';
		echo '删除t_question生成的日志<br/>';
		$ret = Db::execute('delete from t_question where openid="test"');
		dump($ret);
	}	
	public function test10(){
		$u = new UserModel();
		$u->openid = 'AAAAAA';
		$u->nick_name = 'ʚLTTɞ💜';
		$u->avatar_url = '';
		$u->group_id = 44;
		$u->registe_date = date('Y-m-d',time());
		//p_writeLog("test", 'updateUserInfo2');
		if ($u->save())
		{
			//dump($user);
			//p_writeLog("test", 'updateUserInfo3');
			return json(['code'=>'0', 'user_id'=>$u->id,'msg'=>'success']);
		}
		else{
			//p_writeLog("test", 'updateUserInfo4');
			return json(['code'=>'-1' ,'user_id'=> 0,'msg'=>'create new fail']);
		}		
	}
	public function arraytest(){
		$u = ['a'=>111,'b'=>222];
		$x = ['c'=>333,'d'=>444];
		dump($u);
	}
}