<?php

class OHLCController extends DooController {

	function ohlc() {
				
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

		//force timeslice [s,m,h,d,w,M]
		$timeslice = isset($_GET['timeslice']) ? $_GET['timeslice'] : null;

		//debug
		$startTime = gmstrftime('%Y-%m-%d %H:%M:%S', $start / 1000);
		$endTime = gmstrftime('%Y-%m-%d %H:%M:%S', $end / 1000);

		$my_ohlc = new OHLC();
		$results = $my_ohlc->get_ohlc($pair, $start, $end, $bid_offer, $timeslice);

		// print it
		$this->setContentType('js');
		echo "/* console.log(' start = $start, end = $end, startTime = $startTime, endTime = $endTime '); */";
		echo $callback ."([\n" . join(",\n", $results)."\n]);";
	}
}
?>