<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;
use app\wxapi\model\User;

class Grouplist extends Controller
{
    public function index()
    {
    	session_start();
    	trace('username:' .session('username'));
    	if (session('username')=='admin'){
    		$ret = Db::name('t_group')->order('group_id')->paginate(20);
    		$this->assign('list', $ret);
    		return $this->fetch('');    		
    	}
    	else
    		$this->redirect('@index/login');
    	    	

    }

    public function add()
    { //TODO: TONY ???? 这里的代码比较奇怪，分组管理和问题添加应该没有关系。
        if ($this->_isPost()) {
            $ask = $this->request->param('ask');
            $answer_a = $this->request->param('answer_a');
            $answer_b = $this->request->param('answer_b');
            $answer_c = $this->request->param('answer_c');
            $answer_d = $this->request->param('answer_d');
            $answer_right = $this->request->param('answer_right');
            $data = ['ask' => $ask, 'answer_a' => $answer_a, 'answer_b' => $answer_b, 'answer_c' => $answer_c, 'answer_d' => $answer_d, 'answer_right' => $answer_right, ];
            
            Db::name('t_group')->insert($data);

            $ret = Db::query('select * from t_group');
            $this->assign('list', $ret);
        }
      
        return $this->fetch('index');
    }

    public function insert()
    {
        if ($this->_isPost()) {
            $group_name = $this->request->param('group_name');
            $data = ['group_name' => $group_name];

            // 真正向服务器插入数据的时候，打开下面这行
            // Db::name('t_group')->insert($data);
            return json(['name'=>'insert','data'=>$data]);
        }
      
        return json(['name'=>'insert','data'=>'插入错误']);
    }

    public function update()
    {
        if ($this->_isPost()) {
            $group_id = $this->request->param('group_id');
            $group_name = $this->request->param('group_name');
            $data = ['group_name' => $group_name];
            if ($group_id == '') {
                Db::name('t_group')->insert($data);
                // return json(['name'=>'update','msg'=>'新增战队', 'data' => $data]);
            }else {
                Db::name('t_group')
                    ->where('group_id', $group_id)
                    ->update($data);
                // return json(['name'=>'update','msg'=>'更新战队', 'data' => $data]);
            }
            
            
            $ret = Db::name('t_group')->paginate(20);
            $this->assign('list', $ret);
        }
        
        return $this->fetch('index');
    }

    public function delete()
    {
        if ($this->_isPost()) {
        	
            $group_id = $this->request->param('group_id');
            //tony：添加删除前的判断，避免删除导致垃圾数据
            $find = Db::name('t_user')->where('group_id',$group_id);
            if ($find){
            	$this->error('该分组已经使用，不能删除', url('index/grouplist/index'));
            }else{
            	$ret=Db::table('t_group')->where('group_id',$group_id)->delete();
            	$this->success('删除成功', url('index/grouplist/index'));
            }

        }
        
        // return $this->fetch('index');
    }

    private function _isPost()
    {
        if (!$this->request->isPost()) {
            $ret = Db::query('select * from t_group');
            $this->assign('list', $ret);
            return false;
        }
        return true;
    }
}
