<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:williams_r
*/
class PERC_R{

	static function lag($period = 14){
		return $period - 1;
	}
	
	static function run($data, $period = 14){

		$high_array = array();
		$low_array = array();

		//loop data
		foreach($data as $key => $row){

			//add to front
			array_unshift($high_array, $data[$key]['high']);

			//pop back if too long
			if(count($high_array) > $period)
				array_pop($high_array);

			//add to front
			array_unshift($low_array, $data[$key]['low']);

			//pop back if too long
			if(count($low_array) > $period)
				array_pop($low_array);

			//have enough data to calc perc r
			if($key >= $period){
				//max of highs
				$init = $high_array[0];
				$h = array_reduce($high_array, function($v, $w) {
					    $v = max($w, $v);
					    return $v;
					}, $init);

				//low of lows
				$init = $low_array[0];
				$l = array_reduce($low_array, function($v, $w) {
					    $v = min($w, $v);
					    return $v;
					}, $init);

				//calc percent R
				$perc_r  = ($h - $data[$key]['close']) / ( $h - $l)  * -100;
				
				//save
				$data[$key]['val'] = $perc_r;
			}

		}
		return $data;
	}
}