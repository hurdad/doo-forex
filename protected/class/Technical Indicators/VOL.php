<?php
/* 
* Pass through Volume
*/
class VOL{

	static function lag(){
		return 0;
	}

	static function run($data){

		//loop data
		foreach($data as $key => $row){
			//pass through
			$data[$key]['val'] = $data[$key]['vol'];
		}
		return $data;
	}
}

	