<?php

namespace Druid\Core\Query;

use Druid\Interfaces\QueryAction as IActiveRecordQueryAction;

/*
* All Queries are routed and executed through instances of this class
*/
class Query {
	
	/*
	* Query type constants 
	*/
	const 
	
	SELECT 		= 'select'
	,UPDATE		= 'update'
	,INSERT 	= 'insert'
	,DELETE		= 'delete';

	protected $data;
	protected $sql;
	
	protected $action;
	
	// will dump SQL to browser
	protected static $dump = false;
	
	public function __construct($sql='',$data=null,IActiveRecordQueryAction $action=null) {
	
		$this->data = is_null($data)?array():$data;
		$this->action = $action;
		$this->sql = $sql;
	
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function setSql($sql) {
		$this->sql = $sql;
	}
	
	public function getSql() {
		return $this->sql;	
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function addData($data) {
		$this->data[] = $data;
	}
	
	public function query(\PDO $db) {
		
		if(self::$dump===true) {
			$this->showQuery();
		}
	
		if(empty($this->sql)) throw new \Exception('Unable to process query empty SQL string. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.'.');
	
		if($stmt = $db->prepare($this->sql)) {
			
			if(!empty($this->data)) $this->bind($stmt);
			
			if($stmt->execute()) {
				
				if(!is_null($this->action)) $this->action->doAction($db,$stmt);				
				return $stmt;
			
			} else {
			
				throw new \Exception('ActiveRecord Query execution failed. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.'.');
			
			}
			
		} else {
		
			throw new \Exception('Active Record Query preparation failed. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.'.');
		
		}
	
	}
	
	protected function bind(\PDOStatement $stmt) {
	
		$bindCount = count($this->data);
		for($i=0;$i<$bindCount;$i++) $stmt->bindValue(($i+1),$this->data[$i],is_string($this->data[$i])?PDO::PARAM_STR:PDO::PARAM_INT);			
	
	}
	
	public function showQuery() {
	
		echo '<p>',$this->sql,'</p>';
		echo '<pre>',print_r($this->data),'</pre>';
	
	}
	
	public static function enableDump() {
		self::$dump = true;
	}
	
	public static function disableDump() {
		self::$dump = false;
	}

}
?>