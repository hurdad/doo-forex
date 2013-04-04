<?php
/* 
* Reference: http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:average_true_range_a
*/
class ATR{

	static function run($data, $period = 14){
		
		//init
		$High_minus_Low  = null;
		$High_minus_Close_past = null;
		$Low_minus_Close_past = null;
		$TR = null;
		$TR_sum = 0;

		//loop data
		foreach($data as $key => $row){

			$High_minus_Low = $data[$key]['high'] - $data[$key]['low'];

			if($key >= 1){
				$High_minus_Close_past = abs($data[$key]['high'] - $data[$key - 1]['close']);
				$Low_minus_Close_past = abs($data[$key]['low'] - $data[$key - 1]['close']);
			}

		
			if(isset($High_minus_Close_past) && isset($Low_minus_Close_past)){

				$TR = max($High_minus_Low, $High_minus_Close_past, $Low_minus_Close_past);

				//sum first TRs for first ATR avg
				if ($key <= $period)
					$TR_sum += $TR;
			}

			//first ATR
			if ($key == $period){
				$atr = $TR_sum / $period;
				$data[$key]['val'] = $atr;
				$previous_ATR = $atr;
			}

			//remaining ATR
			if($key > $period){
				$atr = (($previous_ATR * ($period - 1)) + $TR) / $period;
				$data[$key]['val'] = $atr;
				$previous_ATR = $atr;
			}
		}
		return $data;
	}
}