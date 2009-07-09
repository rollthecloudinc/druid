<?php
require_once('generate.php');
class ActiveRecordRake {

	protected $db;
	protected $target;
	protected $tables;

	public function __construct(PDO $db,$target,$tables=null) {
	
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