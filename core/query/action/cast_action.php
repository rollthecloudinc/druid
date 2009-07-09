<?php
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../../interface/query_action.php');
class ActiveRecordCastAction implements IActiveRecordQueryAction {

	protected $records;

	public function __construct($records) {
	
		$this->records = $records;
	
	}

	public function doAction(PDO $db,PDOStatement $statement) {
	
		foreach($this->records as $record) {
			$record->cast();
		}
		
		return true;
	
	}

}
?>