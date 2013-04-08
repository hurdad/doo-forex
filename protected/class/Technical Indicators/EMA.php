<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:moving_averages
*/
class EMA{

	static function lag($period = 5){
		return $period - 1;
	}

	static function run($data, $period = 5){

	 	$smoothing_constant = 2 / ($period + 1);
	 	$previous_EMA = null;
            
		//loop data
		foreach($data as $key => $row){
			
			//skip init rows
			if ($key >= $period){

				//first 
				if(!isset($previous_EMA)){
					$sum = 0;
					for ($i = $key - ($period-1); $i <= $key; $i ++)
						$sum += $data[$i]['close'];
					//calc sma
					$sma = $sum / $period;

					//save
					$data[$key]['val'] = $sma;
					$previous_EMA = $sma;
				}else{
					//ema formula
 					$ema = ($data[$key]['close'] - $previous_EMA) * $smoothing_constant + $previous_EMA;

 					//save
			 		$data[$key]['val'] = $ema;
			 		$previous_EMA = $ema;
				}
			}
		}
		return $data;
    }
}

?>