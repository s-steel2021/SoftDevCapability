<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;

class Userlist extends Controller
{
    public function index()
    {
    	session_start();
    	trace('username:' .session('username'));
    	if (session('username')=='admin'){
    		$txtsearch = $this->request->param('txtsearch');
    		if ($txtsearch){
    			$ret = Db::name('v_user')->where('nick_name','like','%'.$txtsearch.'%')->paginate(20);
    			$this->assign('list', $ret);
    			$this->assign('txtsearch',$txtsearch);
    			return $this->fetch('');
    			
    		}else {
    			$ret = Db::name('v_user')->paginate(20);
    			$this->assign('txtsearch','');
    			$this->assign('list', $ret);
    			return $this->fetch('');
    		}
    			
    		
	
    	}
    	else
    		$this->redirect('@index/login');
    	    	

    }
    /*
     *  模板列表中删除触发该function
    */    
    public function delete()
    {
    	//tony:列表模板中调动了该删除方法。
    	$user_id = $this->request->param('user_id');
    	
    	if ($user_id>0){
    		//1.首先删除t_result中相关记录
    		Db::table('t_result')->where('user_id',$user_id)->delete();
    		
    		//2.删除t_user中的记录
    		Db::table('t_user')->where('user_id',$user_id)->delete();
    		return $this->success('删除成功', url('index/userlist/index'));
    	}

    }
    /*
     *  模板编辑触发该function
     */
    public function edit(){
    	//var_dump($this->request->param());
    	$user_id = $this->request->param('user_id');
    	//dump($question_id);
    	if ($user_id){
    		//this is edit
    		$user = Db::name('v_user')->where('user_id',$user_id)->find();
    		//dump($question);
    		$this->assign('user_id',$user_id);
    		$this->assign('openid',$user['openid']);
    		$this->assign('nick_name',$user['nick_name']);
    		$this->assign('avatar_url',$user['avatar_url']);
    		$this->assign('group_id',$user['group_id']);
    		$this->assign('group_name',$user['group_name']);    
    		
    		$grouplist = Db::name('t_group')->order('group_id')->select();
    		$this->assign('grouplist',$grouplist);
    		return $this->fetch('edit');
    	}else
    		return $this->error('编辑失败',url('index/userlist/index'));
    }
    public function doedit(Request $request){
    	
         	$user_id = $this->request->param('user_id');
         	$selgroup = $this->request->param('selgroup');
//          	dump($user_id);
//          	dump($selgroup);
         	$data = ['group_id' => intval($selgroup) ];
         	if ($user_id) {
				//edit
         		$ret = Db::name('t_user')->where('user_id',$user_id)->update($data);
         		return $this->success('编辑成功',url('index/userlist/index'));
         	}
         	return $this->error('添加失败',url('index/userlist/index'));
         	
        }
    	


}
