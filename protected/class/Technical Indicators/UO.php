<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:ultimate_oscillator
*/
class UO{

	static function lag($period1 = 7, $period2 = 14, $period3 = 28){
		return max($period1, $period2, $period3);
	}

	static function run($data, $period1 = 7, $period2 = 14, $period3 = 28){

		$buying_pressure_1_array = array();
		$buying_pressure_2_array = array();
		$buying_pressure_3_array = array();

		$true_range_1_array = array();
		$true_range_2_array = array();
		$true_range_3_array = array();

		//loop data
		foreach($data as $key => $row){

			if($key > 0){

				//calc buying pressure
				$buying_pressure = $data[$key]['close'] - min($data[$key]['low'], $data[$key - 1]['close']);		

				//add to front
				array_unshift($buying_pressure_1_array, $buying_pressure);
				array_unshift($buying_pressure_2_array, $buying_pressure);
				array_unshift($buying_pressure_3_array, $buying_pressure);

				//pop back if too long
				if(count($buying_pressure_1_array) > $period1)
					array_pop($buying_pressure_1_array);

				if(count($buying_pressure_2_array) > $period2)
					array_pop($buying_pressure_2_array);

				if(count($buying_pressure_3_array) > $period3)
					array_pop($buying_pressure_3_array);

				//calc true range
				$tr = max($data[$key]['high'], $data[$key - 1]['close']) - min($data[$key]['low'], $data[$key - 1]['close']);

				//add to front
				array_unshift($true_range_1_array, $tr);
				array_unshift($true_range_2_array, $tr);
				array_unshift($true_range_3_array, $tr);

				//pop back if too long
				if(count($true_range_1_array) > $period1)
					array_pop($true_range_1_array);

				if(count($true_range_2_array) > $period2)
					array_pop($true_range_2_array);

				if(count($true_range_3_array) > $period3)
					array_pop($true_range_3_array);

			}

			//calc Ultimate
			if(count($true_range_3_array) == $period3 && count($buying_pressure_3_array) == $period3){


				$sum_true_range_1 = array_reduce($true_range_1_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$sum_buying_pressure_1 = array_reduce($buying_pressure_1_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				//calc avg of period1
				$avg_1 = $sum_buying_pressure_1 / $sum_true_range_1;

				$sum_true_range_2 = array_reduce($true_range_2_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$sum_buying_pressure_2 = array_reduce($buying_pressure_2_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				//calc average period2
				$avg_2 = $sum_buying_pressure_2 / $sum_true_range_2;

				$sum_true_range_3 = array_reduce($true_range_3_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$sum_buying_pressure_3 = array_reduce($buying_pressure_3_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				//calc avg_28
				$avg_3 = $sum_buying_pressure_3 / $sum_true_range_3;

				//calc Ulitimate Oscillator
				$uo = 100 * (( 4 * $avg_1) + ( 2 * $avg_2) + $avg_3) / (4 + 2 + 1);

				//save
				$data[$key]['val'] = $uo;
			}
		}
		return $data;
	}
}