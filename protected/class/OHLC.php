<?php

class OHLC {

	function __construct() {
      ini_set("memory_limit","512M");
   	}

	public function get_ohlc($pair, $start, $end, $bid_offer, $timeslice, $lag = 0){

		//to seconds factor lookup
		$this->to_seconds_factor['s'] = 1;
		$this->to_seconds_factor['m'] = 60;
		$this->to_seconds_factor['h'] = 60 * 60;
		$this->to_seconds_factor['d'] = 60 * 60 * 24;

		//predis class
		require 'Predis/lib/Predis/Autoloader.php';
		Predis\Autoloader::register();

		// set some utility variables
		$range = $end - $start;
		$startTime = gmstrftime('%Y-%m-%d %H:%M:%S', $start / 1000);
		$endTime = gmstrftime('%Y-%m-%d %H:%M:%S', $end / 1000);
		
		//set suggested timeslice depending on range
		//[1s,5s,10s,20s,30s,1m,5m,10m,20m,30m,1h,2h,3h,6h,1d,1w,1M]
		if(!isset($timeslice)){

			$stick_threshold = 300; //on screen count threshold

			//seconds
			if($range < $stick_threshold * 1000){
				$timeslice = '1s';
			}elseif($range < ($stick_threshold * 5 * 1000)){
				$timeslice = '5s';
			}elseif($range < ($stick_threshold * 10 * 1000)){
				$timeslice = '10s';
			}elseif($range < ($stick_threshold * 20 * 1000)){
				$timeslice = '20s';
			}elseif($range < ($stick_threshold * 30 * 1000)){
				$timeslice = '30s';

			//minutes
			}elseif($range < ($stick_threshold * 60 * 1000)){
				$timeslice = '1m';
			}elseif($range < ($stick_threshold * 60 * 5 * 1000)){
				$timeslice = '5m';
			}elseif($range < ($stick_threshold * 60 * 10 * 1000)){
				$timeslice = '10m';
			}elseif($range < ($stick_threshold * 60 * 20 * 1000)){
				$timeslice = '20m';
			}elseif($range < ($stick_threshold * 60 * 30 * 1000)){
				$timeslice = '30m';

			//hour
			}elseif($range < ($stick_threshold * 60 * 60 * 1000)){
				$timeslice = '1h';
			}elseif($range < ($stick_threshold * 60 * 60 * 2 * 1000)){
				$timeslice = '2h';
			}elseif($range < ($stick_threshold * 60 * 60 * 3 * 1000)){
				$timeslice = '3h';
			}elseif($range < ($stick_threshold * 60 * 60 * 6 * 1000)){
				$timeslice = '6h';

			//day
			}elseif($range < ($stick_threshold * 60 * 60 * 24 * 1000)){
				$timeslice = '1d';

			//week
			}elseif($range < ($stick_threshold * 60 * 60 * 7 * 1000)){
				$timeslice = '1w';

			//month 
			}else{
				$timeslice = '1M';
			}
		}

		//validate and parse timeslice
		if(!preg_match('/^(?P<dur>\d+)(?P<len>s|m|h|d|w|M)$/', $timeslice, $matches)){
			die("Invalid timeslice parameter: $timeslice");
		}

		//timeslice duration (1,2,3,4,..)
		$ts_duration = $matches['dur'];

		//timeslice length (s,m,h,d,w,M)
		$ts_len = $matches['len'];

		//check durations modulus of parent
		//60 seconds in a minute and 60 minute in an hour
		if(in_array($ts_len, array('s', 'm'))){
			if((60 % $ts_duration) != 0)
				//not evenly divisable by 60
				die("Invalid 1timeslice parameter: $ts_duration$ts_len");
		}
		//24 hours in day
		if(in_array($ts_len, array('h'))){
			if((24 % $ts_duration) != 0)
				//not evenly divisable by 24
				die("Invalid timeslice parameter: $ts_duration$ts_len");
		}

		//max 2 day|week|month
		if(in_array($ts_len, array('d', 'w', 'M'))){
			if($ts_duration > 2)
				//not valid duration
				die("Invalid timeslice parameter: $ts_duration$ts_len");
		}

		//check are not cacheing week and month
		$is_caching = in_array($ts_len, array('w', 'M')) ? false : true;

		//add lag
		if($lag > 0){
			if(!in_array($ts_len, array('w', 'M'))){

				//to sec
				$sec = $this->to_seconds_factor[$ts_len] * $ts_duration;

				//lag start 
				$start = ((ceil(($start / 1000) / $sec) * $sec) - ($sec * $lag)) * 1000;
				
			}else{
				if($ts_len == 'w'){
					$date = date_create_from_format('U', $start);
					date_sub($date, date_interval_create_from_date_string("$lag weeks"));
				}
				if($ts_len == 'M'){
					$date = date_create_from_format('U', $start);
					date_sub($date, date_interval_create_from_date_string("$lag months"));
				}

				//save new start
				$start = date_format($date, 'U') * 1000;
			}
		}

		//if no week or Month, round start/end time to match candle length
		if(!in_array($ts_len, array('w', 'M'))){

			//to sec
			$sec = $this->to_seconds_factor[$ts_len] * $ts_duration;

			//round up start time, round down end time
			$startTime = date( 'Y-m-d H:i:s', (ceil(($start / 1000) / $sec) * $sec));

			//round down end time 
			$endTime = date( 'Y-m-d H:i:s', (floor(($end / 1000) / $sec) * $sec) + $sec - 1);
		}

		//init vars
		$time_slices = array();
		$result = array();
		$ranges = array();
		$cache = array();
		$time_slices = array();
		$empty_time_slices = array();
		$is_cache_miss = false;
		$redis = new Predis\Client();
		$redis_zset = "$pair:$bid_offer:$timeslice";
		
		//query redis cache
		if($is_caching){

			//get list of expected time slices
			$time_slices = $this->buildTimeSlices($ts_len, $ts_duration, $start/1000, $end/1000);

			//check redis cache
			$return = $redis->zrangebyscore($redis_zset, $start/1000, $end/1000, array(
			    'withscores' => true
			));

			//build cache datastructure
			foreach($return as $row){
				$cache[$row[1]] = $row[0];
			}
		
			//get empty timeslices
			$return = $redis->zrangebyscore('empty:' . $redis_zset, $start/1000, $end/1000, array(
			    'withscores' => true
			));

			//remove empty timeslices from time_slices
			foreach($return as $row){
				unset($time_slices[$row[1]]);
			}

			//find missing slices
			$cache_miss_slices = array_diff_key($time_slices, $cache);

			//check for cache misses
			if(count($cache_miss_slices) > 0 ){
				$is_cache_miss = true;
				$redis->incr('cache_misses');

				//get ranges for missing slices
				$ranges = $this->buildRangeFromMissingTimeSlices($ts_len, $ts_duration, $cache_miss_slices);

			}else{
				$redis->incr('cache_hits');
				$results = $cache;
			}	
		} else{

			//build full range
			$ranges[] = array('startTime' => $startTime, 'endTime' => $endTime);
		}


		//hit database if we are not caching or if we have a cache miss
		if(!$is_caching || $is_cache_miss){

			//db
			$return = $this->fetchMySQLAndCache($ts_len, $ts_duration, $is_cache_miss ? $cache_miss_slices : $time_slices, $ranges, $is_caching, $redis, $redis_zset, $pair, $bid_offer);

			//add missing slices to cache obj
			if($is_cache_miss){

				foreach($return as $row){
					extract($row);
					$cache[$datetime] = "[$datetime,$open,$high,$low,$close,$vol]";
 				}

 				//sort by key (timestamp)
 				ksort($cache);
 				$results = $cache;
			}else{
				//build non cached results results
				foreach($return as $row){
					extract($row);
					$results[] = "[$datetime,$open,$high,$low,$close,$vol]";
				}
			}
		}	

		return $results;
	}

	private function fetchMySQLAndCache($ts_len, $ts_duration, $time_slices, $ranges, $is_caching, $redis, $redis_zset, $pair, $bid_offer){

		//build SQL range
		$range_sql = array();
		foreach($ranges as $r){
			$range_sql[] = " AND ts BETWEEN '" . $r['startTime'] . "' and '" . $r['endTime'] . "' ";
		}


		//build sql
		$sql = "";
		//if daily or above, use aggregated daily table otherwize use raw quotes
		if(in_array($ts_len, array('d', 'w', 'M'))){
	
			$sql = "SELECT "; 

			if($ts_len == 'd')
				$sql .= "ts, UNIX_TIMESTAMP(ts) * 1000 as 'datetime',";
				else if($ts_len == 'w')
				$sql .= "ADDDATE(ts, INTERVAL 1-DAYOFWEEK(ts) DAY) timeslice, UNIX_TIMESTAMP(ADDDATE(ts, INTERVAL 1-DAYOFWEEK(ts) DAY)) * 1000 as 'datetime',";
					else if($ts_len == 'M')
					$sql .= "ADDDATE(ts, INTERVAL 1-DAYOFMONTH(ts) DAY) timeslice, UNIX_TIMESTAMP(ADDDATE(ts, INTERVAL 1-DAYOFMONTH(ts) DAY)) * 1000 as 'datetime',";

			$sql .= "
			    MIN(" . ($bid_offer == 'bid' ? 'bid' : 'offer') . "_low) as 'low',
			    MAX(" . ($bid_offer == 'bid' ? 'bid' : 'offer') . "_high) as 'high',
			    SUBSTRING_INDEX(GROUP_CONCAT(" . ($bid_offer == 'bid' ? 'bid' : 'offer') . "_open ORDER BY ts
			                SEPARATOR ','),
			            ',',
			            + 1) AS 'open',
			    SUBSTRING_INDEX(GROUP_CONCAT(" . ($bid_offer == 'bid' ? 'bid' : 'offer') . "_close ORDER BY ts
			                SEPARATOR ','),
			            ',',
			            - 1) AS 'close',
			    SUM(vol) as 'vol'
			FROM
			    agg_day
			WHERE
				pair = '$pair' "
				 . join( '\n', $range_sql) . 
			"GROUP BY 1
			ORDER BY datetime";

		//seconds and minutes use raw quotes
		}else if(in_array($ts_len, array('s', 'm'))){

			//convert timeslice duration to seconds
			if($ts_len ==  'm')
				$ts_duration *= 60;

			//sql quotes
			$sql = "SELECT 
			    pair,
			    FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(ts) / $ts_duration) * $ts_duration) AS 'timeslice',
			    UNIX_TIMESTAMP( FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(ts) / $ts_duration) * $ts_duration) ) * 1000 as 'datetime',
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
			    pair = '$pair' "
			    . join( '\n', $range_sql) . 
			"GROUP BY timeslice
			ORDER BY datetime";

		//hour table
		}else if($ts_len == 'h'){
			//3600 seconds in a hour
			$ts_duration *= 3600;

			$sql = "SELECT 
				pair,
				FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(ts) / $ts_duration) * $ts_duration) AS 'timeslice',
			  	UNIX_TIMESTAMP(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(ts) / $ts_duration) * $ts_duration) ) * 1000 as 'datetime',
			    MIN(" . ($bid_offer == 'bid' ? 'bid' : 'offer') . "_low) as 'low',
			    MAX(" . ($bid_offer == 'bid' ? 'bid' : 'offer') . "_high) as 'high',
			    SUBSTRING_INDEX(GROUP_CONCAT(" . ($bid_offer == 'bid' ? 'bid' : 'offer') . "_open ORDER BY ts
			                SEPARATOR ','),
			            ',',
			            + 1) AS 'open',
			    SUBSTRING_INDEX(GROUP_CONCAT(" . ($bid_offer == 'bid' ? 'bid' : 'offer') . "_close ORDER BY ts
			                SEPARATOR ','),
			            ',',
			            - 1) AS 'close',
			    SUM(vol) as 'vol'
			FROM
			    agg_hour
			WHERE
			     pair = '$pair' "
			     . join( '\n', $range_sql) . 
			"GROUP BY timeslice
			ORDER BY datetime";
		}

		//log
		file_put_contents(Doo::conf()->SITE_PATH . "protected/log/sql.log", date( 'Y-m-d H:i:s') . " : " . $sql . PHP_EOL, FILE_APPEND | LOCK_EX);

		//query database
		$res = Doo::db()->fetchAll($sql);

		//only save to redis if we are cacheing
		if($is_caching){
		
			//save to redis
			$pipe = $redis->pipeline();
			foreach($res as $row){
				extract($row);

				//remove calculated timeslices
				if(isset($time_slices[$datetime/1000])){
					unset($time_slices[$datetime/1000]);

					//save to redis
					$pipe->zadd($redis_zset, $datetime/1000, "[$datetime,$open,$high,$low,$close,$vol]");
				}
			}
			$replies = $pipe->execute();

			//save remaining timeslices
			$pipe = $redis->pipeline();
			foreach($time_slices as $key => $val){
				$pipe->zadd('empty:' . $redis_zset, $key, $val['start'] . " - " . $val['end']);
			}
			$replies = $pipe->execute();
		}

		//save
		return $res;

	}

	//$start and $end in unixtimestamp [not used for week | month]
	private function buildTimeSlices($ts_len, $ts_duration, $start, $end){

		if(in_array($ts_len, array('w', 'M'))) return array();

		//to seconds
		$sec = $this->to_seconds_factor[$ts_len] * $ts_duration;

		//round up start
		$val = ceil($start / $sec) * $sec;
		//round down end
		$end = floor($end / $sec) * $sec;

		//check if end of 'end' is past current time
		if(($end + $sec - 1) > time()){
			//take time() timeslice and move back one timeslice
			$end = (floor(time() / $sec) * $sec) - $sec;
		}

		$data = array();
		while($val <= $end){

			if($val >= $start)
				$data[$val] =  array('start' => date( 'Y-m-d H:i:s', $val), 'end' => date( 'Y-m-d H:i:s', $val + $sec - 1));
			$val += $sec;
		}

		return $data;
	}

	//convert timeslice array to ranges for sql [not used for week | month]
	private function buildRangeFromMissingTimeSlices($ts_len, $ts_duration, $time_slices){

		if(in_array($ts_len, array('w', 'M'))) return array();

		//range contains keys with missing ranges
		$ranges = array();
		if(count($time_slices) > 0){

			//to seconds
			$sec = $this->to_seconds_factor[$ts_len] * $ts_duration;

			$time_slice_keys = array_keys($time_slices);
			$range_count = 0;
			$ranges[$range_count] = array();

			//add first 
			array_push($ranges[$range_count], $time_slices[$time_slice_keys[0]]);
			
			foreach($time_slices as $ts=>$s){

				//check if reach end
				if($ts == $time_slice_keys[count($time_slice_keys) - 1 ])
					continue;

				//check if next time slice not is contiguous
				if(!isset($time_slices[$ts + $sec])){
					$range_count++;
					//create range
					$ranges[$range_count] = array();
				}

				//add ts to range
				array_push($ranges[$range_count], $time_slices[$ts + $sec]);
			}
		}

		//set startTime and endTime keys
		foreach($ranges as $k=>$r){

			//first
			$startTime = $r[0]['start'];

			//last
			$endTime = $r[count($r) - 1]['end'];

			//save
			$ranges[$k]['startTime'] = $startTime;
			$ranges[$k]['endTime'] = $endTime;
		}

		return $ranges;
	}
}
?>