<?php
namespace app\wxapi\controller;

use app\wxapi\model\Question as QuestionModel;
use app\wxapi\model\Log as LogModel;
use think\Controller;
use think\Request;
use think\Db;
use app\wxapi\model\User as UserModel;
use app\http\Question;
use app\wxapi\model\SettingModel;

class Index extends Controller
{
    /**
     * 显示资源列表 
     *
     * @return \think\Response
     */
    public function index()
    {
        // abort(404,'index error');
        // $request = new Request();
        // echo 'param: ' . json_encode($request->param()) . '<br/>';
        return json(['name'=>'index','data'=>input()]);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
         return json(['name'=>'create','data'=>input()]);
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
         return json(['name'=>'index','data'=>$request->post()]);
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        // dump(config());
        //
        // abort(404,'index error');
         return json(['name'=>'read','id'=>$id,'data'=>input()]);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
         return json(['name'=>'edit','id'=>$id,'data'=>input()]);
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
        return json(['name'=>'update','id'=>$id,'data'=>input()]);
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        
         return json(['name'=>'delete','id'=>$id,'data'=>input()]);
        //
    }


    private function _isPost()
    {
        if (!$this->request->isPost()) {
            return false;
        }
        return true;
    }
    public function updateUser()
    {
    	//Tony：Todo：这个函数没有被调用过
    	p_writeLog('admin', 'error!updateUser被调用了！');
        if ($this->_isPost()) {
        	p_writeLog("updateUser", 'ERROR! this function should not invocked?? ');
            $openid = $this->request->param('openid');
            $nick_name = $this->request->param('nick_name');
            $avatar_url = $this->request->param('avatar_url');
            $group_id = $this->request->param('group_id');
            // $ret = Db::query('select * from t_user where openid=$openid');
            $ret = Db::table('t_user')->where('openid',$openid)->find();
            if ($ret) {
            	//Tony:todo: error!  column error.
                $data = ['nick_name' => $nick_name,  'avatar_url' => $avatar_url,'group_id' => $group_id];
                Db::table('t_user')->where('openid',$openid)->update($data);
                return json(['name'=>'updateUser','msg'=>'更新User成功','data'=>'[]']);
            }else {
                $data = ['openid' => $openid,  'nick_name' => $nick_name, 'avatar_url' => $avatar_url, 'group_id' => $group_id];
                Db::table('t_user')->insert($data);
                return json(['name'=>'updateUser','msg'=>'新加User成功','data'=>'[]']);
            }
            
        }
        
        return json(['name'=>'updateUser','msg'=>'错误的请求']);
    }
    /**  num:100
     *  get method传入code，从服务器端获取openid
     *  最佳的方式是在服务器端修改code2openid,返回正确的json字符串.
     *  TODO: 当前根据debug和trace开关不一样，返回的字符串也不一样，存在严重隐患!!!
     * @param unknown_type $code
     */
    public function code2openid($code){
    	//return json(['openid'=>'11111','msg'=>'OK']);
    	
    	$code=$_GET["code"];
    	$appid="wx3914443b4f1f876d";
    	$secret="433e21ad5bbec5a1513ce091edb91008";
    	$c= file_get_contents("https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code=".$code."&grant_type=authorization_code");
    	
    	return json_encode($c);//对JSON格式的字符串进行编码
    }
    /*   num:101
     *   post method 传入openid,nickname,AvatarUrl,
     *   如果没有这个openid,则创建，否则更新这个nickname。
     *   dev use time:60min
     */
    public function updateUserInfo(){
    	//p_writeLog("test", 'updateUserInfo');
    	$openid = $this->request->post('openid','');
    	$nickname = $this->request->post('name','');
    	$avatarUrl = $this->request->post('avatar','');
    	
    	if ($openid && count($openid)>0){
    		$user =  UserModel::where('openid',$openid)->find();
    		if ($user){
    			//用模型能够
		    	$user->nick_name = $nickname;
		    	$user->avatar_url = $avatarUrl;
		    	//$user->nick_name = 'cool man';
		    	//p_writeLog("test", 'updateUserInfo1:'.' openid:'.$openid);
		    	$ret = $user->save();
		    	if ($ret!==false)
		    		return json(['code'=>'0', 'user_id'=>$user->user_id,'msg'=>'update success']);
		    	else{
		    		//p_writeLog($openid, 'updateUserInfo fail ret='.$ret.' msg='.$user->getError());
		    		//tony:这里如果数据不改变，不会保存成功，可以忽略。应该是thinkphp的问题
		    		return json(['code'=>'-1' ,'user_id'=> 0,'msg'=>'update fail or data same,can be ignore']);
		    	}
		    		
    		}else{
    			//add new one
    			$u = new UserModel();
    			$u->openid = $openid;
    			$u->nick_name = $nickname;
    			$u->avatar_url = $avatarUrl;
    			$u->group_id = 0;
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
    	}
    }
    /**
     *   no:90
     *   testapi()
     *   test only. 
     * 
     */
    public function testapi(){
    	$log = new LogModel();
    	$log->openid = 'admin';
    	$log->msg='test info';
    	$log->log_date = date('Y-m-d H:i:s',time());
    	if ($log->save())
    		return 'success';
    	else
    		return $log->getError();
    	
    	
    	$user = new UserModel();
    	$user->openid = 'xxxx';
    	$user->nick_name = 'tony';
    	$user->avatar_url = '';
    	$user->group_id = 0;
    	$user->registe_date = date('Y-m-d',time());
    	if ($user->save())
    	{
    		
    		return json(['code'=>'0', 'user_id'=>$user->id,'msg'=>'success']);
    	}
    	else
    		return json(['code'=>'-1' ,'user_id'=> 0,'msg'=>$user->getError()]);
    }
    /*
     *   no:91
     *   测试： 返回t_result所有数据
     */
    public function testapi2(){
    	$table= Db::name('t_result')->select();
    	dump($table);
    }
    /*
     *  no:92
    *   测试： 返回t_question所有数据
    */
    public function testapi3(){
    	$table= Db::name('t_question')->select();
    	dump($table);
    }
    
    
    /*    
    /* num:102
     * 
     *  获取grouplist, 
     *  
     *  return:  json
     *  dev use time： 7min
     */
    public function getgrouplist(){
    	$table = Db::table('t_group')->order('group_id');
    	//$table = Db::name('people');
    	$result = $table->select();
    	//dump($result);
    	return json($result);
    }
    /* num:103
     * 更新用户分组updateUserGroup()
     * get method 传入openid,group_id
    *
    *  return:  json
    *  dev use time： 10min
    */
    
    public function updateUserGroup(){
    	$openid = $this->request->get('openid','');
    	$group_id = $this->request->get('group','');
  
    	$user =  UserModel::where('openid',$openid)->find();
    	if ($user){
	        $data['group_id'] = $group_id;
	        //Tony:todo: 我觉得这句话很危险，因为$data的其他字段没有被赋予初始值。
	        Db::table('t_user')->where('openid',$openid)->update($data);
	        return json(['code'=>'0', 'user_id'=>$user->user_id,'msg'=>'update success']);
    	}else {
    		//add new one
    		//某些用户无法分组，结果方法是直接新增该用户。等待其稍后更新用户昵称和touxiang
    		p_writeLog('updateUserGroup', 'ERROR!openid='.$openid . ' group_id'.$group_id);//test
    		$u = new UserModel();
    		$u->openid = $openid;
    		$u->nick_name = '';
    		$u->avatar_url = '';
    		$u->group_id = $group_id;
    		$u->registe_date = date('Y-m-d',time());
    		//p_writeLog("test", 'updateUserInfo2');
    		if ($u->save())
    		{
    			//dump($user);
    			p_writeLog($openid, 'add new person with userid'.$u->id);
    			return json(['code'=>'0', 'user_id'=>$u->id,'msg'=>'add new success with blank name']);
    		}
    		else{
    			//p_writeLog("test", 'updateUserInfo4');
    			return json(['code'=>'-1' ,'user_id'=> 0,'msg'=>'create new fail']);
    		}    		
      }
    }    
    /*  num:104
     * 获得我的分数（我的荣誉） getRating()
     *  
     * 
    *   get method 传入openid
    *  return:  json
    *  注： 不返回level，level,需要在客户端由积分进行计算。胜率也在客户端计算
    *      如果Ranking返回0，表示没有比赛记录，也没有排名，需要显示“无”
    *  dev use time： 80min
    */
    public function getRating(){
        //select sum(score) total_score,sum(win) total_win,count(win) total_count from t_result where user_id=11;
    	//p_writeLog('test', 'getRating');//test
        $valid = true;
    	$openid = $this->request->get('openid','');
    	if (!$openid || count($openid)<=0)
    		$valid = false;
    	if ($valid){
    		$table= Db::table('v_user');
    		$find = $table->where('openid',$openid)->find();
    		//dump($find);
    		if (!$find){
    			$valid =false;
    		}
    		else{
    			$user_id = $find['user_id'];
    			$group_name = $find['group_name'];
    			if (!isset($group_name)) $group_name='';
    			//p_writeLog('test', $group_name);//test
    			//dump($user_id);
    			//查得总分，胜利场数，总场数
    			$query = Db::query('select sum(score) total_score,sum(win) total_win,count(num) total_count1,sum(num) total_count2 from t_result where user_id='.$user_id);
    			//dump($query);
    			if (count($query)>0){
    				$total_score =$query[0]['total_score'];
    				$total_win=$query[0]['total_win'];
    				$total_count1=$query[0]['total_count1'];	
    				$total_count2=$query[0]['total_count2'];
    				$total_count=$total_count1>$total_count2? $total_count1:$total_count2;
    				if ($total_score==null)  $total_score=0;	
    				if ($total_win==null)  $total_win=0;
    			}else{
    				$total_score =0;
    				$total_win=0;
    				$total_count=0;    				
    			}

    			//查个人排名
    			//todo:Tony 这里可以优化，在view v_rating1中添加自增加字段
    			//从而避免对数据进行循环。
				$table2 =Db::name('v_rating1');
				$tableRanking = $table2->select();
// 				echo '$tableRanking:';
// 				dump($tableRanking);
				$find_ranking=false;
				$order=1;
				foreach($tableRanking as $item) {
					if ($item['user_id']==$user_id){
						$find_ranking=true;
						break;
					}
					$order++;
				}   
				if (!$find_ranking) 
					$order=0;//没有参加比赛，无排名,客户端需要显示“无”
								
    			
    			return json(['code'=>'0', 'group_name'=>$group_name,'score'=>$total_score,
    					'Ranking'=>$order,'win_game'=>$total_win,'total_game'=>$total_count]);
    		}
    	}


    	return json(['code'=>'-1', 'group_name'=>'','score'=>0,
    			'Ranking'=>0,'win_game'=>0,'total_game'=>0]);    	


    }
    /*
     *   num:105
     * 获得排行 getRanking(),包括个人排行和分组排行
    * 
    *
    *  return:  json
    *  dev use time：40min 
    */
    public function getRanking(){
    	//Tony:只显示最靠前的150用户
    	//where nick_name is not null and trim(nick_name)<>''
    	//->where('nick_name','is not','null')
// 			$tablePerson = Db::name('v_rating1')->where('nick_name','NEQ','null')
// 			->where('trim(nick_name)','NEQ','""')
// 			->limit(150)->select();
			$str='select * from v_rating1 where nick_name is not null and trim(nick_name)<>"" limit 150;'; 
			$tablePerson = Db::query($str);
			$no =1;
			$arrPerson=array();
			foreach($tablePerson as $item){
// 				dump($item);
				$item['no']=$no;
				$arrPerson[]=$item;	
				$no++;
			}
			$tableGroup = Db::name('v_rating2')->where('group_id','not null')->
			where('group_id','neq','0')->select();
			$no =1;
			$arrGroup=array();
			foreach($tableGroup as $item){
				// 				dump($item);
				$item['no']=$no;
				$arrGroup[]=$item;
				$no++;
			}			
    		return json(['code'=>'0', 'msg'=>'success','world'=>$arrPerson,'group'=>$arrGroup]);

    }   
    /*
     *  从数据库获取练习题，其格式和对战题一样。每获取一次，训练次数加1
     *     'code':'0',
     *     'msg':'success',
     *     'question':{ //如果question为空对象{},表示所有题目下发完了
     *      'ask': '这是一道测试题',//问题描述
     *      'answer': [
     *       { 'answer': "回答一", 'right': false },//答案及是否是正确答案
     *       { 'answer': "回答二", 'right': true },
     *       { 'answer': "回答三", 'right': false },
     *       { 'answer': "回答四", 'right': false }
     *        ]}
     */
    public function getTraingQuestion(){
    	//1.从question中取出一个问题，然后随机抽取。
		$query = 'select question_id from t_question';
    	$ret = Db::query($query);
    	$num = count($ret);
    	$ques_index = $ret[rand(0,$num-1)]['question_id'];
    	//echo $num . ' ' . $ques_index . ' ';

    	//3.从t_question获取特定问题.
    	$model = QuestionModel::where('question_id',$ques_index)->find();
    	if ($model){
    		$obj = new Question();
    		//echo $model->ask;
    		$obj->ask = $model->ask;
    		$obj->answer1  = $model->answer_a;
    		$obj->answer2  = $model->answer_b;
    		$obj->answer3  = $model->answer_c;
    		$obj->answer4  = $model->answer_d;
    		$obj->right    = $model->answer_right;
    		$buildQuestion = $obj->buildJsonStr();
    		//dump($buildQuestion) ;
    		$finalStr = sprintf('{"code":"0","msg":"success","question":%s}',$buildQuestion);
    		
    		//3增加问题编号
    		$info = Db::name('t_setting');
    		$info->where(array('type_id' => 3))->setInc('switch',1);//hit字段+1
    		
    		//echo $finalStr;
    		return json($finalStr);//return question json.
    	}
    	else
    		return json('{"code":"-1","msg":"question not find","question":""}');
    }
    /*
     *  getGameStatus
     *  return value:0，暂停， 1，继续 ,-1错误
     */
    public function getGameStatus(){
    	$setting = SettingModel::getByType_id(2);
    	if ($setting){
    		$status = $setting->switch;
    		return $setting->switch;
    	}
    	else
    		return -1;
    }
    /*
     *  getAnnounce
     *  获取公告信息
    *  return json: switch 是否显示公告,1,0 
    *               content 公告内容
    */
    public function getAnnounce(){
    	$setting = SettingModel::getByType_id(1);
	    if ($setting){
		    $switch = $setting->switch;
		    $content = $setting->content;
		    $str = sprintf('{"code":"0","msg":"getAnnounce","switch":"%d","content":"%s"}',$switch,$content);
		    return json($str);
		}
		else
		{
		    return json('{"code":"-1","msg":"error","switch":"0","content":""}');
		}
    }    
}
