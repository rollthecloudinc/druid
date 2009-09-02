<?php
require_once('collection_iterator.php');
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/savable.php');
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/destroyable.php');
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/xml.php');
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/dumpable.php');
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../dom/dom_element.php');

class ActiveRecordCollection implements arrayaccess,IteratorAggregate,Countable,ActiveRecordSavable,ActiveRecordDestroyable,IActiveRecordXML,ActiveRecordDumpable  {

	protected $container;
	
	public function __construct() {
	
		$this->container = array();
		
		$args = func_get_args();
		
		foreach($args as $record) {
		
			if($record instanceof IActiveRecordDataEntity) {
			
				$this->container[] = $record;
			
			}
		
		}
	
	}
	
	public function offsetSet($offset,$value) {
		
		if(empty($offset)) {
			$offset = count($this->container);
			$this->container[$offset] = $value;
		} else {
			$this->container[$offset] = $value;
		}
	
	}
	
	public function offsetExists($offset) {
	
		return isset($this->container[$offset]);
	
	}
	
	public function offsetUnset($offset) {
	
		unset($this->container[$offset]);
	
	}
	
	public function offsetGet($offset) {
		
		return isset($this->container[$offset]) ? $this->container[$offset] : null;
	
	}
	
	public function getIterator() {
	
		return new ActiveRecordCollectionIterator($this->container);
	
	}
	
	public function count() {
	
		return count($this->container);
	
	}
	
	public function save() {
	
		if(empty($this->container)) return false;
		
		$save = new ActiveRecordSave();			
		foreach($this->container as $record) $save->addRecord($record);
		
		return $save->query(ActiveRecord::getConnection());
	
	}

	public function destroy() {
	
		if(empty($this->container)) return false;
		
		$config = ActiveRecordModelConfig::getModelConfig(get_class($this->container[0]));
		$node = new ActiveRecordCascadeNode($config);
		
		foreach($this->container as $record) $node->addRecord($record);
		
		try {
		
			$delete = new ActiveRecordDestroy();
			$cascade = new ActiveRecordCascade($delete);
			$cascade->cascade($node);
		
		} catch(Exception $e) {
			
			throw new Exception('Error initializing delete. Exception caught and rethrown from line '.__LINE__.' in class '.__CLASS__.' inside method '.__METHOD__.': '.$e->getMessage());
			return false;
		
		}
		
		try {
		
			if($delete->query(ActiveRecord::getConnection())===true) {
			
				$unset = new ActiveRecordDeactivate();
				$cascade = new ActiveRecordCascade($unset);
				$cascade->cascade($node);
				return true;
				
			} else {
			
				return false;
			
			}
		
		} catch(Exception $e) {
		
			throw new Exception('Error executing delete queries. Exception caught and rethrown from line '.__LINE__.' in class '.__CLASS__.' inside method '.__METHOD__.': '.$e->getMessage());
			return false;
		
		}
	
	}	
	
	public function toXML() {

		$dom = new ActiveRecordDOMElement($this);
		header('Content-Type: text/xml; charset=utf-8');
		$dom->formatOutput = true;
		echo $dom->saveHTML();	
	
	}
	
	public function toDOMElement() {
		
		return new ActiveRecordDOMElement($this);
	
	}
	
	public function dump() {
	
		echo '<pre>',print_r($this),'</pre>';
		
	}
	
	public function setProperty($name,$value) {
	
		$total = $this->count();
		for($i=0;$i<$total;$i++) {
			$this->container[$i]->setProperty($name,$value);
		}
	
	}

}
?>