<?php

class TechnicalAnalysisController extends DooController {

	function ta() {
		
		//callback : required
		if (!isset( $_GET['callback'])  || !preg_match('/^[a-zA-Z0-9_]+$/',  $_GET['callback'])) {
			die('Invalid callback name');
		}
		$callback = $_GET['callback'];

		//pair : required
		if (!isset($_GET['pair']) || !preg_match('/^[A-Z]{3}\/[A-Z]{3}$/', $_GET['pair'])) {
			die("Invalid pair parameter: " . $_GET['pair']);
		}
		$pair = $_GET['pair'];

		//start : optional (default : now - 1 day)
		if (isset($_GET['start']) && !preg_match('/^[0-9]+$/', $_GET['start'])) {
			die("Invalid start parameter: " . $_GET['start']);
		}
		$start = isset($_GET['start']) ? $_GET['start'] : (time() - 86400) * 1000;

		//end : optional (default : now())
		if (isset($_GET['end']) && !preg_match('/^[0-9]+$/', $_GET['end'])) {
			die("Invalid end parameter: " . $_GET['end']);
		}
		$end = isset($_GET['end']) ? $_GET['end'] :  time() * 1000;

		//bid_offer : optional (default : 'bid')
		if (isset($_GET['bid_offer']) && !preg_match('/^(bid|offer)$/', $_GET['bid_offer'])) {
			die("Invalid bid_offer parameter: " . $_GET['bid_offer']);
		}
		$bid_offer = isset($_GET['bid_offer']) ? $_GET['bid_offer'] : 'bid';

		//function : required
		$function = $_GET['function'];
		if (!isset($_GET['function']) || !class_exists($function)) {
		   die("Invalid function parameter: " . $_GET['function']);
		}
		$function = $_GET['function'];

		//function params arr : optional
		$function_param_arr = isset($_GET['function_param_arr']) ? json_decode($_GET['function_param_arr'], true) : array();

		//force timeslice [s,m,h,d,w,M]
		$timeslice = isset($_GET['timeslice']) ? $_GET['timeslice'] : null;

		//get extra rows needed to calculate fuction
		$lag = call_user_func_array(array($function, "lag"), $function_param_arr);
		if(!$lag)
			$lag = 0;

		//get data
		$my_ohlc = new OHLC();
		$results = $my_ohlc->get_ohlc($pair, $start, $end, $bid_offer, $timeslice, $lag);

		//build data 
		$data = array();
		foreach($results as $row){

			//parse out fields
			$arr = explode(',', $row);
			$ts = substr($arr[0], 1);

			$data[] = array('open' => $arr[1], 'high' => $arr[2], 'low' => $arr[3] , 'close' => $arr[4], 'datetime' => $ts);
		}
		
		//push data as first param to generic run function
		array_unshift($function_param_arr, $data);


//var_dump($data); exit;

		//pass to function
		$ta_results = call_user_func_array(array($function, "run"), $function_param_arr);

//var_dump($ta_results);

		$results = array();
		foreach($ta_results as $row){
			extract($row);

			if(isset($val2) && isset($val))
				$results[] = "[$datetime,$val,$val2]";

			else if(isset($val))
				$results[] = "[$datetime,$val]";

			
		}

		//debug
		$startTime = gmstrftime('%Y-%m-%d %H:%M:%S', $start / 1000);
		$endTime = gmstrftime('%Y-%m-%d %H:%M:%S', $end / 1000);

		// print it
		header('Content-Type: text/javascript');
		echo "/* console.log(' start = $start, end = $end, startTime = $startTime, endTime = $endTime '); */";
		echo $callback ."([\n" . join(",\n", $results )."\n]);";
	}
}
?>