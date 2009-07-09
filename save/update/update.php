<?php
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../core/model/model_config.php');
class ActiveRecordUpdate {

	const updateTransform = 'save';

	protected $record;
	protected $records;
	
	protected $sibling;
	
	protected $data;
	protected $structure;
	
	public function __construct(ActiveRecord $pRecord=null) {
	
		if($pRecord) $this->record = $pRecord;
		$this->records = array();
		$this->data = array();
		$this->structure = array();
	
	}
	
	public function getSibling() {
	
		return $this->sibling;
	
	}
	
	public function toSql() {
	
		if(is_null($this->record)) return '';
		
		$this->collectData();
		$set = ' SET '.implode(',',$this->structure);
		
		$config = ActiveRecordModelConfig::getModelConfig(get_class($this->record));		
		$keys = $this->collectPrimaryKeys();
		$pk = $config->getPrimaryKey();
		
		$where = ' WHERE '.$pk.' = '.implode(' OR '.$pk.' = ',$keys);
		$limit = ' LIMIT '.count($keys);
			
		return 'UPDATE '.$config->getTable().$set.$where.$limit;
	
	}
	
	public function getData() {
	
		return $this->data;
	
	}
	
	public function collectData() {
	
		$config = ActiveRecordModelConfig::getModelConfig(get_class($this->record));
		
		if($config->hasFields()===true) {
		
			foreach($config->getFields() as $field) {
			
				if($this->record->hasChanged($field)===true) {
				
					$this->collectFieldData($field);
				
				}
			
			}
		
		}
	
	}
	
	public function collectFieldData($field) {
	
		$config = ActiveRecordModelConfig::getModelConfig(get_class($this->record));
		
		$allTransforms = $config->hasTransformations()?$config->getTransformations():array();
		$fieldTransform = !empty($allTransforms) && array_key_exists($field,$allTransforms) && array_key_exists(self::updateTransform,$allTransforms[$field])?$allTransforms[$field][self::updateTransform]:array();
		
		if(!empty($fieldTransform)) {
		
			$this->structure[] = $field.' = '.$this->applyFieldTransform($this->record,$field,$fieldTransform,$allTransforms);
				
		} else {
	
			$this->data[] = $this->record->getProperty($field);
			$this->structure[] = $field.' = ?';
		
		}
	
	}
	
	public function setSibling(ActiveRecordUpdate $pSibling) {
	
		$this->sibling = $pSibling;
	
	}
	
	public function hasSibling() {
	
		return is_null($this->sibling)?false:true;
	
	}
	
	public function add(ActiveRecord $pRecord,$changed=false) {
	
		if($changed===false && $this->recordHasChanged($pRecord)===false) return;
	
		if(is_null($this->record)) {
			
				$this->record = $pRecord;
	
		} else if($this->isCompatible($pRecord)===true) {
		
			$this->records[] = $pRecord;
		
		} else {
		
			if($this->hasSibling()) {
			
				$this->getSibling()->add($pRecord,true);
			
			} else {
			
				$this->setSibling(new ActiveRecordUpdate($pRecord));
			
			}
		
		}
	
	}
	
	public function recordHasChanged(ActiveRecord $pRecord) {
	
		$config = ActiveRecordModelConfig::getModelConfig(get_class($pRecord));
		
		if($config->hasFields()===true) {
		
			foreach($config->getFields() as $field) {
			
				if($pRecord->hasChanged($field)===true) {
				
					return true;
				
				}
			
			}
		
		}
		
		return false;
	
	}
	
	public function isCompatible(ActiveRecord $pRecord) {
	
	
		if($this->compatibleClassName($pRecord)===false) {
		
			return false;
		
		}
		
		if($this->compatibleStructure($pRecord)===false) {
			
			return false;
		
		}
		
		return true;
		
		
	
	}
	
	public function compatibleClassName(ActiveRecord $pRecord) {
		
		$class = get_class($this->record);
		return $pRecord instanceof $class;
	
	}
	
	public function compatibleStructure(ActiveRecord $pRecord) {
	
	
		if($this->compatibleFields($pRecord)===false) {
		
			return false;
		
		}
		
		return true;
	
	}
	
	public function compatibleFields(ActiveRecord $pRecord) {
	
		$config = ActiveRecordModelConfig::getModelConfig(get_class($this->record));	
		
		if($config->hasFields()===true) {
		
			foreach($config->getFields() as $field) {
			
				$changed = false;
				if($this->record->hasChanged($field) && $pRecord->hasChanged($field)) {
					
					$changed = true;
				
				} else if($this->record->hasChanged($field) && !$pRecord->hasChanged($field)) {
				
					return false;
				
				} else if($pRecord->hasChanged($field) && !$this->record->hasChanged($field)) {
				
					return false;
				
				}
				
				if($changed && !($this->record->getProperty($field)==$pRecord->getProperty($field))) {
					
					return false;
				
				}
			
			}
		
		}
		
		return true;
		
	}
	
	public function collectPrimaryKeys() {
	
		$config = ActiveRecordModelConfig::getModelConfig(get_class($this->record));		
		$primaryKey = $config->getPrimaryKey();
		$placeholders = array();
		
		$records = $this->records;
		$records[] = $this->record;
		
		foreach($records as $record) {
		
			$this->data[] = $record->getProperty($primaryKey);
			$placeholders[] = '?';
		
		}
		
		return $placeholders;
	
	}
	
	public function applyFieldTransform(ActiveRecord $pRecord,$pField,$pFieldTransform,$pAllTransform) {
	
		$statement = is_array($pFieldTransform)?$pFieldTransform[0]:$pFieldTransform;
		$transform = is_array($pFieldTransform)?$pFieldTransform:array();
	
		$matches = array();
		preg_match_all('/\$[1-9][0-9]*?|\{.*?\}/',$statement,$matches,PREG_OFFSET_CAPTURE);
		
		if(array_key_exists(0,$matches) && !empty($matches[0])) {
		
			$offset = 0;
			foreach($matches[0] as $match) {
			
				if(strcmp(substr($match[0],0,1),'$')==0) {
					
					$index = (int) substr($match[0],1);
					$index;
					
					if(array_key_exists($index,$pFieldTransform)) {
					
						$this->data[] = $pFieldTransform[$index];
						$statement = substr_replace($statement,'?',($match[1]+$offset),strlen($match[0]));
						$offset-= (strlen($match[0])-1);
					
					}
				
				} else {
				
					$property = substr($match[0],1,(strlen($match[0])-2));
					
					if(strcmp($property,'this')==0) {
						$property = $pField;
					}
					
					if($pRecord->hasProperty($property)===true) {
					
						if(strcmp($pField,$property)!=0 && array_key_exists($property,$pAllTransform) && array_key_exists(self::updateTransform,$pAllTransform[$property])) {
							
							if($pRecord->hasChanged($property)===false) {

								$statement = substr_replace($statement,$pRecord->hasChanged($property)?'?':$property,($match[1]+$offset),strlen($match[0]));
								$offset-= (strlen($match[0])-1);
							
							} else {
								$nestedStatement = $this->applyFieldTransform($pRecord,$property,$pAllTransform[$property][self::updateTransform],$pAllTransform);
								$statement = preg_replace('/\{'.$property.'\}/',$nestedStatement,$statement,1);
								$offset+= (strlen($nestedStatement)-strlen($match[0]));
							}
							
						
						} else {
							
							if($pRecord->hasChanged($property)===true) {
								$this->data[] = $pRecord->getProperty($property);
							}
							
							$statement = substr_replace($statement,$pRecord->hasChanged($property)?'?':$property,($match[1]+$offset),strlen($match[0]));
							$offset-= (strlen($match[0])-1);							
												
						}
					
					}
					
				
				}
			
			}
		
		}
		
		return $statement;
	
	}
	
	public function getRecords() {

		$records = $this->records;
		$records[] = $this->record;
		return $records;	
	
	}

}
?>