<?php
class ActiveRecordSortClause {	

	private $_nodes;
	private $_sort;

	public function __construct() {
	
		$this->_nodes = array();
		$this->_sort = array();
	
	}
	
	public function getNodes() {
	
		return $this->_nodes;
	
	}
	
	public function getSort() {
	
		return $this->_sort;
	
	}
	
	public function toSql() {
		
		$sorts = array();
		foreach($this->_sort as $sort) {
			
			if(empty($sort)) continue;
			
			$sorts[] = implode(',',$sort);
		
		}
		
		return implode(',',$sorts);
	
	}
	
	public function addSort(ActiveRecordSelectNode $pNode,$pSort) {
	
		$index = array_search($pNode,$this->_nodes);
		
		if($index===false) {
			$this->_nodes[] = $pNode;
			$this->_sort[] = array();
			$index = array_search($pNode,$this->_nodes);
		}
	
		$config = $pNode->getConfig();	
		$alias = 't'.$pNode->getUnique();
    	$fields = $config->getFields();
    	
    	foreach($pSort as $key=>$sort) {
    	
    		$field = is_string($key)?$key:$sort;
    		
    		if(in_array($field,$fields)) {
    			$this->_sort[$index][] = is_string($key)?$alias.'.'.$key.' '.$sort:$alias.'.'.$sort.' DESC';
    		} else {
    			$this->_sort[$index][] = is_string($key)?'t'.$pNode->getUNique().'_'.$key.' '.$sort:'t'.$pNode->getUnique().'_'.$sort.' DESC';
    		}
    	}		
	
	}

}
?>