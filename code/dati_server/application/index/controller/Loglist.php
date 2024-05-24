<?php
namespace app\index\controller;

use think\Controller;
use think\Db;

class Loglist extends Controller
{
    public function index()
    {
    	session_start();
    	trace('username:' .session('username'));
    	if (session('username')=='admin'){
    		$ret = Db::name('t_log')->order('log_id','desc')->paginate(20);
    		$this->assign('list', $ret);
    		return $this->fetch('');    		
    	}
    	else
    		$this->redirect('@index/login');
    }



}
