<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:average_directional_
*/
class ADX{

	static function run($data, $period = 14){

		$true_range_array = array();
		$plus_dm_array = array();
		$minus_dm_array = array();
		$dx_array = array();
		$previous_adx = null;
	
		//loop data
		foreach($data as $key => $row){

			//need 2 data points
			if($key > 0){

				//calc true range
				$true_range = max(($data[$key]['high'] - $data[$key]['low']), abs($data[$key]['high'] - $data[$key - 1]['close']) , abs($data[$key]['low'] - $data[$key - 1]['close']));

				//calc +DM 1
				$plus_dm_1 = (($data[$key]['high'] - $data[$key - 1]['high']) > ($data[$key - 1]['low'] - $data[$key]['low'])) ? max($data[$key]['high'] - $data[$key-1]['high'], 0) : 0;

				//calc -DM 1
				$minus_dm_1 = (($data[$key - 1]['low'] - $data[$key]['low']) > ($data[$key]['high'] - $data[$key-1]['high'])) ? max($data[$key - 1]['low'] - $data[$key]['low'], 0) : 0;

				//add to front
				array_unshift($true_range_array, $true_range);
				array_unshift($plus_dm_array, $plus_dm_1);
				array_unshift($minus_dm_array, $minus_dm_1);

				//pop back if too long
				if(count($true_range_array) > $period)
					array_pop($true_range_array);

				if(count($plus_dm_array) > $period)
					array_pop($plus_dm_array);

				if(count($minus_dm_array) > $period)
					array_pop($minus_dm_array);
			}


			//calc dx
			if(count($true_range_array) == $period){

				$sum_true_range = array_reduce($true_range_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$sum_plus_dm = array_reduce($plus_dm_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$sum_minus_dm = array_reduce($minus_dm_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);


				$plus_di = ($sum_plus_dm / $sum_true_range) * 100;
				$minus_di = ($sum_minus_dm / $sum_true_range) * 100;

				$di_diff = abs($plus_di - $minus_di);
				$di_sum = $plus_di + $minus_di;
				$dx = ($di_diff / $di_sum) * 100;

				//add to front
				array_unshift($dx_array, $dx);
				//pop back if too long
				if(count($dx_array) > $period)
					array_pop($dx_array);

			}

			//calc first adx
			if(count($dx_array) == $period){

				$sum = array_reduce($dx_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$adx = $sum / $period;

				//save
				$data[$key]['val'] = $adx;
				$previous_adx = $adx;
			}

			//calc further adx
			if(isset($previous_adx)){
				$adx = (($previous_adx * ($period - 1)) + $dx) / $period;

				//save
				$data[$key]['val'] = $adx;
				$previous_adx = $adx;
			}

		}
		return $data;
	}
}