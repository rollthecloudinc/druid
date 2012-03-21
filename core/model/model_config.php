<?php
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/model_config.php');

/*
* Class is a storage facility for all configuration information about a model.
*/
class ActiveRecordModelConfig implements IActiveRecordModelConfig {

	protected $_className;
	protected $_table;
	protected $_fields;
	protected $_primaryKey;
	protected $_uniqueKeys;
	protected $_foreignKeys;
	protected $_validation;
	protected $_transformations;
	protected $_dataTypes;
	protected $_requiredFields;
	protected $_defaultValues;
	protected $_defaultFilter;
	protected $_cascadeDelete;
	protected $_links;
	protected $_hasOne;
	protected $_hasMany;
	protected $_belongsTo;
	protected $_belongsToAndHasMany;
	
	protected static $_resolvedAssociations = array();
	protected static $_resolvedAssociationTypes = array();
	
	protected static $_configured = array();

	public function __construct($pClassName=null) {
	
		if(!is_null($pClassName)) { 
		
			if(class_exists($pClassName)===true) {
				$this->setClassName($pClassName);		
				$this->_init();
			} else {
				throw new Exception('Model '.$pClassName.' not found. Exception thrown in class '.__CLASS__.' on line '.__LINE__);
			}
		
			if(!array_key_exists($pClassName,self::$_configured)) {
		
				self::$_configured[$pClassName] = $this;
		
			}
		
		}
		
	}
	
	protected function _init() {
		
		$classAttr = get_class_vars($this->getClassName());
		
		$this->setTable(array_key_exists(IActiveRecordModelConfig::table,$classAttr)?$classAttr[IActiveRecordModelConfig::table]:Inflector::tableize($this->getClassName()));
		
		$this->setFields(array_key_exists(IActiveRecordModelConfig::fields,$classAttr)?$classAttr[IActiveRecordModelConfig::fields]:array());
		
		$this->setPrimaryKey(array_key_exists(IActiveRecordModelConfig::primaryKey,$classAttr)?$classAttr[IActiveRecordModelConfig::primaryKey]:IActiveRecordModelConfig::defaultPrimaryKeyName);
		
		$this->setUniqueKeys(array_key_exists(IActiveRecordModelConfig::uniqueKeys,$classAttr)?$classAttr[IActiveRecordModelConfig::uniqueKeys]:array());
		
		$this->setForeignKeys(array_key_exists(IActiveRecordModelConfig::foreignKeys,$classAttr)?$classAttr[IActiveRecordModelConfig::foreignKeys]:array());
		
		$this->setValidation(array_key_exists(IActiveRecordModelConfig::validation,$classAttr)?$classAttr[IActiveRecordModelConfig::validation]:array());
		
		$this->setTransformations(array_key_exists(IActiveRecordModelConfig::transformations,$classAttr)?$classAttr[IActiveRecordModelConfig::transformations]:array());
			
		$this->setDataTypes(array_key_exists(IActiveRecordModelConfig::dataTypes,$classAttr)?$classAttr[IActiveRecordModelConfig::dataTypes]:array());
			
		$this->setRequiredFields(array_key_exists(IActiveRecordModelConfig::requiredFields,$classAttr)?$classAttr[IActiveRecordModelConfig::requiredFields]:array());
		
		$this->setDefaultValues(array_key_exists(IActiveRecordModelConfig::defaultValues,$classAttr)?$classAttr[IActiveRecordModelConfig::defaultValues]:array());
		
		$this->setDefaultFilter(array_key_exists(IActiveRecordModelConfig::defaultFilter,$classAttr)?$classAttr[IActiveRecordModelConfig::defaultFilter]:array());
		
		$this->setCascadeDelete(array_key_exists(IActiveRecordModelConfig::cascadeDelete,$classAttr)?$classAttr[IActiveRecordModelConfig::cascadeDelete]:array());
		
		$this->setLinks(array_key_exists(IActiveRecordModelConfig::links,$classAttr)?$classAttr[IActiveRecordModelConfig::links]:array());
		
		$this->setHasOne(array_key_exists(IActiveRecordModelConfig::hasOne,$classAttr)?$classAttr[IActiveRecordModelConfig::hasOne]:array());
		
		$this->setHasMany(array_key_exists(IActiveRecordModelConfig::hasMany,$classAttr)?$classAttr[IActiveRecordModelConfig::hasMany]:array());

		$this->setBelongsTo(array_key_exists(IActiveRecordModelConfig::belongsTo,$classAttr)?$classAttr[IActiveRecordModelConfig::belongsTo]:array());
			
		$this->setBelongsToAndHasMany(array_key_exists(IActiveRecordModelConfig::belongsToAndHasMany,$classAttr)?$classAttr[IActiveRecordModelConfig::belongsToAndHasMany]:array());
		
	}
	
	// setters
	
	public function setClassName($pClassName) {
	
		$this->_className = $pClassName;
	
	}
	
	public function setTable($pTable) {
	
		$this->_table = $pTable;
	
	}
	
	public function setFields($pFields) {
	
		$this->_fields = is_array($pFields)?$pFields:array($pFields);
	
	}
	
	public function setPrimaryKey($pPrimaryKey) {
		
		$this->_primaryKey = $pPrimaryKey;
		
	}
	
	public function setUniqueKeys($pUniqueKeys) {
		
		$this->_uniqueKeys = is_array($pUniqueKeys)?$pUniqueKeys:array($pUniqueKeys);
		
	}
	
	public function setForeignKeys($pForeignKeys) {
	
		$this->_foreignKeys = $pForeignKeys;
	
	}
	
	public function setValidation($pValidation) {
	
		$this->_validation = $pValidation;
		
	}
	
	public function setTransformations($pTransformations) {
	
		$this->_transformations = $pTransformations;
	
	}
	
	public function setDataTypes($pDataTypes) {
	
		$this->_dataTypes = $pDataTypes;
	
	}
	
	public function setRequiredFields($pRequiredFields) {
	
		$this->_requiredFields = $pRequiredFields;
	
	}
	
	public function setDefaultFilter($pDefaultFilter) {
	
		$this->_defaultFilter = $pDefaultFilter;
	
	}
	
	public function setDefaultValues($pDefaultValues) {
	
		$this->_defaultValues = $pDefaultValues;
	
	}
	
	public function setCascadeDelete($pCascadeDelete) {
	
		$this->_cascadeDelete = $pCascadeDelete;
	
	}
	
	public function setLinks($pLinks) {
	
		$this->_links = $pLinks;
	
	}
	
	public function setHasOne($pHasOne) {
	
		$this->_hasOne = is_array($pHasOne)?$pHasOne:array($pHasOne);
	
	}
	
	public function setHasMany($pHasMany) {
	
		$this->_hasMany = is_array($pHasMany)?$pHasMany:array($pHasMany);
	
	}
	
	public function setBelongsTo($pBelongsTo) {
	
		$this->_belongsTo = is_array($pBelongsTo)?$pBelongsTo:array($pBelongsTo);
	
	}
	
	public function setBelongsToAndHasMany($pBelongsToAndHasMany) {
	
		$this->_belongsToAndHasMany = is_array($pBelongsToAndHasMany)?$pBelongsToAndHasMany:array($pBelongsToAndHasMany);
	
	}
	
	// getters
	
	public function getClassName() {
		return $this->_className;
	}
	
	public function getTable() {
		return $this->_table;
	}
	
	public function getFields() {
		return $this->_fields;
	}
	
	public function getPrimaryKey() {
		return $this->_primaryKey;
	}
	
	public function getUniqueKeys() {
		return $this->_uniqueKeys;
	}
	
	public function getForeignKeys() {
		return $this->_foreignKeys;
	}
	
	public function getValidation() {
		return $this->_validation;
	}
	
	public function getTransformations() {
		return $this->_transformations;
	}
	
	public function getDataTypes() {
		return $this->_dataTypes;
	}
	
	public function getRequiredFields() {
		return $this->_requiredFields;
	}
	
	public function getDefaultValues() {
		return $this->_defaultValues;
	}
	
	public function getDefaultFilter() {
		return $this->_defaultFilter;
	}
	
	public function getCascadeDelete() {
		return $this->_cascadeDelete;
	}
	
	public function getLinks() {
		return $this->_links;
	}
	
	public function getHasOne() {
		return $this->_hasOne;
	}
	
	public function getHasMany() {
		return $this->_hasMany;
	}
	
	public function getBelongsTo() {
		return $this->_belongsTo;
	}
	
	public function getBelongsToAndHasMany() {
		return $this->_belongsToAndHasMany;
	}
	
	/*
	* has methods are conveniences for determining if the information exists.
	*/
	
	// config will always have a className
	public function hasClassName() {
		return $this->_className==''?false:true;
	}
	
	// config is always going to have a table
	public function hasTable() {
		return $this->_table==''?false:true;
	}
	
	public function hasFields() {
		return empty($this->_fields)?false:true;
	}
	
	public function hasPrimaryKey() {
		return $this->_primaryKey==''?false:true;
	}	
	
	public function hasUniqueKeys() {
		return empty($this->_uniqueKeys)?false:true;
	}
	
	public function hasForeignKeys() {
		return empty($this->_foreignKeys)?false:true;
	}
	
	public function hasValidation() {
		return empty($this->_validation)?false:true;
	}
	
	public function hasTransformations() {
		return empty($this->_transformations)?false:true;
	}
	
	public function hasDataTypes() {
		return empty($this->_dataTypes)?false:true;
	}
	
	public function hasRequiredFields() {
		return empty($this->_requiredFields)?false:true;
	}
	
	public function hasDefaultValues() {
		return empty($this->_defaultValues)?false:true;
	}
	
	public function hasDefaultFilter() {
		return empty($this->_defaultFilter)?false:true;
	}
	
	public function hasCascadeDelete() {
		return empty($this->_cascadeDelete)?false:true;
	}
	
	public function hasLinks() {
		return empty($this->_links)?false:true;
	}
	
	public function hasOne() {
		return empty($this->_hasOne)?false:true;
	}
	
	public function hasMany() {
		return empty($this->_hasMany)?false:true;
	}
	
	public function hasBelongsTo() {
		return empty($this->_belongsTo)?false:true;
	}
	
	public function hasBelongsToAndHasMany() {
		return empty($this->_belongsToAndHasMany)?false:true;
	}
	
	public function getRelatedField(IActiveRecordModelConfig $pConfig) {
	
		if(array_key_exists($this->getClassName(),self::$_resolvedAssociations)===false) {
			
			self::$_resolvedAssociations[$this->getClassName()] = array();
			
		}
		
		$className 					= 		$pConfig->getClassName();
		
		if(array_key_exists($className,self::$_resolvedAssociations[$this->getClassName()])===true) {
			
			return self::$_resolvedAssociations[$this->getClassName()][$className];
			
		}	
	
		// pass node instad so that this method can add to it for a many to many
	
		$singularClassName 			= 		Inflector::underscore(Inflector::singularize($className));
		$pluralClassName 			= 		Inflector::underscore(Inflector::pluralize($className));
		$classPrimaryKey			=		$pConfig->getPrimaryKey();
		
		// foreign keys are most specific so lets look there first
		if($this->hasForeignKeys()) {
			
			foreach($this->getForeignKeys() as $index=>$reference) {
			
				$referenceModel = is_array($reference) && !empty($reference)?array_shift($reference):$reference;
				
				if(strcasecmp($referenceModel,$className)==0) {
					self::$_resolvedAssociations[$this->getClassName()][$className] = $index;
					self::$_resolvedAssociationTypes[$this->getClassName()][$className] = 'one';
					return self::$_resolvedAssociations[$this->getClassName()][$className];
				
				}
			
			}
			
		}
		
		// look in belongsTo array
		if($this->hasBelongsTo()) {
			
			foreach($this->getBelongsTo() as $model) {
				
				if(strcmp($model,$singularClassName)==0) {
				
					self::$_resolvedAssociations[$this->getClassName()][$className] = Inflector::foreign_key($singularClassName,true,$classPrimaryKey);
					self::$_resolvedAssociationTypes[$this->getClassName()][$className] = 'one';
					return self::$_resolvedAssociations[$this->getClassName()][$className];
				
				}
			
			}
			
		}
		
		// look to has one
		
		if($this->hasOne()) {
		
			foreach($this->getHasOne() as $model) {
			
				if(strcmp($model,$singularClassName)==0) {
				
					self::$_resolvedAssociations[$this->getClassName()][$className] = $this->hasPrimaryKey()?$this->getPrimaryKey():IActiveRecordModelConfig::defaultPrimaryKeyName;
					self::$_resolvedAssociationTypes[$this->getClassName()][$className] = 'one';
					return 	self::$_resolvedAssociations[$this->getClassName()][$className]; 
				
				}
			
			}
		
		}
		
		// look to has many
		
		if($this->hasMany()) {
		
			foreach($this->getHasMany() as $model) {
				
				if(strcmp($model,$pluralClassName)==0) {
				
					self::$_resolvedAssociations[$this->getClassName()][$className] = $this->hasPrimaryKey()?$this->getPrimaryKey():IActiveRecordModelConfig::defaultPrimaryKeyName;	
					self::$_resolvedAssociationTypes[$this->getClassName()][$className] = 'many';
					return self::$_resolvedAssociations[$this->getClassName()][$className]; 
				
				}
			
			}
		
		}
		
		if($this->hasBelongsToAndHasMany()) {
		
			foreach($this->getBelongsToAndHasMany() as $model) {
				
				if(is_array($model) && !empty($model) && strcmp($model[1],$pluralClassName)==0) {				
					
					self::$_resolvedAssociations[$this->getClassName()][$className] = $this->hasPrimaryKey()?$this->getPrimaryKey():IActiveRecordModelConfig::defaultPrimaryKeyName;
					self::$_resolvedAssociationTypes[$this->getClassName()][$className] = 'many';
					return self::$_resolvedAssociations[$this->getClassName()][$className];
				
				}
			
			}
		
		}
		
		// reverse foreign key search
		if($pConfig->hasForeignKeys()) {
		
			foreach($pConfig->getForeignKeys() as $index=>$reference) {
			
				$referenceModel = is_array($reference) && !empty($reference)?array_shift($reference):$reference;
				$primaryKey = is_array($reference) && !empty($reference)?array_shift($reference):$this->getPrimaryKey();
				
				if(strcmp($referenceModel,$this->getClassName())==0) {
				
					self::$_resolvedAssociations[$this->getClassName()][$className] = $primaryKey;
					self::$_resolvedAssociationTypes[$this->getClassName()][$className] = 'many';
					return self::$_resolvedAssociations[$this->getClassName()][$className];
				
				}
			
			}
			
		}
		
		$mySingularName = Inflector::singularize(Inflector::underscore($this->getClassName()));
		
		// reverse belongsto search
		if($pConfig->hasBelongsTo()) {
			
			foreach($pConfig->getBelongsTo() as $model) {

				if(strcmp($model,$mySingularName)==0) {
				
					self::$_resolvedAssociations[$this->getClassName()][$className] = $this->getPrimaryKey();
					self::$_resolvedAssociationTypes[$this->getClassName()][$className] = 'many';
					return self::$_resolvedAssociations[$this->getClassName()][$className];
				
				}			
				
			
			}
			
		}		
		
		return '';
	
	}
	
	public function getRelatedType(IActiveRecordModelConfig $pConfig) {
			
		$this->getRelatedField($pConfig);
		
		$className = $pConfig->getClassName();
		return array_key_exists($className,self::$_resolvedAssociationTypes[$this->getClassName()])?self::$_resolvedAssociationTypes[$this->getClassName()][$className]:'many';
	
	}
	
	public static function getModelConfig($pClassName) {
		
		/*
		* Select statement can be read into a model at runtime. 
		*/
		if($pClassName instanceof ActiveRecordSelectStatement) {
			return new ActiveRecordDynamicModel($pClassName);
		}
	
		return array_key_exists($pClassName,self::$_configured)===true?self::$_configured[$pClassName]:new ActiveRecordModelConfig($pClassName);
	
	}

}
?>