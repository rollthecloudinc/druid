<?php
if($argc != 6) { ?>

	options: host,user,db,password and target are required
	
<?php }

require_once(str_replace('//','/',dirname(__FILE__).'/') .'../core/connection/connection.php');
require_once(str_replace('//','/',dirname(__FILE__).'/') .'../rake/rake.php');

$db = new Connection($argv[1],$argv[2],$argv[3],$argv[4]);

?>
