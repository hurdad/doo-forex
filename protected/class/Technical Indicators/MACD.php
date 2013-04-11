<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:moving_average_conve
*/
class MACD{

	static function lag($ema1 = 12, $ema2 = 26, $signal = 9){
		return (max($ema1, $ema2) + $signal) - 1;
	}

	static function run($data, $ema1 = 12, $ema2 = 26, $signal = 9){

	 	$smoothing_constant_1 = 2 / ($ema1 + 1);
	 	$smoothing_constant_2 = 2 / ($ema2 + 1);
	 	$previous_EMA1 = null;
	 	$previous_EMA2 = null;

	 	$ema1_value = null;
	 	$ema2_value = null;

	 	$macd_array = array();
            
		//loop data
		foreach($data as $key => $row){
			
			//ema 1
			if ($key >= $ema1){

				//first 
				if(!isset($previous_EMA1)){
					$sum = 0;
					for ($i = $key - ($ema1-1); $i <= $key; $i ++)
						$sum += $data[$i]['close'];
					//calc sma
					$sma = $sum / $ema1;

					//save
					$previous_EMA1 = $sma;
					$ema1_value = $sma;
				}else{
					//ema formula
 					$ema = ($data[$key]['close'] - $previous_EMA1) * $smoothing_constant_1 + $previous_EMA1;

 					//save
			 		$previous_EMA1 = $ema;
			 		$ema1_value = $ema;
				}
			}

			//ema 2
			if ($key >= $ema2){

				//first 
				if(!isset($previous_EMA2)){
					$sum = 0;
					for ($i = $key - ($ema2-1); $i <= $key; $i ++)
						$sum += $data[$i]['close'];
					//calc sma
					$sma = $sum / $ema2;

					//save
					$previous_EMA2 = $sma;
					$ema2_value = $sma;
				}else{
					//ema formula
 					$ema = ($data[$key]['close'] - $previous_EMA2) * $smoothing_constant_2 + $previous_EMA2;

 					//save
			 		$previous_EMA2 = $ema;
			 		$ema2_value = $ema;
				}
			}

			//check if we have 2 values to calc MACD Line
			if(isset($ema1_value) && isset($ema2_value)){

				$macd_line = $ema1_value - $ema2_value;
		
				//add to front
				array_unshift($macd_array, $macd_line);

				//pop back if too long
				if(count($macd_array) > $signal)
					array_pop($macd_array);

				//save
				$data[$key]['val'] = $macd_line;
			}

			//have enough data to calc signal sma
			if(count($macd_array) == $signal){
				
				//k moving average 
				$sum = array_reduce($macd_array, function($result, $item) { 
					    $result += $item;
					    return $result;
					}, 0);

				$sma = $sum / $signal;
				
				//save
				$data[$key]['val2'] = $sma;
			}
		}
		return $data;
	}
}