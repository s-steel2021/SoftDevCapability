<?php
namespace app\http;
/***
 *
*    Authoer:Tony Peng
*    Create: 2019-4-1
*    usage:  Main file of Workerman,handle all message of workerman & client.
*/
use think\worker\Server;
//use think\worker\Timer;
use app\wxapi\model\GameResult;
use app\wxapi\model\User as UserModel;
use think\Db;
use app\wxapi\model\SettingModel;
use workerman\Lib\Timer;
use think\facade\Log;
use think\facade\Env;

class Worker extends Server
{
	//todo:配置 =>   配置worker端口
    protected $socket = 'websocket://0.0.0.0:2345'; //正式服务器用2345端口,wss映射9001端口
//     protected $option = [
//     'count'		=> 1,
// //    'pidFile'   => Env::get('runtime_path') . 'worker.pid',
//     'name'		=> 'think9',
// //    'daemonize' =>true
//     ];
//    protected $daemonize =true;
     
    //等待用户列表，array of Player
    protected $WaitList; 
    
    //房间列表， array of Room
    protected $RoomList;
    
    //从数据库装入的question list
    protected $QuestionList;
    
    protected $CurrentQuestionIndex; //从0~count-1 loop
    
    protected $uidConnectoins;  //临时保存的连接
    
    protected $uid = 0; // int number, auto increase.
    //最多支持的房间数量
    const MAX_ROOM_NUM = 500; 
    
    public $gameRunning = true;
    
    
    //two reason which cause send600 and room close
    const REASON_NORMAL_600 = "normal";
    const REASON_CONN_CLOSE_600 = "conn_close";
    /**
     *   构造函数
     *   
     */
    
    public function __construct() {
    	parent::__construct();
	//    1.初始化 WaitList
		$this->WaitList = array();
    //    2.初始化 RoomList
    	$this->RoomList = array();
    //    3.初始化参数，包括暂停状态
    	// 该参数应该从数据库中读取，并且定时刷新
    	$this->gameRunning = $this->getGameStatus();
    //  
    	$this->uidConnectoin = array();
        //初始化问题列表
        $this->loadQuestions();
        
        p_writeLog('admin','Worker启动!');
    }
    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {
       // $connection->send('getmsg:' . strlen($data));
//        echo '收到信息:'+$data;
        
          //  1.解析收到的消息，区分其内容
              $actionCmd = $this->parseDataType($data);
              echo "getmsg cmd:" .  $actionCmd . "\n";
              switch($actionCmd)
               {
                   case '100':
                      $this->handleData100($connection,$data);
                 		break;
                   case '101':
                   	$this->handleData101($connection,$data);
                       break;
                   case '300':
                   	$this->handleData300($connection,$data);
                       break;
                   case '500':
                   	$this->handleData500($connection,$data);
                       break;       
                   case '999':
                   	$this->handleData999($connection,$data);
                   	   break;
              	default:
              		echo "warning: unknown message" . $data . "\n";
               }  
        
        
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
    	echo "onConnect begin.\n";
    	//给当前$conection生成uid，并存入连接列表
    	if (!isset($connection->uid)){
    		$connection->uid = $this->uid;
    		$this->uid++;
    		$this->uidConnectoin[]=$connection;
    	}
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {
    	echo "onClose begin.\n";
    	//TODO: TEST source code.
//     	$find = array_search($connection, $this->uidConnectoins,true);
//     	if ($find===FALSE)
//     		echo 'onclose not found.';
//     	else 
//     		echo 'onclose  found:'. $find;

    	
		//1.查找waitList中，是否有该$connection,如果有，直接删除即可
		$id=0;
		foreach ($this->WaitList as $player){
			if ($player->uid ==$connection->uid){
				echo "count of waitlist:" . count($this->WaitList) . " \n";
				array_splice($this->WaitList, $id,1);
				echo "count of wailist(del):" . count($this->WaitList) . " \n";
				unset($player);
				return;
			}
			$id++;
		}
		
		//2.查找当前RoomList中，是否有该$connection。如果有，发送600结束。1秒钟后关闭连接，关闭Room
		foreach($this->RoomList as $room){
			//房间正在关闭中，直接忽略.
			if ($room->closing) continue;
			if($room->player1->uid==$connection->uid ||
			   $room->player2->uid==$connection->uid) {
				if ($room->player1->uid==$connection->uid){
					$livePlayer=$room->player2;
					$deadPlayer=$room->player1;
					//$room->player1=null;
				}else{
					$livePlayer=$room->player1;
					$deadPlayer=$room->player2;
					//$room->player2=null;
				}
				$this->send600($livePlayer, $room, self::REASON_CONN_CLOSE_600);
				unset($deadPlayer);
				
				//关闭连接，关闭房间
				$this->closeConnAndRoom($room);
			}
		}
		
    	//P.S. 特殊情况，如果断电断网，则onClose不会启动。
    	
    	//TODO:send600之后，client可能主动断掉链接，因此需要特殊处理??
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "ERROR! onError $code $msg\n";
        Log::error("ERROR! onError $code $msg\n");
        //TODO: 如何处理？
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
    	//每10秒刷新一次,正式版本，为60s
    	$time_interval =60;  //10s
		Timer::add($time_interval,function(){
			// 该参数应该从数据库中读取，并且定时刷新
			$newstate = $this->getGameStatus();
			if ($newstate!=$this->gameRunning){
				echo "Game state changed!" .intval($newstate) . "\n";
				$this->gameRunning = $newstate;
			}
			
			
		},array(),true);
    }
    /*
     *   100 配对请求
     * 
     */
    public function handleData100($connection,$data){
    	echo "handleData100 begin.\n";
    	if (strlen($data)<=0 ||
    			strpos($data,'socketCmd')===false ||
    			strpos($data,'socketAction')===false ||
    			strpos($data,'data')===false ||
    			strpos($data,'openid')===false){
    		echo 'handleData100 error data!';
    		$this->send209($connection);
    		dump($data);
    		return 0;  //error!
    	}
    	$arr = json_decode($data,true);
    	
		//1.取出用户的openid,$connection中的uid，如果uid不存在，输出，并抛弃这个数据
		$openid = $arr['data']['openid'];
		$uid =  $connection->uid;
		echo "user with openid come:" .$openid ."\n";
		Log::info("user with openid come:" .$openid ."\n");
		
		//2.将用户的$connection和openid封装成Player
		$player=new Player();
		$player->connection = $connection;
		$player->openid = $openid;
		$player->uid = $uid;
		
		//从数据库读取用户昵称等信息
    	$user = UserModel::getByOpenid($player->openid);
    	if ($user){
    		$player->avatarUrl =$user->avatar_url;
    		$player->nickName = $user->nick_name;   
    		$player->user_id  = $user->user_id; 
    		echo "user with nick name come:" .$player->nickName ."\n";
    		Log::info("user with nick name come:" .$player->nickName ."\n");    		
    	}else
    	{
    		echo "ERROR!! can not find the person by openid.\n";
    		echo "openid:" . $player->openid . ' \n'; 
    		$player->avatarUrl ="";
    		$player->nickName = "Tony Test";
    		$player->user_id =0;  //error condition
    	}

		
    	//3.如果RoomList满，然后
    	//回复“202服务器忙"，客户端直接返回
    	//输出当前有多少桌数据
    	$roomNum = count($this->RoomList);
    	$logout = "Room number:".$roomNum ."\n";
    	Log::info($logout);
    	echo $logout;
    	if (count($this->RoomList)>=self::MAX_ROOM_NUM){
    		$this->send202($player);
    		return;
    	}
    	
    	//4. 如果比赛暂停，则回复203，比赛未开始。client直接返回
    	//TODO: 需要定时刷新游戏状态
		if (!$this->gameRunning){
			$this->send203($player);
			return;
		}
			
		//5.检查WaitList中是否有合适Player，有的话，
		//  创建一个Room，把两个Player放入Room
		//  回复 200 配对成功    
		if (count($this->WaitList)>=1){
			//5.1 检查该用户是否已经存在，如果存在，也直接返回,不需要重复存放waitlist
			//NOTE:如果取消这里的判断，程序其他地方会出问题！！！
			foreach($this->WaitList as $existPlayer){
				if ($existPlayer->openid==$player->openid){
					echo "same openid,ignore it.\n";
					$this->send201($player);
					return ;
				}
			}
			
			$new_room = new Room();
			
			//取出第一个player，放入新房间
			$b = $this->WaitList[0];
			$new_room->addPlayer($player, $b);
			
			//从WaitList删除第一个player
			array_splice($this->WaitList, 0,1);
			echo "waitlist num:". count($this->WaitList). "\n";
			
			//添加到RoomList
			$this->RoomList[]=$new_room;
			
			//回复200，配对成功
			$this->send200($player,$b,$new_room->RoomNum);  //todo: add parameter;
			$this->send200($b,$player,$new_room->RoomNum); 
			p_writeLog($player->openid,'对战开始.另外一个用户'.$b->openid);
		}else
		{
			// 6. 如果没有合适的Player，则把Player放入WaitList。
			//  同时回复201，请client等待			
			$this->WaitList[] =  $player;
			echo "waitlist num:". count($this->WaitList). "\n";
			$this->send201($player);
		}


    }    
    /*
     *   101   40s 等待超时
    *    在waitilist中删除这个Player
    */    
    public function handleData101($connection,$data){
    	echo "handleData101 begin.\n";
    	if (strlen($data)<=0 ||
    			strpos($data,'socketCmd')===false ||
    			strpos($data,'socketAction')===false ||
    			strpos($data,'data')===false ||
    			strpos($data,'openid')===false){
    		echo 'handleData101 error data!';
    		$this->send209($connection);
    		return 0;  //error!
    	}
    	$arr = json_decode($data,true);
    	 
    	//1.取出用户的openid,$connection中的uid，如果uid不存在，输出，并抛弃这个数据
    	$openid = $arr['data']['openid'];
    	$uid =  $connection->uid;
    	
    	//在waitilist中删除这个Player
    	$i =0;
    	foreach($this->WaitList as $player){
    		if ($player->openid == $openid){
    			array_splice($this->WaitList, $i,1);
    			return ;
    		}
    		$i++;
    	}
    	    	
    }
    

   

    /**
     *   Parse data and return action command.
     *   
     * @param json $data
     * return data:  action command.
     *               if there is error, return zero.
     */
    public function parseDataType($data){
    	//test
    	if ($data && strlen($data)>0)
    		Log::info('parseDataType coming data :'.$data);
    	echo "data=" . $data ."\n";//test
    	//1.判断数据完整性
    	if (strlen($data)<=0 || 
    		strpos($data,'socketCmd')===false ||
    		strpos($data,'socketAction')===false  ){
    		
    		echo "coming data error1 \n";
    		Log::error('coming data error1:'.$data);
    		return 0;  //error!
    	}
    	
    	$arr = json_decode($data,true);
    	if ($arr!=null){
    		//echo "socketCmd=" .$arr['socketCmd'] ."\n";//test
    		//var_dump($arr);
    		return $arr['socketCmd'];
    	}
    	else{
    		echo "coming data error2 \n";
    		Log::error('coming data error2:'.$data);
    		return 0;//error!
    	}
    		
    }
    
 
    
    /**
     *  给player发送200，配对成功,client准备开始比赛
     */
    public function send200(Player $player1, Player $player2,$RoomNum){
    	echo "send200 begin.\n";
    	$str =sprintf( '{"code": 0,'.
    		 '"msg": "successful",'.
    		'"socketCmd": 200,"action": "match_room",'.
    		'"data": { ' .
    		'"roomName": "%d",'.
    		'"player_other": { '. 
    		'"openid": "%s", '.
    		'"avatarUrl": "%s", '. 
    		'"nickName": "%s"}  }}',
    			$RoomNum,$player2->openid,$player2->avatarUrl,$player2->nickName);    
    	$player1->connection->send($str);
    }
    
    /**
     *  给player发送201，没有合适的玩家,请client等待
     */
    public function send201(Player $player){
    	echo "send201 begin.\n";
    	$str = '{"code": 0, "msg": "please_wait", "socketCmd": 201,'.
               '"action": "match_room_wait", "data": {}  }';
    	$player->connection->send($str);
    }
    /**
     *  给player发送202服务器繁忙，超负荷。client返回
     */
    public function send202(Player $player){
    	echo "send202 begin.\n";
    	$str = '{"code": 0, "msg": "server_busy", "socketCmd": 202,'.
    	'"action": "match_room_server_busy", "data": {}  }';
    	$player->connection->send($str);    	 
    }
    
    /**
     *  给player发送203，比赛未开始。client返回
     */
    public function send203(Player $player){
    	echo "send203 begin.\n";
    	$str = '{"code": 0, "msg": "game_not_start", "socketCmd": 203,'.
    	'"action": "match_room_game_not_start", "data": {}  }';
    	$player->connection->send($str);    
    }   
    /**
     *  给player发送209，数据包错误
     */
    public function send209($connection){
    	echo "send209 begin.\n";
    	$str = '{"code": 209, "msg": "error data package", "socketCmd": 209,'.
    			'"action": "error_data", "data": {}  }';
    	$connection->send($str);
    }     
    /**
     *
     *
     * @param unknown_type $connection
     * @param unknown_type $data
     */
    public function handleData300($connection,$data){
    	echo "handleData300 begin.\n";
    	if (strlen($data)<=0 ||
    			strpos($data,'socketCmd')===false ||
    			strpos($data,'socketAction')===false ||
    			strpos($data,'data')===false ||
    			strpos($data,'roomName')===false ){
    		echo 'handleData300 error data!';
    		return 0;  //error!
    	}    	
    	
    	//1.检查房间的情况，符合条件，则400发题
    	$arr = json_decode($data,true);
    	$openid = $arr['data']['openid'];
    	$roomName = $arr['data']['roomName']; 
    	$findRoom=null;
    	foreach($this->RoomList as $room){
    		if ($roomName==$room->RoomNum){
    			if ($room->player1->openid == $openid || $room->player2->openid==$openid){
    				$findRoom = $room;
    				break;
    			}
    		}	
    	}
    	if ($findRoom == null){
    		echo 'ERROR:handleData300 error no this room or player!';
    		return 0;  //error!    		
    	}
    	
    	if ($findRoom->round == 0){
    		if ($findRoom->question==null){
    			$question = $this->pickOneQuestion();
    			$findRoom->question = $question;
    		}else 
    			$question =$findRoom->question;
   
    		$this->send400($connection,$findRoom,$question); //begin to send question(发题)
    	}
    	 

    }

    /**
     *   loadQuestions
     *   useage: load all questions from DB.
     */
    public function loadQuestions(){
    	echo "loadQuestions begin.\n";
    	$this->QuestionList =array();

    	//todo:  load data from db.
    	$list = Db::name('t_question')->select();
    	foreach($list as $item){
    		$obj = new Question();
    		$obj->ask = $item['ask'];
    		$obj->answer1  = $item['answer_a'];
    		$obj->answer2  = $item['answer_b'];
    		$obj->answer3  = $item['answer_c'];
    		$obj->answer4  = $item['answer_d'];
    		$obj->right    = $item['answer_right'];
    		$this->QuestionList[]=$obj;
    	}
    	$this->CurrentQuestionIndex =rand(0,count($list)-1);
		echo "load total". count($list) . " questions.\n";
    }
    
    /**
     * 
     * pickOneQuestion
     * usage: pick one question, and $CurrentQuestionIndex++
     */
    public function pickOneQuestion(){
    	if (count($this->QuestionList)<=0)
    		return null;
    	
    	$qu = $this->QuestionList[$this->CurrentQuestionIndex];
    	$this->CurrentQuestionIndex++;
    	$this->CurrentQuestionIndex>=count($this->QuestionList) && $this->CurrentQuestionIndex=0;
    	return $qu;
    }
    
    /**
     * send400  send question 发题
     * 
     */
    public function send400($connection,Room $room,Question $question){
    	echo "send400 begin.\n";
    	$questionStr = $question->buildJsonStr();
    	$choicePlayer1 = $room->player1->buildJsonAnswerStr();
    	$choicePlayer2 = $room->player2->buildJsonAnswerStr();
    	$strBase = '{"code": 0, "msg": "successful", "socketCmd": 400,'.
    	'"action": "question", "data": {'.
    	'"roomName":"%d","question":%s,"choicePlayer1":%s,"choicePlayer2":%s'.
        '}  }';
    	$sendStr = sprintf($strBase,$room->RoomNum,$questionStr,$choicePlayer1,$choicePlayer2);
    	$connection->send($sendStr);    	
    } 
    
    /**
     * send402  answer notify
     *  A玩家已经答题， 服务器转发给B玩家的数据
     *  这个命令不用了。400实现了类似的功能。
     */
    
    public function send402($otherPlayer,$roomId,$openid,$scoreMyself){
    
    
//     	$questionStr = $question->buildJsonStr();
//     	$strBase = '{"socketCmd": 402,"socketAction": "answer_notify",' . 
//   				'"data": {"roomName": "%d",' .
//     			'"choice": {"openid": "%s","scoreOtherPlayer": "%s" } },  "version": 1  }';
//     	$msg = sprintf($strBase,$roomId,$openid,$scoreMyself);
//     	$player->connection->send($str);
    }
    

    
    /**
     *  handle 500 answer 答题
     * 
     *  $connection
     *  $data
     *
     **/
     
    public function handleData500($connection,$data){
    	echo "handleData500 begin.\n";
    	if (strlen($data)<=0 ||
    			strpos($data,'socketCmd')===false ||
    			strpos($data,'socketAction')===false ||
    			strpos($data,'data')===false ){
    		echo 'handleData500 error data!';
    		$this->send209($connection);
    		return 0;  //error!
    	}    	
    	//1.解析500答题数据，
    	$arr = json_decode($data,true);
    	$roomName = $arr['data']['roomName'];
    	$openid = $arr['data']['choice']['openid'];
    	$userChoose = $arr['data']['choice']['userChoose'];
    	$right = $arr['data']['choice']['answerColor'];
    	$scoreMyself =$arr['data']['choice']['scoreMyself']; 
    	//2.给对手发送402通知+分数
    	$room = $this->findRoomById(intval($roomName));
    	
    	//房间可能已经关闭，客户端还在发消息过来。
    	if ($room==null){
    		echo "room has close.\n";
    		return ;
    	}
    	$currentPlayer = null;
    	if ($room->player1->openid==$openid){
    		$room->roundPlayer1Answer=true;
    		$currentPlayer =$room->player1;
    		//$room->player1->scoreMyself = $scoreMyself;
    	}
    	else {
    		$room->roundPlayer2Answer=true;
    		$currentPlayer =$room->player2;
    		
    	}
    	$currentPlayer->userChoose = $userChoose;
    	$currentPlayer->answerColor = $right;
    	$currentPlayer->scoreMyself = $scoreMyself;
    	
    	//$otherPlayer = getOtherPlayer($room,$openid);
    	 
    	//send402($otherPlayer,$roomName,$openid,$scoreMyself);
    	
    	
    	//3.如果双方都已经答题，则400发题；如果已经完成5轮答题，则发送600结束。
    	if ($room->roundPlayer1Answer && $room->roundPlayer2Answer){
    		if ($room->round>=4 ){
    			//finished 600!
    			$room->round = Room::CLOSE_ROUND;
    			$this->send600($room->player1,$room,self::REASON_NORMAL_600);
    			$this->send600($room->player2,$room,self::REASON_NORMAL_600);
    			//send600之后，client可能主动断掉链接，因此需要特殊处理（已经处理）
    			
    			//写入比赛结果,先查该用户的user_id
    			if ($room->player1->scoreMyself >$room->player2->scoreMyself){
    				$intWin1 = 1;
    				$intWin2 = 0;
    			}else if ($room->player1->scoreMyself ==$room->player2->scoreMyself){
    				//tony:修改定义，平局和失败都是0
    				$intWin1 = 0;
    				$intWin2 = 0;
    			}else
    			{
    				$intWin1 = 0;
    				$intWin2 = 1;
    			}
    			$this->writeGameResult($room->player1,$intWin1);
    			$this->writeGameResult($room->player2,$intWin2);
    			
    			p_writeLog($room->player1->openid,'比赛结束，比分'.
    					$room->player1->scoreMyself.':'.
    					$room->player2->scoreMyself . 
    					$room->player1->nickName . " pk ".$room->player2->nickName);
    			
    			//close room and remove connectoin.
    			$this->closeConnAndRoom($room);
    			
    		}else {
    			$room->round++;
    			$room->roundPlayer1Answer=false;
    			$room->roundPlayer2Answer=false;

    			$question = $this->pickOneQuestion(); 
    			$this->send400($room->player1->connection,$room,$question);
    			$this->send400($room->player2->connection,$room,$question); 
    		}
    		
    		
    	}
    }
    
    
    /*
     *   处理心跳消息 999
    *    
    */
    public function handleData999($connection,$data){
    	echo "handleData999 begin.\n";
    	if (strlen($data)<=0 ||
    			strpos($data,'socketCmd')===false ||
    			strpos($data,'socketAction')===false ){
    		echo 'handleData999 error data!';
    		$this->send209($connection);
    		return 0;  //error!
    	}
    	
    	$this->send999($connection);

    }
    
    
    /**
     *   find Room object from room ID.
     * 
     * @param int $roomId
     */
    public function findRoomById($roomId){
    	foreach($this->RoomList as $item){
    		if ($item->RoomNum == $roomId)
    			return $item;
    	}
    	return null;
    }
    
    /**
     *   getOtherPlayer
     *   find other player object using room object and one player openid.  
     * 
     */
    public function getOtherPlayer(Room $room,$openid){
    	if ($room->player1->openid==$openid)
    		return $room->player2;
    	else
    		return $room->player1;
    }
    /*
     *   send600
     *   usage: game over. finished. ask client to exit.
     */
    public function send600(Player $player, Room $room,$reason){
    	echo "send600 begin.\n";
    	$baseStr = '{ "code": 0, "msg": "GameOver",'. 
  		'"socketCmd": 600,"action": "GameOver",'. 
  		'"data": {  "roomName": "%d", "reason":"%s",'.
    '"player1": {"openid":"%s", "score":%d},'.
    '"player2": { "openid":"%s","score":%d}'.
   			 '}}';	
    	
    	$other = $room->getOtherPlayer($player);
    	$sendStr = sprintf($baseStr,$room->RoomNum,$reason,$player->openid,$player->scoreMyself,
    			$other->openid,$other->scoreMyself);
    	
    	$player->connection->send($sendStr);
    }
    
    /*
     * closeConnAndRoom
     * 
     * 关闭房间中用户的连接，关闭房间，从RoomList从移除房间。
     * 
     */
    public function closeConnAndRoom(Room $room){
    	echo "closeConnAndRoom\n";
    	echo "count of rooms:" . count($this->RoomList) . " \n";
    	
    	$room->closing =true;
    	$arr = array();
    	if ($room->player1)
    		$arr[] = $room->player1;
    	if ($room->player2)
    		$arr[] = $room->player2;
    	foreach($arr as $player){
    		//不再主动断掉，然旧会把变量值null
    		//$player->connection->close(); 
    		$player->connection=null;
    		unset($player);
    	}
    	$room->player1=null;
    	$room->player2=null;
    	
    	//从RoomList中删除$room
    	$find = array_search($room,$this->RoomList,true);
    	if ($find===FALSE){
    		echo 'Error:closeConnAndRoom error,room not find.<br/>';
    		return; //未找到，肯定有错
    	}else{
    		echo "count of rooms:" . count($this->RoomList) . " \n";
    		array_splice($this->RoomList, $find,1);
    		echo "count of rooms(after remove):" . count($this->RoomList) . " \n";
    		unset($room);
    	}
    	
    }
    /**
     *  服务器给客户端回复心跳消息
     */
    public function send999($connection){
    	echo "send999 begin.\n";
    	$str = '{"code": 0, "msg": "pong", "socketCmd": 999,'.
    			'"action": "pong", "data": {}  }';
    	$connection->send($str);
    }
    /*
     *  记录比赛成绩到数据库
     * 
     */
    public function writeGameResult(Player $player,$intWin){
    	echo "writeGameResult() begin. \n";
    	$user_id = $player->user_id;
    	//tony:老版本不积分
    	if ($player->scoreMyself>50){
    		p_writeLog($player->openid,$player->nickName .'分数太高，应该是老版本不计分' );
    		$player->scoreMyself =0;
    	}

    	$query = Db::query('select count(result_id) total_count,sum(num) sum_num from t_result where user_id='.$user_id);
    	if (count($query)>0){
    		$total_count=$query[0]['total_count'];
    		$sum_num=$query[0]['sum_num'];
    	}
    	$query=null;
    	//一个用户多有条记录，必然是老数据；如果一个用户只有一条记录，num是0，也必然是老数据
    	if ($total_count>1 || ($total_count==1 &&  $sum_num==0) ){
    		//说明有老格式的数据，先求和，再删除，再新增
    		//求和
    		$query2 = Db::query('select sum(score) total_score,sum(win) total_win,count(win) total_count from t_result where user_id='.$user_id);
    		if (count($query2)>0){
    			$total_score =$query2[0]['total_score'];
    			$total_win=$query2[0]['total_win'];
    			$total_count=$query2[0]['total_count'];
    			if ($total_score==null)  $total_score=0;
    			if ($total_win==null)  $total_win=0;
    		}else{
    			$total_score =0;
    			$total_win=0;
    			$total_count=0;
    		}    	
    		//删除所有相关记录
    		Db::execute('delete from t_result where user_id='.$user_id);	
    		//新建记录
    		$ret = new GameResult();
    		$ret->user_id = $user_id;
    		$ret->score=$total_score+$player->scoreMyself;
    		$ret->win=$total_win+$intWin;
    		$ret->num = $total_count+1;
    		$ret->result_date = date('Y-m-d',time());
    		if ($ret->save())
    			return 0;
    		else{
    			p_writeLog($player->openid, 'write game result fail:'.$user_id);
    			return -1;
    		}    		
    		
    	}else if ($total_count==1){
    		//这种情况update现有记录就可以了,一定是最新记录
    		$query3= Db::name('t_result')->where('user_id',$user_id)->find();
    		if ($query3){
    			$data = ['score' =>$query3['score']+$player->scoreMyself,  
    					'win' => $query3['win']+$intWin,
    					'num' => $query3['num']+1,
    					'result_date' => date('Y-m-d',time())];
    			Db::name('t_result')->where('user_id',$user_id)->update($data);
    		}

    	}else{
    		//没有数据，直接存入数据库
    		$ret = new GameResult();
    		$ret->user_id = $user_id;
    		$ret->score=$player->scoreMyself;
    		$ret->win=$intWin;
    		$ret->num=1;
    		$ret->result_date = date('Y-m-d',time());
    		if ($ret->save())
    			return 0;
    		else{
    			p_writeLog($player->openid, 'write game result fail:'.$user_id);
    			return -1;
    		}   		
    	}
    }
     
	public function getGameStatus(){
		//echo "getGameStatus:";
		$setting = SettingModel::getByType_id(2);
		if ($setting){
			$status = $setting->switch;
			//echo $status ." ";//不要换行，很占显示
			if ($status){
				return true;//running
			}
			else 
				return false;//pause
		}
		else{
			echo "ERROR! CAN NOT GET getGameStatus \n";
			return true;
		}
	}
    
}
