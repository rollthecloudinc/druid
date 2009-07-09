<?php
class ActiveRecordCascadeNode {
	
	protected $config;
	protected $records;
	
	public function __construct(IActiveRecordModelConfig $config) {
		
		$this->config = $config;
		$this->records = array();
	
	}
	
	public function addRecord(IActiveRecordDataEntity $entity) {
	
		if($this->isCompatible($entity)===true) {
			
			$this->records[] = $entity;
		
		} else {		
		
			throw new Exception('Data entity instance of class '.get_class($entity).' is not compatible with '.$this->config->getClassName().'. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.'.');
		
		}
	
	}
	
	public function isCompatible(IActiveRecordDataEntity $entity) {
	
		$class = $this->config->getClassName();
		
		return $entity instanceof $class?true:false;
	
	}
	
	public function getConfig() {
	
		return $this->config;
	
	}
	
	public function getRecords() {
	
		return $this->records;
	
	}
	
	public function hasRecords() {
	
		return empty($this->records)?false:true;
	
	}

}
?>