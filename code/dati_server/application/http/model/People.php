<?php
namespace app\http\model;

class People{
	public $name="test name";
	public $age=99;
	
	public function getname(){
		return $this->name;
	}
	public function getall(){
		return $this->name . ' ' . $this->age; 
	}
	
	
}