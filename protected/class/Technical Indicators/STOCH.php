<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:stochastic_oscillato
*/
class STOCH{


	static function lag($period = 14, $sma_period = 3){
		return $period - 1 + $sma_period - 1;
	}

	static function run($data, $period = 14, $sma_period = 3){

		$high_array = array();
		$low_array = array();
		$k_array = array();

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

			//have enough data to calc stoch
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

				//calc
				$k  = ($data[$key]['close'] - $l) / ( $h - $l) * 100;

				//add to front
				array_unshift($k_array, $k);

				//pop back if too long
				if(count($k_array) > $sma_period)
					array_pop($k_array);
				
				//save
				$data[$key]['val'] = $k;
			}

			//have enough data to calc sma
			if(count($k_array) == $sma_period){
				
				//k moving average 
				$sum = array_reduce($k_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$sma = $sum / $sma_period;

				//save
				$data[$key]['val2'] = $sma;
			}
		}
		return $data;
	}
}