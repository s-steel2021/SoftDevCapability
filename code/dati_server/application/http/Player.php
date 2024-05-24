<?php
/***
 *
*    Authoer:Tony Peng
*    Create: 2019-4-1
*    usage:  玩家
*/
namespace app\http;

class Player{
	//连接相关数据
	public $connection;
	public $uid;
	public $user_id;  //db中的id
	
	//用户关系数据
	public $openid;
	public $avatarUrl;
	public $nickName;
	
	//用户最后一个选择
	public $userChoose=0;    //int  0123
	public $answerColor='';  //"right"
	public $scoreMyself=0;  //int 自己打分
	
	//创建json数据，用于send400
    //[openid, 用户选择了第几个答案, 用户是否答对, 用户这局总得分]  
    //  'choicePlayer1': ["1234", 1, "right", 0],//对手1
	public function buildJsonAnswerStr(){
		$str = sprintf('["%s",%d,"%s",%d]',$this->openid,$this->userChoose,
				$this->answerColor,$this->scoreMyself);
		return $str;
	}
	
	
}