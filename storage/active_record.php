<?php
$d = str_replace('//','/',dirname(__FILE__).'/');
require_once($d.'entity/data_entity.php');
require_once($d.'collection/collection.php');
require_once($d.'../core/inflector/inflector.php');
require_once($d.'../select/select_node.php');
require_once($d.'../select/count_statement.php');
require_once($d.'../select/collection_agent.php');
require_once($d.'../save/save.php');
require_once($d.'../cascade/cascade.php');
require_once($d.'../cascade/cascade_node.php');
require_once($d.'../cascade/action/destroy.php');
require_once($d.'../cascade/action/deactivate.php');
require_once($d.'../core/validation/validation.php');
abstract class ActiveRecord implements IActiveRecordDataEntity,arrayaccess,ActiveRecordSavable,ActiveRecordDestroyable,IActiveRecordXML,ActiveRecordDumpable {

	const findCount 	= 	'count';
	const findAll	 	=	'all';
	const findOne		=	'one';
	const findSelect	=	'subquery';
	
	const findRelatedPrefix = 'get';
	const countRelatedPrefix = 'count';
	const addRelatedPrefix = 'add';

	private $_data;	
	private $_changed;
	
	private static $_db;
	private static $_validation;
	
	public function __construct() {
	
		$this->_data = new ActiveRecordDataEntity();
		$this->_changed = array();
		
		if(self::isConnected()===false) throw new Exception('A database Adaptor has not been set for the '.__CLASS__.' Class.');
		
		$args = func_get_args();
		
		if(!empty($args)) {
		
			$this->_init($args);
			
		}
	
	}
	
	protected function _init($pArgs) {
	
		if($pArgs[0] instanceof IActiveRecordDataEntity) {
	
			$this->_data = $pArgs[0];
	
		} else if(is_array($pArgs[0])===true) {
		
			$this->_initInactive($pArgs);
		
		} else if(count($pArgs)>1) {
		
			$str = '';	
			foreach($pArgs as $arg) {
				$pos = strpos($arg,':');
				if($pos!==false) $str.= '\''.substr($arg,0,$pos).'\'=>'.substr($arg,($pos+1)).',';
			}
	
			eval('$properties = array('.rtrim($str,',').');');
			$this->_initInActive(array($properties));	
		
		} else {	
		
			$this->_initActive($pArgs);
		
		}
	
	}
	
	protected function _initInactive($pArgs) {
		
		foreach($pArgs[0] as $property=>$value) {
			
			//not certain but this might break something
			//$this->_data->setProperty($property,$value);
			$this->setProperty($property,$value);
		
		}
		
		if(array_key_exists(1,$pArgs) && is_bool($pArgs[1] && $pArgs[1]===true)) {
		
			$this->save();
		
		}
	
	}
	
	protected function _initActive($pArgs) {
	
		$className = get_class($this);
		$model = ActiveRecordModelConfig::getModelConfig($className);
		
		if($model->hasPrimaryKey()===false) {
			throw new Exception('A model must have a primary key static property specified as a string to be initialized as a ActiveRecord. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__);
		}
		
		$primaryKeyField = $model->getPrimaryKey();
		
		$record = self::_find($className,array(self::findOne,array($primaryKeyField=>$pArgs[0])));
		
		if(is_null($record)===true) {
			
			throw new Exception('A '.$model->getClassName().' with a primary key of '.$pArgs[0].' could not be located. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__);
			
		} else {
		
			$this->_data = $record->getData();
			unset($record);
		
		}
	
	}
	
	public function offsetSet($offset,$value) {
	
		$this->setProperty($offset,$value);
	
	}
	
	public function offsetExists($offset) {
	
		return $this->hasProperty($offset);
	
	}
	
	public function offsetUnset($offset) {
	
		$this->removeProperty($offset);
	
	}
	
	public function offsetGet($offset) {
	
		return $this->hasProperty($offset) ? $this->getProperty($offset) : null;
	
	}
	
	public function getIterator() {
		return $this->_data->getIterator();
	}

	public function __set($pName,$pValue) {
	
		$this->setProperty($pName,$pValue);
	
	}
	
	public function __get($pName) {
	
		return $this->getProperty($pName);
	
	}
	
	public function setProperty($pName,$pValue) {
		
		if(!in_array($pName,$this->_changed)) $this->_changed[] = $pName;
		
		$this->_data->setProperty($pName,$pValue);
	
	}
	
	public function getProperty($pName) {
	
		if($this->_data->hasProperty($pName)===true) {
		
			return $this->_data->getProperty($pName);
		
		} else {
		
			return $this->load($pName);
		
		}
	
	}
	
	public function hasProperty($pName) {
	
		return $this->_data->hasProperty($pName);
	
	}
	
	public function removeProperty($pName) {
	
		return $this->_data->removeProperty($pName);
	
	}
	
	public function getRecord($pPropertyName,$pPrimaryKey,$pField) {
	
		return $this->_data->getRecord($pPropertyName,$pPrimaryKey,$pField);
	
	}
	
	public function addRecord($pPropertyName,IActiveRecordDataEntity $pRecord,$pArrayByDefault=false) {
	
		$this->_data->addRecord($pPropertyName,$pRecord,$pArrayByDefault);
	
	}
	
	public function getData() {
	
		return $this->_data;
	
	}
	
	public function cast() {
		
		$this->_changed = array();
		$this->_data->cast();
	
	}
	
	public function hasChanged($pName) {
	
		//return $this->_data->hasChanged($pName);		
		return in_array($pName,$this->_changed)?true:false;
	
	}
	
	public function __call($pName,$pArgs) {
	
		if(preg_match('/^'.self::findRelatedPrefix.'/',$pName)) {
			
			$mode = self::findRelatedPrefix;
			$propertyName = substr($pName,strlen($mode));			
			$relatedClassName = Inflector::classify($propertyName);
			
			$modelConfig = ActiveRecordModelConfig::getModelConfig(get_class($this));
			$relatedModelConfig =  ActiveRecordModelConfig::getModelConfig($relatedClassName);
			
			$modelField = $modelConfig->getRelatedField($relatedModelConfig);
			$relatedModelField = $relatedModelConfig->getRelatedField($modelConfig);
			
			if(empty($modelField) || empty($relatedModelField)) {
				
				// voodoo to make this work
				if($modelConfig->hasBelongsToAndHasMany()) {
					foreach($modelConfig->getBelongsToAndHasMany() as $index=>$reference) {
						
						$class = Inflector::classify($reference[0]);
						if(strcmp($class,$relatedModelConfig->getClassName())==0) {
							$manyToMany = true;
							// 'views' array('include'=>'viewazations'),array('controler_id'=1)
							$class = Inflector::classify($reference[1]);
							$tModel = ActiveRecordModelConfig::getModelConfig($class);
							$relatedField = $modelConfig->getRelatedField($tModel);
							$tRelatedField = $tModel->getRelatedField($modelConfig);
							$pArgs = empty($pArgs)===true?array(array()):$pArgs;
							$pArgs[0][IActiveRecordFindConfig::findInclude] = $reference[1];
							$invisible = IActiveRecordFindConfig::findInvisible;
							$pArgs[1] = array($tRelatedField=>$this->getProperty($relatedField),$invisible=>true); 
							return self::_find($relatedModelConfig->getClassName(),$pArgs);
						}
						
					}
				}
				
				throw new Exception('Relationship between '.get_class($this).' and '.$relatedClassName.' could not be resolved. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.' for '.$pName.'()');
			
			}
			
			$pArgs = empty($pArgs)===true?array(array()):$pArgs;			
			array_unshift($pArgs,$modelConfig->getRelatedType($relatedModelConfig));
			$pArgs[1][$relatedModelField] = $this->$modelField;
			
			return self::_find($relatedClassName,$pArgs);
		
		} else if(preg_match('/^'.self::countRelatedPrefix.'/',$pName)) {
			
			$mode = self::countRelatedPrefix;
			$relatedClassName = Inflector::classify(substr($pName,strlen($mode)));
			
			$modelConfig = ActiveRecordModelConfig::getModelConfig(get_class($this));
			$relatedModelConfig =  ActiveRecordModelConfig::getModelConfig($relatedClassName);
			
			$modelField = $modelConfig->getRelatedField($relatedModelConfig);
			$relatedModelField = $relatedModelConfig->getRelatedField($modelConfig);
			
			if(empty($modelField) || empty($relatedModelField)) {
			
				// voodoo to make this work
				if($modelConfig->hasBelongsToAndHasMany()) {
					foreach($modelConfig->getBelongsToAndHasMany() as $index=>$reference) {
						
						$class = Inflector::classify($reference[0]);
						if(strcmp($class,$relatedModelConfig->getClassName())==0) {
							$manyToMany = true;
							// 'views' array('include'=>'viewazations'),array('controler_id'=1)
							$class = Inflector::classify($reference[1]);
							$tModel = ActiveRecordModelConfig::getModelConfig($class);
							$relatedField = $modelConfig->getRelatedField($tModel);
							$tRelatedField = $tModel->getRelatedField($modelConfig);
							$pArgs = empty($pArgs)===true?array(array()):$pArgs;
							$pArgs[0][IActiveRecordFindConfig::findInclude] = $reference[1];
							$invisible = IActiveRecordFindConfig::findInvisible;
							$pArgs[1] = array($tRelatedField=>$this->getProperty($relatedField),$invisible=>true); 
							array_unshift($pArgs,$mode);
							return self::_find($relatedModelConfig->getClassName(),$pArgs);
						}
						
					}
				}
			
				throw new Exception('Relationship between '.get_class($this).' and '.$relatedClassName.' could not be resolved. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.' for '.$pName.'()');
			
			}
			
			$pArgs = empty($pArgs)===true?array(array()):$pArgs;
			$pArgs[0][$relatedModelField] = $this->$modelField;
			array_unshift($pArgs,$mode);
			
			return self::_find($relatedClassName,$pArgs);
		
		} else if(preg_match('/^'.self::addRelatedPrefix.'/',$pName)) {
			
			$mode = self::addRelatedPrefix;
			$relatedClassName = Inflector::classify(substr($pName,strlen($mode)));
			
			$modelConfig = ActiveRecordModelConfig::getModelConfig(get_class($this));
			
			if(empty($pArgs)===true) {
			
				throw new Exception('The dynamic '.self::addRelatedPrefix.' method for instance of class '.$modelConfig->getClassName().' requires at least one argument. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__);
			
			}
			
			$relatedModelConfig =  ActiveRecordModelConfig::getModelConfig($relatedClassName);			
			$modelField = $modelConfig->getRelatedField($relatedModelConfig);
			$relatedModelField = $relatedModelConfig->getRelatedField($modelConfig);
			
			if(empty($modelField) || empty($relatedModelField)) {
			
				$manyToMany = false;
			
				// voodoo to make this work
				if($modelConfig->hasBelongsToAndHasMany()) {
					foreach($modelConfig->getBelongsToAndHasMany() as $index=>$reference) {
						
						$class = Inflector::classify($reference[0]);
						if(strcmp($class,$relatedModelConfig->getClassName())==0) {
							$manyToMany = true;
							break;
						}
						
					}
				}
				
				if($manyToMany===false) {
				
					throw new Exception('Relationship between '.get_class($this).' and '.$relatedClassName.' could not be resolved. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.' for '.$pName.'()');
				
				}
			
			}
			
			$property = Inflector::pluralize(Inflector::underscore($relatedClassName));
			$invalid = array();
			foreach($pArgs as $key=>$arg) {
			
				if(!$arg instanceof $relatedClassName) {
				
					$invalid[] = $key;
				
				}
			
			}
			
			if(!empty($invalid)) {
			
				throw new Exception('Dynamic method '.$pName.' only accepts objects of type '.$relatedClassName.' as arguments. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.'.');
			
			}
			
			foreach($pArgs as $arg) {
			
				/*if($arg->hasProperty($relatedModelField)===false) {
					$arg->setProperty($relatedModelField,$clone);
				}*/
			
				$this->addRecord($property,$arg,true);
			
			}
			
			return $this;
				
		} else {
		
		}
	
	}
	
	// lazy load mechanism
	public function load($pPropertyName) {
		
		$config = ActiveRecordModelConfig::getModelConfig(get_class($this));
		
		try {
			
			$relatedConfig = ActiveRecordModelConfig::getModelConfig(Inflector::classify($pPropertyName));			
			//$relatedField = $config->getRelatedField($relatedConfig);
			
			$method = self::findRelatedPrefix.ucfirst(Inflector::camelize($pPropertyName));
			$record = $this->$method();
			$this->setProperty($pPropertyName,$record);
			return $this->getProperty($pPropertyName);
		
		} catch(Exception $e) {
		
			return null;
		
		}
	
	}
	
	public function destroy() {
		
		$config = ActiveRecordModelConfig::getModelConfig(get_class($this));
		$node = new ActiveRecordCascadeNode($config);
		$node->addRecord($this);
		
		try {
		
			$delete = new ActiveRecordDestroy();
			$cascade = new ActiveRecordCascade($delete);
			$cascade->cascade($node);
		
		} catch(Exception $e) {
			
			throw new Exception('Error initializing delete. Exception caught and rethrown from line '.__LINE__.' in class '.__CLASS__.' inside method '.__METHOD__.': '.$e->getMessage());
			return false;
		
		}
		
		try {
		
			if($delete->query(self::getConnection())===true) {
			
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
	
	public function save($pValidate=true) {
	
		$save = new ActiveRecordSave($this);		
		return $save->query(self::$_db);
	
	}
	
	protected static function _find($pClassName,$pOptions) {
		
		if(self::isConnected()===false) throw new Exception('A database Adaptor has not been set for the '.__CLASS__.' Class.');
	
		$model = ActiveRecordModelConfig::getModelConfig($pClassName);
		
		$mode = !empty($pOptions) && is_array($pOptions[0])===false?array_shift($pOptions):self::findAll;
		
		$node = new ActiveRecordSelectNode($model,new ActiveRecordFindConfig(!empty($pOptions)?$pOptions[0]:array()));
		$select = strcasecmp($mode,self::findCount)==0?new ActiveRecordCountStatement($node,$pOptions):new ActiveRecordSelectStatement($node,$pOptions);
		
		if(strcmp($mode,self::findSelect)==0) return $select; // return without reseting count
		
		// echo '<p>',$select->toSql(),'</p>';
		// echo '<pre>',print_r($select->getBindData()),'</pre>';
		//return;
		
		$stmt = $select->query(self::$_db);
		ActiveRecordSelectNode::resetCount();
		$collectionAgent = new ActiveRecordCollectionAgent($select);
		
		if(strcmp($mode,self::findCount)==0) { return $stmt->fetchColumn(); }

		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$collectionAgent->process($row,$node);
		}

		$records = $collectionAgent->getRecords();
		
		return strcmp($mode,self::findOne)==0?count($records)!=0?$records[0]:null:$records;
		
	}
	
	public function dump() {
	
		echo '<pre>',print_r($this),'</pre>';
		
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
	
	public static function isConnected() {
		return is_null(self::$_db)?false:true;
	}
	
	public static function setConnection(PDO $pDb) {
		self::$_db = $pDb;
	}
	
	public static function getConnection() {
		return self::$_db;
	}
	
	public static function setValidation(IActiveRecordValidation $pValidation) {
		self::$_validation = $pValidation;
	}

	abstract public static function find();
}
?>