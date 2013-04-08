<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:stochrsi
*/
class STOCHRSI{

	static function lag($period = 14){
		return $period - 1;
	}

	static function run($data, $period = 14){

		$change_array = array();
		$rsi_array = array();

		//loop data
		foreach($data as $key => $row){

			//need 2 points to get change
			if($key >= 1){

				$change = $data[$key]['close'] - $data[$key - 1]['close'];

				//add to front
				array_unshift($change_array, $change);

				//pop back if too long
				if(count($change_array) > $period)
					array_pop($change_array);
			}

			//have enough data to calc rsi
			if($key > $period){
				//reduce change array getting sum loss and sum gains
				$res = array_reduce($change_array, function($result, $item) { 

							if($item >= 0)
								$result['sum_gain'] += $item;
			
							if($item < 0)
								$result['sum_loss'] += abs($item);

					  		return $result; 
						}, array('sum_gain' => 0, 'sum_loss' => 0)); 


				$avg_gain = $res['sum_gain'] / $period;
				$avg_loss = $res['sum_loss'] / $period;

				//check divide by zero
				if($avg_loss == 0){
					$rsi = 100;
				} else {
					//calc and normalize
					$rs = $avg_gain / $avg_loss;				
					$rsi = 100 - (100 / ( 1 + $rs));
				}

				//add to front
				array_unshift($rsi_array, $rsi);

				//pop back if too long
				if(count($rsi_array) > $rsi)
					array_pop($rsi_array);
			}

			//have enough data to calc stochrsi
			if($key >= $period * 2){

				//max of highs
				$init = $rsi_array[0];
				$h = array_reduce($rsi_array, function($v, $w) {
					    $v = max($w, $v);
					    return $v;
					}, $init);

				//low of lows
				$init = $rsi_array[0];
				$l = array_reduce($rsi_array, function($v, $w) {
					    $v = min($w, $v);
					    return $v;
					}, $init);

				//calc stoch rsi
				$stochrsi = ($rsi_array[0] - $l) / ($h - $l);

				//save
				$data[$key]['val'] = $stochrsi;
				
			}
		}
		return $data;
	}
}