<?php
class ActiveRecordInsertStatement {

	const insertTransform = 'insert';

	protected $_config;
	
	protected $_insert;
	protected $_insertData;
	
	protected $_bluePrint;
	
	public function __construct(IActiveRecordModelConfig $pConfig) {
	
		$this->_config = $pConfig;
		$this->_insert = array();
		$this->_insertData = array();
	
	}
	
	public function toSql() {
	
		/*
		* use the first entity as a blueprint
		*/
		
		$structure = array();
		
		foreach($this->_config->getFields() as $field) {
		
			if($this->_bluePrint->hasProperty($field)) {
			
				$structure[] = $field;
			
			}
		
		}
		
		$data = array();
		
		foreach($this->_insert as $entity) {
		
			$data[] = '('.implode(',',$entity).')';
		
		}
		
		return 'INSERT INTO '.$this->_config->getTable().' ('.implode(',',$structure).') VALUES '.implode(',',$data);
	
	}
	
	public function getData() {
	
		return $this->_insertData;
	
	}
	
	public function setBluePrint(IActiveRecordDataEntity $pEntity) {
		
		$this->_bluePrint = $pEntity;
	
	}
	
	public function addValue(IActiveRecordDataEntity $pEntity,$pApplyTransform=true) {
	
		if(is_null($this->_bluePrint)===true) {
			$this->setBluePrint($pEntity);
		}
	
		$insert = array();
	
		$transform = $this->_config->hasTransformations()?$this->_config->getTransformations():array();
	
		foreach($this->_config->getFields() as $field) {
		
			if($pEntity->hasProperty($field)===false) continue;
		
			if(
			$pApplyTransform === true
			&& !empty($transform)
			&& array_key_exists($field,$transform) 
			&& array_key_exists(self::insertTransform,$transform[$field])
			) {
			
				$fieldTransformation = $transform[$field][self::insertTransform];
			
				if(is_array($fieldTransformation)) {
				
					$statement = str_replace($this->_config->getClassName().'.',$this->_config->getTable().'.',array_shift($fieldTransformation));
					$this->_insertData = array_merge($this->_insertData,$fieldTransformation);
				
				} else {
				
					$statement = str_replace($this->_config->getClassName().'.',$this->_config->getTable().'.',$fieldTransformation);
				
				}
				
				$statement = $this->_replaceSpecialKeywords($statement,$pEntity,$field);
				
				$insert[] = $statement;
				
				if(is_array($pEntity->getProperty($field))) {
				
					$this->_insertData = array_merge($this->_insertData,$pEntity->getProperty($field));
					
				} else {
				
					$this->_insertData[] = $pEntity->getProperty($field);
				
				}
			
			} else {
			
				$this->_insertData[] = $pEntity->getProperty($field);
				$insert[] = '?';		
			
			}
		
		}
		
		$this->_insert[] = $insert;
	
	}
	
	protected function _replaceSpecialKeywords($pStatement,IActiveRecordDataEntity $pEntity,$pField) {
	
	
		$matches = array();
		preg_match_all('/{.*?}/',$pStatement,$matches);
		
		if(array_key_exists(0,$matches) && !empty($matches[0])) {
		
			foreach($matches[0] as $key=>$match) {
				
				$propertyName = str_replace(array('{','}'),'',$match);
				if($pEntity->hasProperty($propertyName)===true) {
					
					$value = $pEntity->getProperty($propertyName);
					$value = is_string($value)?'\''.$value.'\'':$value;
					$pStatement = str_replace($match,$value,$pStatement);
				
				}
			
			}
		
		}
		
		return $pStatement;
	
	
	}

}
?>