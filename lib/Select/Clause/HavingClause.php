<?php

namespace Druid\Select\Clause;

use Druid\Select\SelectNode as ActiveRecordSelectNode;

class HavingClause {

	private $_having;
	private $_nodes;
	private $_havingData;

	public function __construct() {
	
		$this->_nodes = array();
		$this->_having = array();
		$this->_havingData = array();
	
	}
	
	public function getHaving() {
	
		return $this->_having;
	
	}
	
	public function getHavingData() {
	
		return $this->_havingData;
	
	}
	
	public function getNodes() {
	
		return $this->_nodes;
	
	}
	
	public function toSql() {
		
		$having = array();
		foreach($this->_having as $have) {
			
			if(empty($have)) continue;
			
			$having[] = implode(' ',$have);
		
		}
		
		return implode(' ',$having);
	
	}
	
	public function addHaving(ActiveRecordSelectNode $pNode,$pHaving) {
	
		$index = array_search($pNode,$this->_nodes);
		
		if($index===false) {
			$this->_having[] = array();
			$this->_nodes[] = $pNode;
			$index = array_search($pNode,$this->_nodes);
		}
	
		$config = $pNode->getConfig();
    	$className = $config->getClassName();
		
		$str = '';
		
		if(is_array($pHaving)) {
			
			$str = array_shift($pHaving);
			$this->_havingData = array_merge($this->_havingData,$pHaving);
			
		} else {
			
			$str = $pHaving;
			
		}
			
		//$str = $pAliases->replaceWithAlias($str);
			
		$this->_having[$index][] = $str;		
	
	}

}