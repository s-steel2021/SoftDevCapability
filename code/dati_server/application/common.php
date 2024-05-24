<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use app\wxapi\model\Log as LogModel;

/*
 *   全局的日志函数，可以写入日志，帮助分析系统bug和error。   
 *   p_writeLog
 */
function p_writeLog($openid,$msg){
	//TODO: TONY开发完成之后再加入try catch
// 	try{
		if ($msg==null)
			$msg='null';
		$log = new LogModel();
		$log->openid = $openid;
		$log->msg=$msg;
		$log->log_date = date('Y-m-d H:i:s',time());
		if ($log->save())
			return 0;
		else{
			trace('write log fail:'.$msg);
			return -1;
		}		
// 	}catch(Exception $err){
// 		echo $e->getMessage();
		
// 	}

  
	 	
}