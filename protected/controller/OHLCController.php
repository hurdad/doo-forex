<?php

class OHLCController extends DooController {

	function ohlc() {
		
		//callback : required
		$callback = $_GET['callback'];
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $callback)) {
			die('Invalid callback name');
		}

		//pair : required
		$pair = $_GET['pair'];
		if (!isset($pair) || !preg_match('/^[A-Z]{3}\/[A-Z]{3}$/', $pair)) {
			die("Invalid pair parameter: $pair");
		}

		//start : optional (default : now - 1 day)
		$start = $_GET['start'];
		if ($start && !preg_match('/^[0-9]+$/', $start)) {
			die("Invalid start parameter: $start");
		}
		if (!$start) $start = (time() - 86400) * 1000;

		//end : optional (default : now())
		$end = $_GET['end'];
		if ($end && !preg_match('/^[0-9]+$/', $end)) {
			die("Invalid end parameter: $end");
		}
		if (!$end) $end = time() * 1000;
	
		//bid_offer : optional (default : 'bid')
		$bid_offer = $_GET['bid_offer'];
		if ($bid_offer && !preg_match('/^(bid|offer)$/', $bid_offer)) {
			die("Invalid bid_offer parameter: $bid_offer");
		}
		if (!$bid_offer) $bid_offer = 'bid';

		//force timeslice [s,m,h,d,w,M]
		$timeslice = $_GET['timeslice'];

		// set some utility variables
		$range = $end - $start;
		$startTime = gmstrftime('%Y-%m-%d %H:%M:%S', $start / 1000);
		$endTime = gmstrftime('%Y-%m-%d %H:%M:%S', $end / 1000);

		//set suggested timeslice depending on range
		if(!isset($timeslice)){

			// find the right table
			// two days range loads minute data
			if ($range < 2 * 24 * 3600 * 1000) {
				$timeslice = '1m';

			// one month range loads hourly data
			} elseif ($range < 31 * 24 * 3600 * 1000) {
				$timeslice = '1h';

			// one year range loads daily data
			} elseif ($range < 15 * 31 * 24 * 3600 * 1000) {
				$timeslice = '1d';

			// greater range loads monthly data
			} else {
				$timeslice = '1M';
			} 
		}

		//validate and parse timeslice
		if(!preg_match('/^(?P<dur>\d+)(?P<len>s|m|h|d|w|M)$/', $timeslice, $matches)){
			die("Invalid timeslice parameter: $timeslice");
		}

		//timeslice duration (0,1,2,3,4,..)
		$ts_duration = $matches['dur'];
		//timeslice length (s,m,h,d,w,M)
		$ts_len = $matches['len'];

		//build sql
		$sql = "";
		//if daily or above, use aggregated daily table otherwize use raw quotes
		if(in_array($ts_len, array('d', 'w', 'M'))){
	
			$sql = "SELECT "; 

    			if($ts_len == 'd')
    				$sql .= "ts, UNIX_TIMESTAMP(ts) * 1000 as datetime,";
 				else if($ts_len == 'w')
					$sql .= "ADDDATE(ts, INTERVAL 1-DAYOFWEEK(ts) DAY) timeslice, UNIX_TIMESTAMP(ADDDATE(ts, INTERVAL 1-DAYOFWEEK(ts) DAY)) * 1000 as datetime,";
 					else if($ts_len == 'm')
						$sql .= "ADDDATE(ts, INTERVAL 1-DAYOFMONTH(ts) DAY) timeslice, UNIX_TIMESTAMP(ADDDATE(ts, INTERVAL 1-DAYOFWEEK(ts) DAY)) * 1000 as datetime,";

			$sql .= "
			    MIN(low) as 'low',
			    MAX(high) as 'high',
			    SUBSTRING_INDEX(GROUP_CONCAT(open ORDER BY ts
			                SEPARATOR ','),
			            ',',
			            + 1) AS 'open',
			    SUBSTRING_INDEX(GROUP_CONCAT(close ORDER BY ts
			                SEPARATOR ','),
			            ',',
			            - 1) AS 'close',
			    SUM(vol) as 'vol'
			FROM
			    eur_usd_day_bid
			 WHERE
			     ts BETWEEN '$startTime' and '$endTime'
			GROUP BY 1
			ORDER BY datetime";

		//seconds and minutes use raw quotes
		}else if(in_array($ts_len, array('s', 'm'))){

			//convert timeslice duration to seconds
			if($ts_len ==  'm')
				$ts_duration *= 60;

			//sql quotes
			$sql = "SELECT 
			    pair,
			    FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(ts) / $ts_duration) * $ts_duration) AS timeslice,
			    UNIX_TIMESTAMP( FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(ts) / $ts_duration) * $ts_duration) ) * 1000 as datetime,
			    MIN($bid_offer) as 'low',
			    MAX($bid_offer) as 'high',
			    SUBSTRING_INDEX(GROUP_CONCAT($bid_offer ORDER BY ts
			                SEPARATOR ','),
			            ',',
			            + 1) AS 'open',
			    SUBSTRING_INDEX(GROUP_CONCAT($bid_offer ORDER BY ts
			                SEPARATOR ','),
			            ',',
			            - 1) AS 'close',
			    count(*) as 'vol'
			FROM
			    quotes
			WHERE
			    pair = '$pair' AND ts BETWEEN '$startTime' and '$endTime'
			GROUP BY timeslice
			ORDER BY datetime";

		//hour table
		}else if($ts_len == 'h'){

			$sql = "SELECT ts, UNIX_TIMESTAMP(ts) * 1000 as datetime,
			    MIN(low) as 'low',
			    MAX(high) as 'high',
			    SUBSTRING_INDEX(GROUP_CONCAT(open ORDER BY ts
			                SEPARATOR ','),
			            ',',
			            + 1) AS 'open',
			    SUBSTRING_INDEX(GROUP_CONCAT(close ORDER BY ts
			                SEPARATOR ','),
			            ',',
			            - 1) AS 'close',
			    SUM(vol) as 'vol'
			FROM
			    eur_usd_hour_bid
			 WHERE
			     ts BETWEEN '$startTime' and '$endTime'
			GROUP BY 1
			ORDER BY datetime";
		}


//var_dump($sql); exit;

		$res = Doo::db()->fetchAll($sql, array(':pair' => $pair, ':startTime' => $startTime, ':endTime' => $endTime));
		$rows = array();
		$res = Doo::db()->fetchAll($sql);
//var_dump($res); exit;

		foreach($res as $row){
			extract($row);
			$rows[] = "[$datetime,$open,$high,$low,$close]";
		}
		// print it
		header('Content-Type: text/javascript');

		echo "/* console.log(' start = $start, end = $end, startTime = $startTime, endTime = $endTime '); */";
		echo $callback ."([\n" . join(",\n", $rows)."\n]);";

	}

}
?>