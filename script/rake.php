<?php
/**
 * Command line script to generate model files from existing db.
 * version: php 5.6+
 */

require str_replace('//','/',dirname(__FILE__).'/').'../vendor/autoload.php';

use Druid\Storage\ActiveRecord as ActiveRecord;
use Druid\Rake\Rake as Rake;

// Get active record config.
$config = ActiveRecord::getConfig();

// Get database credentials from config.
$name = (string) $config->connection->name;
$host = (string) $config->connection->host;
$user = (string) $config->connection->user;
$password = (string) $config->connection->password;
$models = (string) $config->models->directory;

// Establish connection to db
$db = new PDO("mysql:dbname=$name;host=$host",$user,$password);

// Location of model files.
$directory = str_replace('//','/',dirname(__FILE__).'/')."../$models";

if(!is_dir($directory)) {
  mkdir($directory);
}

try {
  new Rake($db,$directory);
  echo PHP_EOL.'Script executed successfully'.PHP_EOL;
} catch(PDOException $e) {
  echo 'Connection Error: '.$e->getMessage();
} catch(Exception $e) {
  echo 'Basic Error: '.$e->getMessage();
}
