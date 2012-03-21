<?php
require_once(str_replace('//','/',dirname(__FILE__).'/').'storage/active_record.php');

$strDirectory = (string) ActiveRecord::getConfig()->models->directory;
$strModelsDirectory = str_replace('//','/',dirname(__FILE__).'/').$strDirectory;

if(!is_dir($strModelsDirectory)) {
	
	try {
	
		require_once(str_replace('//','/',dirname(__FILE__).'/').'rake/rake.php');
		mkdir($strModelsDirectory);
		new ActiveRecordRake(ActiveRecord::getConnection(),$strModelsDirectory);
	
	} catch(PDOException $e) {
	
		echo 'Connection Error: '.$e->getMessage();
		
	} catch(Exception $e) {
	
		echo 'Basic Error: '.$e->getMessage();
		
	}
	
}
?>