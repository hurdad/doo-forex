<?php
/* 
* Reference: SMA Highs /(SMA Lows - 1)
*/
class Highs_Lows{

	static function lag($period = 14){
		return $period - 1;
	}

	static function run($data, $period = 14){

		$highs_array = array();
		$lows_array = array();
		
		//loop data
		foreach($data as $key => $row){
		
			$low = $data[$key]['low'];
			$high = $data[$key]['high'];

			//add to front
			array_unshift($highs_array, $high);
			array_unshift($lows_array, $low);

			//pop back if too long
			if(count($highs_array) > $period)
				array_pop($highs_array);
			if(count($lows_array) > $period)
				array_pop($lows_array);
			
			//enough data to calc smas
			if(count($lows_array) == $period && count($highs_array) == $period) {

				$lows_sum = array_reduce($lows_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$lows_sma = $lows_sum / $period;

				$highs_sum = array_reduce($highs_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$highs_sma = $highs_sum / $period;

				//calc highs/lows
				$high_low = $highs_sma / $lows_sma - 1;

				//save
				$data[$key]['val'] = $high_low;
			}
		}
		return $data;
	}
}