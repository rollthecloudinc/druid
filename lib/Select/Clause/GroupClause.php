<?php

namespace Druid\Select\Clause;

use Druid\Select\SelectNode as ActiveRecordSelectNode;

class GroupClause {

	private $_group;
	private $_nodes;

	public function __construct() {
	
		$this->_group = array();
		$this->_nodes = array();
	
	}
	
	public function getGroup() {
	
		return $this->_group;
	
	}
	
	public function getNodes() {
	
		return $this->_nodes;
	
	}
	
	public function toSql() {
		
		$groups = array();
		foreach($this->_group as $group) {
			
			if(empty($group)) continue;
			
			$groups[] = implode(',',$group);
		
		}
		
		return implode(',',$groups);
	
	}
	
	public function addGroup(ActiveRecordSelectNode $pNode,$pGroup) {
	
		$index = array_search($pNode,$this->_nodes);
		
		if($index===false) {
			$this->_nodes[] = $pNode;
			$this->_group[] = array();
			$index = array_search($pNode,$this->_nodes);
		}
		
		$config = $pNode->getConfig();
		$alias = 't'.$pNode->getUnique();
    	$className = $config->getClassName();
    	
    	
    	$fields = $config->getFields();
    	
    	foreach($pGroup as $key=>$group) {
    		if(in_array($group,$fields)) {
    			$this->_group[$index][] = $alias.'.'.$group;
    		} else {
    			// just added this
    			$this->_group[$index][] = $alias.'_'.$group;
    		}
    	}
	
	}

}