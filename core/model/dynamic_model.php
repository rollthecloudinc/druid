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
	
	public function getFields($boolAlias=false) {
		
		$fields = array();
		
		// used for remapping aliases to fields
		$arrNodes = $this->select->getSelectClause()->getNodes();
		
		foreach($this->select->getSelectClause()->getFields() as $intIndex=>$table) {
		
			foreach($table as $field=>$alias) {
				
				$fields[] = $boolAlias === true?"t{$arrNodes[$intIndex]->getUnique()}_$field":$field;
			
			}
		
		}
		
		return $fields;
		
	
	}
	
	public function getBindData() {
	
		return $this->select->getBindData();
	
	}
	
	public function getSelect() {
		return $this->select;
	}

}
?>