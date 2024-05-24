<?php
namespace app\index\controller;

use think\Controller;
use think\Validate;


class Login extends Controller
{
    public function index()
    {
    	trace('login/index');
        return $this->fetch('');
    }

    public function doLogin(){
    	$nickname = $this->request->post('nickname','');
    	$password = $this->request->post('password','');
    	
    	trace('abc');
    	
    	$validate = Validate::make([
    			'username' => 'require|min:3',
    			'password' => 'require|min:3',
    			], [
    			'username.require' => '登录账号不能为空！',
    			'username.min'     => '登录账号长度不能少于3位有效字符！',
    			'password.require' => '登录密码不能为空！',
    			'password.min'     => '登录密码长度不能少于3位有效字符！',
    			]);
    	$data = [
    	'username' => $nickname,
    	'password' => $password,
    	];
    	$validate->check($data) || $this->error($validate->getError());

    	
    	if ($nickname=='admin' && $password=="admin420") {
    		session_start();
    		Session('username',$nickname);
    		Session('password',$password);
    		setcookie('userdatax','xxxxxx',time()+60*60*24*7);
    		$this->redirect('@index/index');
    	}else
    		return $this->error('账号密码错误');
    }


}
