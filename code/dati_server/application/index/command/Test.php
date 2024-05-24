<?php
namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\wxapi\model\GroupModel;
use app\wxapi\model\User as UserModel;
use app\wxapi\model\GameResult;
/*
 *  这是一个测试的命令行命令。用来添加大量数据，测试系统的性能。
 *  
 * 
 */
class Test extends Command
{
	protected function configure()
	{
		$this->setName('test')->setDescription('Create lot\'s of data.(Tony) ');
	}

	protected function execute(Input $input, Output $output)
	{
		$output->writeln("try to add lot's of data... please wait:");
		
		$round = 100;//group数量100
		$begin = 40;
		
		//每个group生成1000用户，每个用户生成2条记录
		$roundUser=  1000;//1000
		$roundGame = 2;
		
		$countUser =0;
		$countGameResult = 0;
		
		for($i=1;$i<=$round;$i++){
			$obj = new GroupModel();
			$obj->group_name = 'group'.$begin;
			if ($obj->save()){
				$group_id = $obj->id;
				$output->writeln("group_id is " . $group_id);
				$begin = $group_id+1;//下个循环的id+1
				
				for($j=1;$j<=$roundUser;$j++){
					$user = new UserModel();
					$user->openid="USER".$group_id .'U'.$j;
					$user->nick_name = $user->openid;
					$user->avatar_url ='';
					$user->group_id = $group_id;
					$user->registe_date = date('Y-m-d',time());

					if ($user->save()){
						$countUser++;
						$user_id = $user->id;
						for($k=1;$k<=$roundGame;$k++){
							$game = new GameResult();
							$game->user_id = $user_id;
							$game->score = 100;
							$game->win= rand(0,2);
							$game->result_date =date('Y-m-d',time());
							if (!$game->save()){
								$output->writeln("add game reuslt fail");
								break;								
							}else
								$countGameResult++;
						}						
					}else{
						$output->writeln("add user fail");
						break;						
					}

				}
				
			}
			else{
				$output->writeln("add group fail");
				break;
			} 
				
		}
		
		//finished! print result.
		$output->writeln("finished!");
		$output->writeln("create " . $countUser . " users.");
		$output->writeln("create " . $countGameResult . " gameResults.");
		
	}
}