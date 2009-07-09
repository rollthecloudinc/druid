<?php
require_once('select_statement.php');
class ActiveRecordCountStatement extends ActiveRecordSelectStatement {

	public function __construct(ActiveRecordSelectNode $pRootNode,$pOptions=null) {
	
		parent::__construct($pRootNode,$pOptions);
	
	}

	protected function _init() {
	
		$this->_selectClause = new ActiveRecordSelectClause();
		$this->_whereClause = new ActiveRecordWhereClause();
		$this->_havingClause = new ActiveRecordHavingClause();
		$this->_sortClause = new ActiveRecordSortClause();
		$this->_groupClause = new ActiveRecordGroupClause();
		$this->_limitClause = new ActiveRecordLimitClause();
		
		$primaryKeyField = $this->_root->getConfig()->getPrimaryKey();
		$selectDynamic = array('total'=>'COUNT(DISTINCT t'.$this->_root->getUnique().'.'.$primaryKeyField.')');
	
		$this->_selectClause->selectDynamic($this->_root,$selectDynamic);
	
	}
	

	protected function _applyAll(ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParent=null) {
	
		$this->_applyFrom($pNode,$pParent);
		
		//$this->_applySelect($pNode);
		$this->_applyFilter($pNode);
		//$this->_applySort($pNode);
		//$this->_applyGroup($pNode);
		//$this->_applyHaving($pNode);
	
	}

}
?>