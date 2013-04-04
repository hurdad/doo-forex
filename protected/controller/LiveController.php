<?php

class LiveController extends DooController {

	function live() {

		//callback : required
		if (!isset( $_GET['callback'])  || !preg_match('/^[a-zA-Z0-9_]+$/',  $_GET['callback'])) {
			die('Invalid callback name');
		}
		$callback = $_GET['callback'];

		//query db
		$results = Doo::db()->find('QuotesLive', array('asArray' => true));

		//convert to float to strip trailing zeros
		foreach($results as $key=>$row){
			$results[$key]['bid'] = (float)$row['bid'];
			$results[$key]['offer'] = (float)$row['offer'];
		}

		//print it
		$this->setContentType('js');
		echo $callback ."(" . json_encode($results) . ");";
	}
}