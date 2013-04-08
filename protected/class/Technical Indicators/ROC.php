<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:rate_of_change_roc_a
*/
class ROC{

	static function lag($period = 14){
		return $period;
	}
	
	static function run($data, $period = 12){

		$close_array = array();

		//loop data
		foreach($data as $key => $row){
	
			//first ROC
			if ($key >= $period){

				//calc
				$roc = (($data[$key]['close'] - $close_array[$period - 1]) /  $close_array[$period - 1]) * 100;
				
				//save
				$data[$key]['val'] = $roc;
			}

			//add to front
			array_unshift($close_array, $data[$key]['close']);

			//pop back if too long
			if(count($close_array) > $period)
				array_pop($close_array);

		}
		return $data;
    }
} 