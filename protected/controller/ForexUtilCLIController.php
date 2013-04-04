<?php

class ForexUtilCLIController extends DooCLIController {

	function forex_update() {

    	$current_timeperiod = array();
        while(1){

            echo "UTC:".time() . PHP_EOL; 

            $url = "http://webrates.truefx.com/rates/connect.html";

            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: application/x-www-form-urlencoded'));
            $data = curl_exec($ch);
            curl_close($ch);

            $pairCount = 0;
            $valueStr = $data;
            $index = strpos($valueStr, "/");

            while($index > -1){

                    $pairCount++;
                    $valueStr = substr($valueStr, $index+1);
                    $index = strpos($valueStr, "/");
            }
            if($pairCount > 0){
                $valueStr = $data;


                $pairs = $this->parseIt($valueStr, 7, $pairCount);
                $valueStr = substr($valueStr, $pairCount * 7);

                $bidBigNumber = $this->parseIt($valueStr, 4, $pairCount);
                $valueStr = substr($valueStr,$pairCount*4);

                $bidPoints = $this->parseIt($valueStr, 3, $pairCount);
                $valueStr = substr($valueStr, $pairCount * 3);

                $offerBigNumber = $this->parseIt($valueStr, 4, $pairCount);
                $valueStr = substr($valueStr, $pairCount * 4);

                $offerPoints = $this->parseIt($valueStr, 3, $pairCount);
                $valueStr = substr($valueStr, $pairCount * 3);

                $highs = $this->parseIt($valueStr, 7, $pairCount);
                $valueStr = substr($valueStr, $pairCount * 7);
                $lows = $this->parseIt($valueStr, 7, $pairCount);
                $valueStr = substr($valueStr, $pairCount * 7 );

                $msTime = $this->parseIt($valueStr, 13, $pairCount);
                $valueStr = substr($valueStr, $pairCount * 13);


                for($i = 0 ; $i < count($pairs); $i++){


                    $pair = $pairs[$i];
                    $bid = preg_replace('/#/', '', $bidBigNumber[$i]) . $bidPoints[$i];
                    $offer = preg_replace('/#/', '', $offerBigNumber[$i]) . $offerPoints[$i];
                    $time =  $msTime[$i];


                    //first time set and go straight to sql
                    if(!isset($current_timeperiod[$pair])){
                        $current_timeperiod[$pair]['hour'] = date('Y-m-d H', $time/1000);
                        $current_timeperiod[$pair]['day'] = date('Y-m-d', $time/1000);
                    }else{

                        //check if we need to do a hourly agg
                        if($current_timeperiod[$pair]['hour'] != date('Y-m-d H', $time/1000) ){

                            $date = $current_timeperiod[$pair]['hour'] . ":00:00";
                            $cmd = "php cli.php quote_aggregator hour $pair '$date'";
                            $p = new Process($cmd);
                           
                            //update
                            $current_timeperiod[$pair]['hour'] = date('Y-m-d H', $time/1000);
                        }
                       
                        //check if we need to do a daily
                        if($current_timeperiod[$pair]['day'] != date('Y-m-d', $time/1000)){
                            
                            $date = $current_timeperiod[$pair]['day'];
                            $cmd = "php cli.php quote_aggregator day $pair '$date'";
                            $p = new Process($cmd);

                            //update
                            $current_timeperiod[$pair]['day'] = date('Y-m-d', $time/1000);
                        }
                    }
                    
                    //SQL insert / upsert
                    $sql = "INSERT IGNORE INTO quotes (pair,bid,offer,ts) VALUES('$pair', $bid, $offer, FROM_UNIXTIME($time/1000))";
                    Doo::db()->query($sql);

                    $sql = "INSERT INTO quotes_live (pair,bid,offer,ts) VALUES('$pair', $bid, $offer, FROM_UNIXTIME($time/1000))
                            ON DUPLICATE KEY UPDATE bid = $bid, offer = $offer, ts = FROM_UNIXTIME($time/1000)";
                    Doo::db()->query($sql);
                }
            }
        }
    }

	private function parseIt($valueStr, $tokenLength, $tokenCount) {

        $start = 0;
        $end = $start + $tokenLength;
        for($index = 0; $index < $tokenCount; $index++) {
                $tokens[] = substr($valueStr, $start, $tokenLength);
                $start = $end;
                $end = $start + $tokenLength;
        }
        return $tokens;
    }

    function forex_loader_month(){

        //check for args
        if(count($this->arguments) != 2){
            $this->writeLine("Usage: forex_loader_month 'folderpath'");
            exit;
        }
        $dir = $this->arguments[1];

        if (is_dir($dir)) {  
            if ($dh = opendir($dir)) {  
                  
                while (($file = readdir($dh)) !== false)   {  
                    
                    //check for csv
                    if(preg_match('/\.csv/', $file)){
                        $this->writeLine("Processing: " . $file);
                        $cmd = "php cli.php forex_loader '$dir$file'";
                        $p = new Process($cmd);
                    }
                }  
                closedir($dh);  
            }  
        }

        $this->writeLine("Done...!");
    }

	function forex_loader() {

        //check for args
        if(count($this->arguments) != 2){
            $this->writeLine("Usage: forex_loader 'filepath'");
            exit;
        }
        $file = $this->arguments[1];

        $res = Doo::db()->fetchRow("SELECT @@max_allowed_packet as max_allowed_packet");
        $max_len = (int)$res['max_allowed_packet'] - 4096;

        $sql = "INSERT IGNORE INTO quotes VALUES";
        $values = "";

		$fp = fopen($file, 'r');
		while(!feof($fp)){
			//get onle line 
			$buffer = fgets($fp);

			$row = str_getcsv($buffer);

			$pair = $row[0];
			$ts = $row[1];
			$bid = $row[2];
			$offer = $row[3];

			if (($timestamp = strtotime($ts)) === false) 
				continue;

			$mysqlts = date( 'Y-m-d H:i:s', $timestamp );
			if(!preg_match('/[0-9]{2}:[0-9]{2}:[0-9]{2}\.(?P<ms>[0-9]{3})/',  $ts, $match))
				continue;

			$ms = $match['ms'];

            if($values) $values .= ",";
            $values .= "('$pair', '$mysqlts.$ms', $bid, $offer)";

            if(strlen($values) >= $max_len) {  
                Doo::db()->query($sql . $values);
                $values = "";
            }
		}

        //any rows left over?
        if($values) {
            Doo::db()->query($sql . $values);
        }

		fclose($fp);
	}

    function quote_aggregator(){

        $time_start = microtime(true);

        //check for args
        if(count($this->arguments) != 4){
            $this->writeLine("Usage: quote_aggregator day|hour pair datetime");
            exit;
        }
        $day_hour = $this->arguments[1];
        $pair = $this->arguments[2];
        $datetime = $this->arguments[3]; 

        if($day_hour == 'day'){


            $sql = "INSERT IGNORE INTO agg_day(
            SELECT 
                pair,
                DATE(ts) AS timeslice,
                SUBSTRING_INDEX(GROUP_CONCAT(bid
                            SEPARATOR ','),
                        ',',
                        + 1) AS 'bid_open',
                MAX(bid) AS 'bid_high',
                MIN(bid) AS 'bid_low',
               
                SUBSTRING_INDEX(GROUP_CONCAT(bid ORDER BY ts
                            SEPARATOR ','),
                        ',',
                        - 1) AS 'bid_close',

                SUBSTRING_INDEX(GROUP_CONCAT(offer ORDER BY ts
                            SEPARATOR ','),
                        ',',
                        + 1) AS 'offer_open',
                MAX(offer) AS 'offer_high',
                MIN(offer) AS 'offer_low',
               
                SUBSTRING_INDEX(GROUP_CONCAT(offer ORDER BY ts
                            SEPARATOR ','),
                        ',',
                        - 1) AS 'offer_close',
                COUNT(*)
            FROM
                quotes
            WHERE pair = '$pair' AND ts BETWEEN '$datetime' AND '$datetime' + INTERVAL 1 DAY
            GROUP BY timeslice
            ORDER BY ts)";

        }elseif($day_hour == 'hour'){


            $sql = "INSERT IGNORE INTO agg_hour(
            SELECT 
                pair,
                FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(ts) / 3600) * 3600) AS timeslice,
                SUBSTRING_INDEX(GROUP_CONCAT(bid
                            ORDER BY ts
                            SEPARATOR ','),
                        ',',
                        + 1) AS `open`,
                MAX(bid) AS 'high',
                MIN(bid) AS 'low',
                SUBSTRING_INDEX(GROUP_CONCAT(bid
                            ORDER BY ts
                            SEPARATOR ','),
                        ',',
                        - 1) AS `close`,
                SUBSTRING_INDEX(GROUP_CONCAT(offer
                            ORDER BY ts
                            SEPARATOR ','),
                        ',',
                        + 1) AS `offeropen`,
                MAX(offer) AS 'offerhigh',
                MIN(offer) AS 'offerlow',
                SUBSTRING_INDEX(GROUP_CONCAT(offer
                            ORDER BY ts
                            SEPARATOR ','),
                        ',',
                        - 1) AS `offerclose`,
                COUNT(*)
            FROM
                quotes
            WHERE  pair = '$pair' AND ts BETWEEN '$datetime' AND '$datetime' + INTERVAL 1 HOUR
            GROUP BY timeslice
            ORDER BY ts)";

        }

        //fire off sql
        Doo::db()->query($sql);

        //get script duration in seconds
        $time_end = microtime(true);
        $time = $time_end - $time_start;

        //log completion timestamp
        $msg = date( 'Y-m-d H:i:s') . " : COMPLETE $pair - $day_hour - $datetime - duration(s): $time" . PHP_EOL;
        file_put_contents(Doo::conf()->SITE_PATH . "protected/log/quote_aggregator.log", $msg, FILE_APPEND | LOCK_EX);
         
    }

}
?>