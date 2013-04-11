<?php
// Author: Alex Hurd
// This script will:
// -create a mysql user 'forex' if it doesnt exist. 
// -create the following databases if they dont exist : 
// 		-forex


//only run via CLI
if(!defined('STDIN') ) exit;

//get auth
writeLine("Please enter local mysql administrator username: [root]");
$username = read_line();
writeLine("password:");
$password = read_line();

//connect via pdo
$dbh = null;
try{
	$dbh = new PDO('mysql:host=127.0.0.1;', $username, $password);
} catch (PDOException $e) {
	writeLine('Connection failed: ' . $e->getMessage());
	return;
}

//Check for forex account
$sql  = "SELECT 
		COUNT(*) as count
	FROM
		mysql.user
	WHERE
		user = 'forex'";
$stmt = $dbh->query($sql);
$res = $stmt->fetch(PDO::FETCH_ASSOC);
if((int)$res['count'] == 0){
	writeLine("'forex' user NOT found! Adding..");
	//add acount
	$sql = "GRANT USAGE ON *.* TO 'forex'@'localhost' IDENTIFIED BY PASSWORD '*A8946DC43650F3217043FCB748FAAFEDC65E81E5';
			GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, LOCK TABLES, EXECUTE ON `forex`.* TO 'forex'@'localhost';";
	$stmt = $dbh->query($sql);		
}else{
	writeLine("'forex' user found!");
}

//Create database + tables
writeLine("Installing 'forex' database");
$stmt = $dbh->query("CREATE DATABASE forex");
$cmd = "mysql -u {$username} -p{$password} -D forex < protected/db/forex.sql";
$result = exec($cmd);
//print if we have error?
if(!empty($result)){
	writeLine($result);
}	

//done
writeLine("Done!");

// Exit correctly
exit(0);

function writeLine($msg){
	fwrite(STDOUT, $msg . "\n");
}
function read_line(){
	return trim(fgets(STDIN));
}
?>