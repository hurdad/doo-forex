<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:relative_strength_in
*/
class RSI{

	static function lag($period = 14){
		return $period;
	}
	
	static function run($data, $period = 14){

		$change_array = array();

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

				//save
				$data[$key]['val'] = $rsi;
			
			}
		}
		return $data;
	}
}