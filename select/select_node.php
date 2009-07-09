<?php
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../interface/node.php');
require_once('find/find_config.php');
class ActiveRecordSelectNode implements IActiveRecordNode {

	private $_child;
	private $_sibling;
	
	private $_unique;
	private static $_count = 0;
	
	private $_config;
	private $_find;
	
	public function __construct(IActiveRecordModelConfig $pConfig,IActiveRecordFindConfig $pFindConfig=null) {
		$this->_config = $pConfig;
		$this->_find = is_null($pFindConfig)?new ActiveRecordFindConfig(array()):$pFindConfig;
		$this->_unique = self::$_count++;
	}
	
	public function addSibling(IActiveRecordList $pElement) {
		if($this->hasSibling()===true) { 
			$this->getSibling()->addSibling($pElement);
		} else {
			$this->setSibling($pElement);
		}
	}
	
	public function addChild(IActiveRecordNode $pElement) {
		if($this->hasChild()===true) { 
			$this->getChild()->addSibling($pElement);
		} else {
			$this->setChild($pElement);
		}
	}
	
	public function hasSibling() {
		return is_null($this->_sibling)?false:true;
	}
	
	public function hasChild() {
		return is_null($this->_child)?false:true;
	}
	
	public function getSibling() {
		return $this->_sibling;
	}
	
	public function getChild() {
		return $this->_child;
	}
	
	public function setSibling(IActiveRecordList $pElement) {
		$this->_sibling = $pElement;
	}
	
	public function setChild(IActiveRecordNode $pElement) {
		$this->_child = $pElement;
	}
	
	public function getConfig() {
	
		return $this->_config;
	
	}
	
	public function getFindConfig() {
	
		return $this->_find;
	
	}
	
	public function getUnique() {
		return $this->_unique;
	}
	
	public static function resetCount() {
	
		self::$_count = 0;
	
	}

}
?>