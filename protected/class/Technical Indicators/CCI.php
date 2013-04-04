<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:commodity_channel_in
*/
class CCI{

	static function run($data, $period = 14){

		$tp_array = array();
		
		//loop data
		foreach($data as $key => $row){

			//typical price calc
			$TP = ($row['high'] + $row['low'] + $row['close']) / 3;

			//add to front
			array_unshift($tp_array, $TP);

			//pop back if too long
			if(count($tp_array) > $period)
				array_pop($tp_array);

			//have enough data to calc cci
			if($key >= $period){

				//TP moving average 
				$sum = array_reduce($tp_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$tp_sma = $sum / $period;

				//calc mean dev
				$res = array_reduce($tp_array, function($result, $item) { 
						$result['sum_abs'] += abs($result['tp_sma'] - $item);
						return $result; 
					}, array('sum_abs' => 0, 'tp_sma' => $tp_sma)); 

				$mean_dev = $res['sum_abs'] / $period;

				//calc cci
				$cci = ($TP - $tp_sma) / (0.015 * $mean_dev);

				//save
				$data[$key]['val'] = $cci;
				 
			}
		}
		return $data;
	}
}