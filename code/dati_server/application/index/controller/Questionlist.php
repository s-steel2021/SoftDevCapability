<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;

class Questionlist extends Controller
{
    public function index()
    {
    	session_start();
    	trace('username:' .session('username'));
    	if (session('username')=='admin'){
    		$ret = Db::name('t_question')->paginate(20);
    		$this->assign('list', $ret);
    		return $this->fetch('');    		
    	}
    	else
    		$this->redirect('@index/login');
    	    	

    }

    public function add()
    {
    	//Tony：干什么用？感觉add和insert重复了
        if ($this->_isPost()) {
            $ask = $this->request->param('ask');
            $answer_a = $this->request->param('answer_a');
            $answer_b = $this->request->param('answer_b');
            $answer_c = $this->request->param('answer_c');
            $answer_d = $this->request->param('answer_d');
            $answer_right = $this->request->param('answer_right');
            $data = ['ask' => $ask, 'answer_a' => $answer_a, 'answer_b' => $answer_b, 'answer_c' => $answer_c, 'answer_d' => $answer_d, 'answer_right' => $answer_right, ];
            
            Db::name('t_question')->insert($data);

            $ret = Db::name('t_question')->paginate(20);
            $this->assign('list', $ret);
        }
      
        return $this->fetch('index');
    }

    public function insert()
    {
    	//Tony: 对外接口，用来插入数据的。
        if ($this->_isPost()) {
            $ask = $this->request->param('ask');
            $answer_a = $this->request->param('answer_a');
            $answer_b = $this->request->param('answer_b');
            $answer_c = $this->request->param('answer_c');
            $answer_d = $this->request->param('answer_d');
            $answer_right = $this->request->param('answer_right');
            $data = ['ask' => $ask, 'answer_a' => $answer_a, 'answer_b' => $answer_b, 'answer_c' => $answer_c, 'answer_d' => $answer_d, 'answer_right' => $answer_right, ];

            // 真正向服务器插入数据的时候，打开下面这行
            Db::name('t_question')->insert($data);
            return json(['name'=>'insert','data'=>$data]);
        }
      
        return json(['name'=>'insert','data'=>'插入错误']);
    }

    public function update()
    {
    	//Tony：好像是修改，没有调用了。
        if ($this->_isPost()) {
            $question_id = $this->request->param('question_id');
            $ask = $this->request->param('ask');
            $answer_a = $this->request->param('answer_a');
            $answer_b = $this->request->param('answer_b');
            $answer_c = $this->request->param('answer_c');
            $answer_d = $this->request->param('answer_d');
            $answer_right = $this->request->param('answer_right');
            $data = ['ask' => $ask, 'answer_a' => $answer_a, 'answer_b' => $answer_b, 'answer_c' => $answer_c, 'answer_d' => $answer_d, 'answer_right' => $answer_right, ];
            Db::name('t_question')
                ->where('question_id', $question_id)
                ->update($data);
            
            $ret = Db::name('t_question')->paginate(20);
            $this->assign('list', $ret);
        }
        
        return $this->fetch('index');
    }

    public function delete()
    {
    	//tony:没有其他地方调用把？ 列表模板中调动了该删除方法。
       // if ($this->_isPost()) {
            $question_id = $this->request->param('question_id');
            Db::table('t_question')->where('question_id',$question_id)->delete($question_id);
            return $this->success('删除成功', url('index/questionlist/index'));
      //  }
    }

    private function _isPost()
    {
        if (!$this->request->isPost()) {
            $ret = Db::query('select * from t_question');
            $this->assign('list', $ret);
            return false;
        }
        return true;
    }
    public function edit(){
    	//var_dump($this->request->param());
    	$question_id = $this->request->param('question_id');
    	//dump($question_id);
    	if ($question_id){
    		//this is edit
    		$question = Db::name('t_question')->where('question_id',$question_id)->find();
    		//dump($question);
    		$this->assign('question_id',$question_id);
    		$this->assign('ask',$question['ask']);
    		$this->assign('answer_a',$question['answer_a']);
    		$this->assign('answer_b',$question['answer_b']);
    		$this->assign('answer_c',$question['answer_c']);
    		$this->assign('answer_d',$question['answer_d']);
    		$this->assign('answer_right',$question['answer_right']);    		
    	}else{
    		$this->assign('question_id','');
    		$this->assign('ask','');
    		$this->assign('answer_a','');
    		$this->assign('answer_b','');
    		$this->assign('answer_c','');
    		$this->assign('answer_d','');
    		$this->assign('answer_right','');
    	}
    	return $this->fetch('edit');
    }
    public function doedit(Request $request){
    	
         if ($this->_isPost()) {
         	$question_id = $this->request->param('question_id');
         	//dump($question_id);
         	$ask = $this->request->param('ask');
         	$answer_a = $this->request->param('answer_a');
         	$answer_b = $this->request->param('answer_b');
         	$answer_c = $this->request->param('answer_c');
         	$answer_d = $this->request->param('answer_d');
         	$answer_right = $this->request->param('answer_right');    
         	$data = ['ask' => $ask, 'answer_a' => $answer_a, 'answer_b' => $answer_b, 'answer_c' => $answer_c, 'answer_d' => $answer_d, 'answer_right' => $answer_right, ];
         	if ($question_id) {
				//edit
         		$ret = Db::name('t_question')->where('question_id',$question_id)->update($data);
         		return $this->success('编辑成功',url('index/questionlist/index'));
         	}else{
         		//create new
         		$ret = Db::name('t_question')->insert($data);
         		return $this->success('新增成功',url('index/questionlist/index'));
         	}
        }
        return $this->error('添加失败',url('index/questionlist/index'));
    	
    }
}
