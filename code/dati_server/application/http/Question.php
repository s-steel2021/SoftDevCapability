<?php
namespace  app\http;

class Question{
	public $ask;
	public $answer1;
	public $answer2;
	public $answer3;
	public $answer4;
	public $right;
		
	public function buildJsonStr(){
	   $baseStr = '{ "ask": "%s","answer": ['.
        '{ "answer": "%s", "right": %s },'.
        '{ "answer": "%s", "right": %s },'.
        '{ "answer": "%s", "right": %s },'.
        '{ "answer": "%s", "right": %s }]}';
	   $arr = array();  
	   $s = '';
	   for($i=0;$i<4;$i++){
	   	  if ($this->right == $i)
	   	  	$s = 'true';
	   	  else
	   	  	$s = 'false';
	   	  $arr[]=$s;
	   }
	   $str = sprintf($baseStr,$this->ask,$this->answer1,$arr[0],$this->answer2,$arr[1],$this->answer3,$arr[2],$this->answer4,$arr[3]);
	   return $str;
	}
}