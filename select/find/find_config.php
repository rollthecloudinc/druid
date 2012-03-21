<?php
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/find_config.php');
class ActiveRecordFindConfig implements IActiveRecordFindConfig {
	
	protected 
	
	 $_include
	,$_limit
	,$_offset
	,$_select
	,$_nonSelect
	,$_dynamic
	,$_condition
	,$_conditionMap
	,$_filter
	,$_group
	,$_sort
	,$_having
	,$_joinType
	,$_requireJoin
	,$_magicalFilter
	,$_invisible
	,$_empty
	,$_ignoreModelFilter
	,$_association
	,$_associationPropertyName
	,$_associationPropertyType
	,$_count;

	public function __construct($pOptions) {
	
		$this->_init($pOptions);
	
	}
	
	protected function _init($pOptions) {
	
		if(array_key_exists(IActiveRecordFindConfig::findInclude,$pOptions)) {
			$this->setInclude($pOptions[IActiveRecordFindConfig::findInclude]);
			unset($pOptions[IActiveRecordFindConfig::findInclude]);
		}
			
		if(array_key_exists(IActiveRecordFindConfig::findLimit,$pOptions)) {
			$this->setLimit($pOptions[IActiveRecordFindConfig::findLimit]);
			unset($pOptions[IActiveRecordFindConfig::findLimit]);
		}
			
		if(array_key_exists(IActiveRecordFindConfig::findOffset,$pOptions)) {
			$this->setOffset($pOptions[IActiveRecordFindConfig::findOffset]);
			unset($pOptions[IActiveRecordFindConfig::findOffset]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findSelect,$pOptions)) {
			$this->setSelect($pOptions[IActiveRecordFindConfig::findSelect]);
			unset($pOptions[IActiveRecordFindConfig::findSelect]);
		}
			
		if(array_key_exists(IActiveRecordFindConfig::findNonSelect,$pOptions)) {
			$this->setNonSelect($pOptions[IActiveRecordFindConfig::findNonSelect]);
			unset($pOptions[IActiveRecordFindConfig::findNonSelect]);
		}
			
		if(array_key_exists(IActiveRecordFindConfig::findDynamic,$pOptions)) {
			$this->setDynamic($pOptions[IActiveRecordFindConfig::findDynamic]);
			unset($pOptions[IActiveRecordFindConfig::findDynamic]);
		}
			
		if(array_key_exists(IActiveRecordFindConfig::findCondition,$pOptions)) {
			$this->setCondition($pOptions[IActiveRecordFindConfig::findCondition]);
			unset($pOptions[IActiveRecordFindConfig::findCondition]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findConditionMap,$pOptions)) {
			$this->setConditionMap($pOptions[IActiveRecordFindConfig::findConditionMap]);
			unset($pOptions[IActiveRecordFindConfig::findConditionMap]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findFilter,$pOptions)) {
			$this->setFilter($pOptions[IActiveRecordFindConfig::findFilter]);
			unset($pOptions[IActiveRecordFindConfig::findFilter]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findGroup,$pOptions)) {
			$this->setGroup($pOptions[IActiveRecordFindConfig::findGroup]);
			unset($pOptions[IActiveRecordFindConfig::findGroup]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findHaving,$pOptions)) {
			$this->setHaving($pOptions[IActiveRecordFindConfig::findHaving]);
			unset($pOptions[IActiveRecordFindConfig::findHaving]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findSort,$pOptions)) {
			$this->setSort($pOptions[IActiveRecordFindConfig::findSort]);
			unset($pOptions[IActiveRecordFindConfig::findSort]);
		}
			
		if(array_key_exists(IActiveRecordFindConfig::findRequireJoin,$pOptions)) {
			$this->setRequireJoin($pOptions[IActiveRecordFindConfig::findRequireJoin]);
			unset($pOptions[IActiveRecordFindConfig::findRequireJoin]);
		}
			
		if(array_key_exists(IActiveRecordFindConfig::findJoinType,$pOptions)) {
			$this->setJoinType($pOptions[IActiveRecordFindConfig::findJoinType]);		
			unset($pOptions[IActiveRecordFindConfig::findJoinType]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findInvisible,$pOptions)) {
			$this->setInvisible($pOptions[IActiveRecordFindConfig::findInvisible]);
			unset($pOptions[IActiveRecordFindConfig::findInvisible]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findEmpty,$pOptions)) {
			$this->setEmpty($pOptions[IActiveRecordFindConfig::findEmpty]);
			unset($pOptions[IActiveRecordFindConfig::findEmpty]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findIgnoreModelFilter,$pOptions)) {
			$this->setIgnoreModelFilter($pOptions[IActiveRecordFindConfig::findIgnoreModelFilter]);
			unset($pOptions[IActiveRecordFindConfig::findIgnoreModelFilter]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findAssociation,$pOptions)) {
			$this->setAssociation($pOptions[IActiveRecordFindConfig::findAssociation]);
			unset($pOptions[IActiveRecordFindConfig::findAssociation]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findAssociationPropertyName,$pOptions)) {
			$this->setAssociationPropertyName($pOptions[IActiveRecordFindConfig::findAssociationPropertyName]);
			unset($pOptions[IActiveRecordFindConfig::findAssociationPropertyName]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findAssociationPropertyType,$pOptions)) {
			$this->setAssociationPropertyType($pOptions[IActiveRecordFindConfig::findAssociationPropertyType]);
			unset($pOptions[IActiveRecordFindConfig::findAssociationPropertyType]);
		}
		
		if(array_key_exists(IActiveRecordFindConfig::findCount,$pOptions)) {
			$this->setCount($pOptions[IActiveRecordFindConfig::findCount]);
			unset($pOptions[IActiveRecordFindConfig::findCount]);
		}
			
		if(!empty($pOptions)) $this->setMagicalFilter($pOptions);
	
	}
	
	// setters
	
	public function setInclude($pInclude) {
		
		/*
		* Compatible with comma separated list values. IE. content,project
		* This eliminates some typing and makes defining multiple includes
		* a little less tedious providing an alternative to the standard array format. 
		*/
		if( is_string($pInclude) && strpos($pInclude,',') !== false ) {
			$pInclude = explode(',',$pInclude);
		}
	
		$this->_include = is_array($pInclude)?$pInclude:array($pInclude);
	
	}
	
	/*
	* Number of rows to return
	* 
	* @param int number of rows
	*/
	public function setLimit($pLimit) {
	
		$this->_limit = $pLimit;
	
	}
	
	/*
	*  The query offset
	*  
	*  @param int query offset
	*/
	public function setOffset($pOffset) {
	
		$this->_offset = $pOffset;
	
	}
	
	/*
	* Columns to select
	* 
	* @param mix array of column names or single column string
	*/
	public function setSelect($pSelect) {
	
		$this->_select = is_array($pSelect)?$pSelect:array($pSelect);
	
	}
	
	/*
	* Columns to deselect (remove from selection)
	* 
	* @param mix column names to remove or single column name to remove
	*/
	public function setNonSelect($pNonSelect) {
	
		$this->_nonSelect = is_array($pNonSelect)?$pNonSelect:array($pNonSelect);
	
	}
	
	/*
	* Calculated column defitions that will be overloaded as properties
	* 
	* @param array associative array where the key is the intended property
	*              name and value is the SQL to derive the value.
	*/
	public function setDynamic($pDynamic) {
	
		$this->_dynamic = $pDynamic;
	
	}
	
	public function setCondition($pCondition) {
	
		$this->_condition = $pCondition;
	
	}
	
	public function setConditionMap($pConditionMap) {
	
		$this->_conditionMap = $pConditionMap;
	
	}
	
	public function setFilter($pFilter) {
	
		$this->_filter = $pFilter;
	
	}
	
	public function setGroup($pGroup) {
	
		$this->_group = is_array($pGroup)?$pGroup:array($pGroup);
	
	}
	
	public function setSort($pSort) {
		
		$this->_sort = is_array($pSort)?$pSort:array($pSort);
	
	}
	
	public function setHaving($pHaving) {
	
		$this->_having = is_array($pHaving)?$pHaving:array($pHaving);
	
	}
	
	public function setJoinType($pJoinType) {
	
		$this->_joinType = $pJoinType;
	
	}
	
	public function setRequireJoin($pRequireJoin) {
	
		$this->_requireJoin = $pRequireJoin;
	
	}
	
	public function setMagicalFilter($pMagicalFilter) {
	
		$this->_magicalFilter = $pMagicalFilter;
	
	}
	
	public function setInvisible($pInvisible) {
	
		$this->_invisible = $pInvisible;
	
	}
	
	public function setEmpty($pEmpty) {
	
		if(is_bool($pEmpty)) {
	
			$this->_empty = $pEmpty;
		
		}
	
	}
	
	public function setIgnoreModelFilter($pIgnoreModelFilter) {
	
		if(is_bool($pIgnoreModelFilter)) {
	
			$this->_ignoreModelFilter = $pIgnoreModelFilter;
		
		}
	
	}
	
	public function setAssociation($pAssociation) {
	
		$this->_association = $pAssociation;
	
	}
	
	public function setAssociationPropertyName($pPropertyName) {
		$this->_associationPropertyName = $pPropertyName;
	
	}
	
	public function setAssociationPropertyType($pPropertyType) {
		$this->_associationPropertyType = $pPropertyType;	
	}
	
	public function setCount($pCount) {
	
		if(is_bool($pCount)) {
	
			$this->_count = $pCount;
		
		}
	
	}
	
	// getters
	
	public function getInclude() {
	
		return $this->_include;
	
	}
	
	public function getLimit() {
	
		return $this->_limit;
	
	}
	
	public function getOffset() {
	
		return $this->_offset;
	
	}
	
	public function getSelect() {
	
		return $this->_select;
	
	}
	
	public function getNonSelect() {
	
		return $this->_nonSelect;
	
	}
	
	public function getDynamic() {
	
		return $this->_dynamic;
	
	}
	
	public function getCondition() {
	
		return $this->_condition;
	
	}
	
	public function getConditionMap() {
	
		return $this->_conditionMap;
	
	}
	
	public function getFilter() {
	
		return $this->_filter;
	
	}
	
	public function getGroup() {
	
		return $this->_group;
	
	}
	
	public function getSort() {
	
		return $this->_sort;
	
	}
	
	public function getJoinType() {
	
		return $this->_joinType;
	
	}
	
	public function getRequireJoin() {
	
		return $this->_requireJoin;
	
	}
	
	public function getHaving() {
	
		return $this->_having;
	
	}
	
	public function getMagicalFilter() {
	
		return $this->_magicalFilter;
	
	}
	
	public function getInvisible() {
	
		return $this->_invisible;
	
	}
	
	public function getEmpty() {
	
		return $this->_empty;
	
	}
	
	public function getIgnoreModelFilter() {
	
		return $this->_ignoreModelFilter;
	
	}
	
	public function getAssociation() {
	
		return $this->_association;
	
	}
	
	public function getAssociationPropertyName() {
	
		return $this->_associationPropertyName;
	
	}
	
	public function getAssociationPropertyType() {
	
		return $this->_associationPropertyType;
	
	}
	
	public function getCount() {
	
		return $this->_count;
	
	}
	
	// has methods

	public function hasInclude() {
	
		return is_null($this->_include) || empty($this->_include)?false:true;
	
	}
	
	public function hasLimit() {
	
		return is_null($this->_limit)?false:true;
	
	}
	
	public function hasOffset() {
	
		return is_null($this->_offset)?false:true;
	
	}
	
	public function hasSelect() {
	
		return is_null($this->_select)?false:true;
	
	}
	
	public function hasNonSelect() {
	
		return is_null($this->_nonSelect) || empty($this->_nonSelect)?false:true;
	
	}
	
	public function hasDynamic() {
	
		return is_null($this->_dynamic) || empty($this->_dynamic)?false:true;
	
	}
	
	public function hasCondition() {
	
		return is_null($this->_condition) || empty($this->_condition)?false:true;
	
	}
	
	public function hasConditionMap() {
	
		return is_null($this->_conditionMap) || empty($this->_conditionMap)?false:true;
	
	}
	
	public function hasFilter() {
	
		return is_null($this->_filter) || empty($this->_filter)?false:true;
	
	}
	
	public function hasGroup() {
	
		return is_null($this->_group) || empty($this->_group)?false:true;
	
	}
	
	public function hasHaving() {
	
		return is_null($this->_having) || empty($this->_having)?false:true;
	
	}
	
	public function hasSort() {
	
		return is_null($this->_sort) || empty($this->_sort)?false:true;
	
	}
	
	public function hasJoinType() {
	
		return is_null($this->_joinType)?false:true;
	
	}
	
	public function hasRequireJoin() {
	
		return is_null($this->_requireJoin)?false:true;
	
	}
	
	public function hasMagicalFilter() {
	
		return is_null($this->_magicalFilter) || empty($this->_magicalFilter)?false:true;
	
	}	
	
	public function hasInvisible() {
	
		return is_null($this->_invisible)?false:true;
	
	}
	
	public function hasEmpty() {
	
		return is_null($this->_empty)?false:true;
	
	}
	
	public function hasIgnoreModelFilter() {
	
		return is_null($this->_ignoreModelFilter)?false:true;
	
	}
	
	public function hasAssociation() {
	
		return is_null($this->_association) || empty($this->_association)?false:true;
	
	}
	
	public function hasAssociationPropertyName() {
	
		return is_null($this->_associationPropertyName)?false:true;
	
	}
	
	public function hasAssociationPropertyType() {
	
		return is_null($this->_associationPropertyType)?false:true;
	
	}
	
	public function hasCount() {
	
		return is_null($this->_count)?false:true;
	
	}
	

}
?>