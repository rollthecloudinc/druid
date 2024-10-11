<?php

namespace Druid\Rake;

use Druid\Rake\Generate as ActiveRecordGenerate;
/*
* Writes model files to specified directory upon creation
* - May supply optional argument to constructor listing tables to convert to model files
* - the core/config file does not include this class by default.
*/
class Rake {

	protected $db;
	protected $target;
	protected $tables;
	
	/*
	*	1.) PDO Connection
	*	2.) Path to directory which model files will be written
	* 	3.) [optional] Rather then writting all tables to model files an array may be placed with names of tables to include
	*/
	public function __construct(\PDO $db,$target,$tables=null) {
	
		$this->db = $db;
		$this->target = $target;
		$this->tables = $tables;
		
		$this->_init();
	
	}
	
	protected function _init() {

		$g = new ActiveRecordGenerate($this->db);
		$g->generate($this->target,$this->tables);		
	
	}

}
?>