<?php

class SMA{

	static function lag($period = 5){
		return $period - 1;
	}

	 static function run($data, $period = 5){
            
		//loop data
		foreach($data as $key => $row){
			
			//Add logic here
			if ($key >= $period){
				$sum = 0;
				for ($i = $key - ($period-1); $i <= $key; $i ++)
					$sum += $data[$i]['close'];
			
				$sma = $sum / $period;
			
				//add sma field and value
				$data[$key]['val'] = $sma;
			}
		}
		return $data;
    }
}

?>