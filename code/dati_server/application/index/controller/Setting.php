<?php
namespace app\index\controller;


use think\facade\Env;
use app\http\model\People;
use think\Controller;
use app\wxapi\model\SettingModel;
use think\Request;

class Setting extends Controller
{
    public function index()
    {
        session_start();
        trace('username:' .session('username'));
        if (session('username')=='admin'){
        	$setting = SettingModel::getByType_id(1);
        	if ($setting){
        		$switch = $setting->switch;
        		$content = $setting->content;
        		
        	}
        	else
        	{
        		$switch=0;
        		$content='';
        	}
        	$this->assign('switch',$switch?'checked':'');
        	$this->assign('content',$content);
        	return $this->fetch('');
        }
        else
        	$this->redirect('@index/login');
    }

	public function save(Request $request){

		$chkSwitch = $request->param('chkSwitch');
		$txtContent = $request->param('txtContent');

		
		$setting = SettingModel::getByType_id(1);
		if ($setting){
			$setting->switch = $chkSwitch=='on'? 1 : 0 ;
			$setting->content =$txtContent ;
			$setting->isUpdate()->save();
		}		
		return $this->success('保存成功', 'index/setting/index');
		
	}
}
