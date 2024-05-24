<?php
namespace app\http;
/***
 *
*    Authoer:Tony Peng
*    Create: 2019-4-1
*    usage:  比赛房间
*/
class Room{
	public $player1;
	public $player2;
	public static $RoomNumBase =100;  //init room num 100
	public $RoomNum;  //房号,唯一标识
	
	const CLOSE_ROUND=5;
	public $round=0; //回合， 0~4
	
	//某轮已经答题的标志，每轮开始需要设置为false,是重新发题的标志
	public $roundPlayer1Answer=false;
	public $roundPlayer2Answer=false;
	
// 	public $player1Score;
// 	public $player2Score;
	
	//当前问题
	public $question=null;
	
	//正在关闭中.这种状态，不再检查内部connection等。
	public $closing = false;
	
	public function __construct(){
		$this->RoomNum = self::$RoomNumBase;
		self::$RoomNumBase++;
		//self::RoomNumBase = self::RoomNumBase+1;
	}
	
	public function addPlayer($a,$b){
		$this->player1 = $a;
		$this->player2 = $b;
	}
	public function getOtherPlayer(Player $player){
		if ($this->player1->openid==$player->openid)
			return $this->player2;
		else
			return $this->player1;
	}
	
	
	
}