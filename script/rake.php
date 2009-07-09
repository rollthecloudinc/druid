<?php
/*
* command line script to generate model files
* requires PHP 5.0: php -v 
* use: php PATH -h localhost -u username -d dbname -p password -t WRITE_DIR_PATH
*/

require_once(str_replace('//','/',dirname(__FILE__).'/') .'../rake/rake.php');

$args = array();

if($argc!=1 && (($argc-1)%2)==0) {
	$i=1;
	while($i<$argc) {
		$args[$argv[$i++]] = $argv[$i++];
	}
}

// make sure required options have been provided
if(!array_key_exists('-d',$args) || !array_key_exists('-t',$args) || !array_key_exists('-p',$args)) {
	echo 'options -d,-p and -u are required to run this script.';
	exit;
}

$host = array_key_exists('-h',$args)?$args['-h']:'localhost';
$user = array_key_exists('-u',$args)?$args['-u']:'root';
$name = $args['-d'];
$pwd = $args['-p'];
$target = $args['-t'];

// make sure target directory exists
if(!is_dir($target)) {
	echo 'Script can\'t procede because target directory '.$target.' does not exist';
	exit;
}

try {

	$db = new PDO("mysql:dbname=$name;host=$host",$user,$pwd);
	new ActiveRecordRake($db,$target);
	echo "\n".'Script executed sucessfully'."\n";

} catch(PDOException $e) {

	echo 'Connection Error: '.$e->getMessage();
	
} catch(Exception $e) {

	echo 'Basic Error: '.$e->getMessage();
	
}
?>
