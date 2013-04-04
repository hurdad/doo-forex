<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:relative_strength_in
*/
class UO{

	static function run($data, $period = 14){

		$buying_pressure_7_array = array();
		$buying_pressure_14_array = array();
		$buying_pressure_28_array = array();

		$true_range_7_array = array();
		$true_range_14_array = array();
		$true_range_28_array = array();

		//loop data
		foreach($data as $key => $row){

			if($key > 0){

				//calc buying pressure
				$buying_pressure = $data[$key]['close'] - min($data[$key]['low'], $data[$key - 1]['close']);		

				//add to front
				array_unshift($buying_pressure_7_array, $buying_pressure);
				array_unshift($buying_pressure_14_array, $buying_pressure);
				array_unshift($buying_pressure_28_array, $buying_pressure);

				//pop back if too long
				if(count($buying_pressure_7_array) > 7)
					array_pop($buying_pressure_7_array);

				if(count($buying_pressure_14_array) > 14)
					array_pop($buying_pressure_14_array);

				if(count($buying_pressure_28_array) > 28)
					array_pop($buying_pressure_28_array);

				//calc true range
				$tr = max($data[$key]['high'], $data[$key - 1]['close']) - min($data[$key]['low'], $data[$key - 1]['close']);

				//add to front
				array_unshift($true_range_7_array, $tr);
				array_unshift($true_range_14_array, $tr);
				array_unshift($true_range_28_array, $tr);

				//pop back if too long
				if(count($true_range_7_array) > 7)
					array_pop($true_range_7_array);

				if(count($true_range_14_array) > 14)
					array_pop($true_range_14_array);

				if(count($true_range_28_array) > 28)
					array_pop($true_range_28_array);

			}

			//calc Ultimate
			if(count($true_range_28_array) == 28 && count($buying_pressure_28_array) == 28){


				$sum_true_range_7 = array_reduce($true_range_7_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$sum_buying_pressure_7 = array_reduce($buying_pressure_7_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				//calc avg_7
				$avg_7 = $sum_buying_pressure_7 / $sum_true_range_7;

				$sum_true_range_14 = array_reduce($true_range_14_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$sum_buying_pressure_14 = array_reduce($buying_pressure_14_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				//calc avg_14
				$avg_14 = $sum_buying_pressure_14 / $sum_true_range_14;

				$sum_true_range_28 = array_reduce($true_range_28_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$sum_buying_pressure_28 = array_reduce($buying_pressure_28_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				//calc avg_28
				$avg_28 = $sum_buying_pressure_28 / $sum_true_range_28;

				//calc Ulitimate Oscillator
				$uo = 100 * (( 4 * $avg_7) + ( 2 * $avg_14) + $avg_28) / (4 + 2 + 1);

				//save
				$data[$key]['val'] = $uo;
			}
		}
		return $data;
	}
}