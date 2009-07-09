<?php

/*
* This will eventually be a command line script to generate all model files
*/

require_once(str_replace('//','/',dirname(__FILE__).'/') .'../rake/rake.php');
require_once(str_replace('//','/',dirname(__FILE__).'/') .'../core/connection/connection.php');

/* $args = array();

if($argc!=1 && (($argc-1)%2)==0) {
	$i=1;
	while($i<$argc) {
		$args[$argv[$i++]] = $argv[$i++];
	}
}*/

$db = new Connection($argv[1],$argv[2],$argv[3],$argv[4]);*/
?>
