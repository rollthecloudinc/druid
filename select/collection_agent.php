<?php
/*
* Class responsible for collecting non hierarchical data into hierarchy
* that mocks the passed node.
*/
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../storage/collection/collection.php');
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../storage/entity/data_entity.php');
class ActiveRecordCollectionAgent {

	private $_primaryKeys;
	private $_records;
	
	private $_fields;
	private $_selectNodes;
	
	public function __construct(ActiveRecordSelectStatement $pSelect) {
	
		$this->_primaryKeys = array();
		//$this->_records = array(); probably a mistake
		$this->_records = new ActiveRecordCollection();
		$this->_selectNodes = $pSelect->getSelectClause()->getNodes();
		$this->_fields = $pSelect->getSelectClause()->getFields();
	
	}
	
	public function getRecords() {
		
		return $this->_records;
		
	}
	
	public function process($pData,ActiveRecordSelectNode $pNode,ActiveRecord $pParent=null,ActiveRecordSelectNode $pParentNode=null) {
	
		$find = $pNode->getFindConfig();
		
		if($find->getInvisible()===true) return;
		
		/*
		* 3/6/10 - Added condition to not ignore dynamic model (select statement) that is the root 
		* 
		* Added: || is_null($pParentNode)
		*/
		if(!($pNode->getConfig() instanceof ActiveRecordDynamicModel) || is_null($pParentNode)) {
			if(is_null($pParent)===true) {
				
				$current = $this->_processRoot($pData,$pNode);
		
			} else {
				
				$current = $this->_processChild($pParent,$pData,$pNode,$pParentNode);
				
			}
		} else {
			$current = $pParent;
		}
		
		
		// current could possibly be false if the record is empty (left join occured)
		if($current && $pNode->hasChild()) {
		
			$this->process($pData,$pNode->getChild(),$current,$pNode);
			
		}
		
		if($pNode->hasSibling()) {
		
			$this->process($pData,$pNode->getSibling(),$pParent,$pParentNode);
			
		}
	
	}
	
	protected function _processRoot($pData,ActiveRecordSelectNode $pNode) {
		
		/*
		* 3/6/10 Edited to handle root level dynamic select statements. Added conditonal if
		* statement to handle differentation in implementation between root dynamic model
		* and true model. 
		*/
		if($pNode->getConfig() instanceof ActiveRecordDynamicModel) {
			$primaryKeyField = 't'.$pNode->getUnique()."_t{$pNode->getConfig()->getSelect()->getNode()->getUnique()}_".$pNode->getConfig()->getSelect()->getNode()->getConfig()->getPrimaryKey();
		} else {
			$primaryKeyField = 't'.$pNode->getUnique().'_'.$pNode->getConfig()->getPrimaryKey();
		}
		
		$primaryKey = $pData[$primaryKeyField];
		$index = array_search($primaryKey,$this->_primaryKeys);
	
		if($index === false) {
			
			$entity = new ActiveRecordDataEntity();
			$className = $pNode->getConfig()->getClassName();
			
			/*
			* 3/6/10 Added conditional to use base node for select where the root
			* node is a subquery. 
			*/
			if($pNode->getConfig() instanceof ActiveRecordDynamicModel) {
				$className = $pNode->getConfig()->getSelect()->getNode()->getConfig()->getClassName();
			} else {
				$className = $pNode->getConfig()->getClassName();
			}
				
			$record = new $className($entity);			
			$this->_loadData($entity,$pData,$pNode);
			
			$this->_primaryKeys[] = $primaryKey;
			$this->_records[] = $record;
			
			return $record;
		
		} else {
		
			return $this->_records[$index];
		
		}
	
	
	}
	
	protected function _processChild(IActiveRecordDataEntity $pParent,$pData,ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParentNode) {
	
		$primaryKeyField = 't'.$pNode->getUnique().'_'.$pNode->getConfig()->getPrimaryKey();
		$primaryKey = $pData[$primaryKeyField];
		
		$oneOrMany = $this->_oneOrMany($pNode,$pParentNode);
		$propertyName = $this->_determineChildPropertyName($oneOrMany,$pNode,$pParentNode);
		
		if(is_null($primaryKey)===true) {
			if($pParent->hasProperty($propertyName)===true) return false;
			$pParent->getData()->setProperty($propertyName,strcmp($oneOrMany,'one')==0?null:new ActiveRecordCollection());
			return false;
		}
		
		$record = $pParent->getRecord($propertyName,$primaryKey,$pNode->getConfig()->getPrimaryKey());
	
		if($record===false) {
		
			$entity = new ActiveRecordDataEntity();
			$className = $pNode->getConfig()->getClassName();
			$record = new $className($entity);
			
			$this->_loadData($entity,$pData,$pNode);
			
			$pParent->addRecord($propertyName,$record,strcmp('one',$oneOrMany)==0?false:true);
			
			return $record;
		
		} else {
		
			return $record;
		
		}
	
	}
	
	protected function _loadData(IActiveRecordDataEntity $pRecord,$pData,ActiveRecordSelectNode $pNode) {
	
		$index = array_search($pNode,$this->_selectNodes);
		
		/*
		* No data was selected on that node
		*/
		if($index===false) return;
		
		$fields = $this->_fields[$index];
		
		//$deselected = $pNode->getFindConfig()->hasNonSelect()===true?$pNode->getFindConfig()->getNonSelect():array();
		//$config = $pNode->getConfig()->getFields();
		
		$unique = $pNode->getUnique();
		
		foreach($fields as $fieldName=>$select) {
						
			$pRecord->setProperty($fieldName,$pData['t'.$unique.'_'.$fieldName]);
		
		}
	
	}
	
	protected function _determineChildPropertyName($pOneOrMany,ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParent=null) {
		
		$tableName = Inflector::tableize($pNode->getConfig()->getClassName());
		
		// override default model name
		if(!is_null($pParent) && $pParent->getConfig() instanceof ActiveRecordDynamicModel && $pParent->getFindConfig()->hasAssociationPropertyName()===true) {
		
			return $pParent->getFindConfig()->getAssociationPropertyName();
			
		} else if($pNode->getFindConfig()->hasAssociationPropertyName()===true) {
		
			return $pNode->getFindConfig()->getAssociationPropertyName();
			
		}
		
		switch($pOneOrMany) {
		
			case 'many':
				return Inflector::pluralize($tableName);
			
			case 'one':
			default:
				return Inflector::singularize($tableName);
				
		
		}
	
	}
	
	protected function _oneOrMany(ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParentNode) {
		
		// override default property type
		if(!is_null($pParentNode) && $pParentNode->getConfig() instanceof ActiveRecordDynamicModel && $pParentNode->getFindConfig()->hasAssociationPropertyType()===true) {
			
			return $pParentNode->getFindConfig()->getAssociationPropertyType();
		
		} else if($pNode->getFindConfig()->hasAssociationPropertyType()===true) {
			
			return $pNode->getFindConfig()->getAssociationPropertyType();
		
		}
		
		// singular or plural
		
		// sorta dirty considering we only really need to do this once per model
		
		$parentConfig = $pParentNode->getConfig();
		$childConfig = $pNode->getConfig();
		
		$childTable = Inflector::tableize($childConfig->getClassName());
		$childModelSingular = Inflector::singularize($childTable);
		$childModelPlural= Inflector::pluralize($childTable);
		
		if($parentConfig->hasBelongsTo()) {
			if(in_array($childModelSingular,$parentConfig->getBelongsTo())) return 'one';
		}
		
		
		if($parentConfig->hasOne()) {
			if(in_array($childModelSingular,$parentConfig->getHasOne())) return 'one';
		}
		
		
		if($parentConfig->hasMany()) {
			if(in_array($childModelPlural,$parentConfig->getHasMany())) return 'many';
		}
		
		return 'many';	
	
	}

}
?>