<?php
require_once('model_config.php');
class ActiveRecordDynamicModel extends ActiveRecordModelConfig {

	protected $select;

	public function __construct(ActiveRecordSelectStatement $select) {
		
		$select->toSql();
		$this->select = $select;
	
	}
	
	public function getTable() {
	
		return '('.$this->select->toSql().')';
	
	}
	
	public function getFields() {
		
		$fields = array();
		
		foreach($this->select->getSelectClause()->getFields() as $table) {
		
			foreach($table as $field=>$alias) {
			
				$fields[] = $field;
			
			}
		
		}
		
		return $fields;
		
	
	}
	
	public function getBindData() {
	
		return $this->select->getBindData();
	
	}

}
?>