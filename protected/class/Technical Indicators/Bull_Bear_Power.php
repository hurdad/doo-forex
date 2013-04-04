<?php
/* 
* Reference: http://ta.mql4.com/indicators/oscillators/elder_rays
*/
class Bull_Bear_Power{

	static function run($data, $period = 13){
	
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

				//calc bull bear power
				$bull_power = $data[$key]['high'] - $previous_EMA;
				$bear_power = $data[$key]['low'] - $previous_EMA;
				$diff = $bull_power - $bear_power;
		
				//save
				$data[$key]['val'] = $diff;
			}
		}
		return $data;
	}
}