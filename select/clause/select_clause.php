<?php
class ActiveRecordSelectClause {	

	const selectTransformation = 'select';
	const selectDynamicThisModel = '{this}';

	private $_selectData;
	
	private $_nodes;
	private $_fields;
	
	public function __construct() {
	
		$this->_selectData = array();
		$this->_nodes = array();
		$this->_fields = array();
	
	}
	
	public function getSelectData() {
	
		return $this->_selectData;
		
	}
	
	public function getNodes() {
	
		return $this->_nodes;
	
	}
	
	public function getFields() {
	
		return $this->_fields;
	
	}
	
	public function toSql() {
		
		$select = array();
		foreach($this->_fields as $fields) {
			
			if(empty($fields)) continue;
			
			$select[] = implode(',',$fields);
		
		}
		
		return implode(',',$select);
	
	}

	public function select(ActiveRecordSelectNode $pNode,$pFields,$pApplyTransform=true,$pPrefix='',$pAliasPrefix='') {
	
		$index = array_search($pNode,$this->_nodes);
	
		if($index===false) {
			$this->_nodes[] = $pNode;
			$this->_fields[] = array();
			$index = array_search($pNode,$this->_nodes);
		}
	
		$config = $pNode->getConfig();
    	
    	$className = $config->getClassName();
    	$alias = 't'.$pNode->getUnique();
    	$transformations = $config->hasTransformations()?$config->getTransformations():array();
   
    	foreach($pFields as $field) {
    	
    		$str = '';
    			
    		if($pApplyTransform===true && array_key_exists($field,$transformations) && array_key_exists(self::selectTransformation,$transformations[$field])) {
    			
    			$str = $this->_transformFilter($transformations[$field][self::selectTransformation],$className,$alias);
    			
    		} else {
    			
    			$aliasPrefix = empty($pAliasPrefix)?$alias:$pAliasPrefix;
    			$str = $alias==''?'`'.$pPrefix.$field.'`':$aliasPrefix.'.`'.$pPrefix.$field.'`';		
    			
    		}
    			
    		$str.= ' AS '.$alias.'_'.$field;
    		$this->_fields[$index][$field] = $str;
    	
    	}		
	
	}
	
	public function selectDynamic(ActiveRecordSelectNode $pNode,$pFields,$pHasPriority=false) {
	
		$index = array_search($pNode,$this->_nodes);
	
		if($index===false) {
			$this->_nodes[] = $pNode;
			$this->_fields[] = array();
			$index = array_search($pNode,$this->_nodes);
		}	
		
		$config = $pNode->getConfig();
		$alias = 't'.$pNode->getUnique();
		$className = $config->getClassName();
		
		foreach($pFields as $name=>$field) {
			
			// avoid processing columns with conflicting names. Model columns have priority over dynamic ones
			// maybe throw error if this conditon occurs.
			if($pHasPriority===false && array_key_exists($name,$this->_fields[$index])) continue;
			
			$str='';
			
			if($field instanceof ActiveRecordSelectStatement) {
				
				// may run into conflicting names - if outter models are the same
				$str = '('.$field->toSql().')';
				
				$this->_selectData = array_merge($this->_selectData,$field->getBindData());
			
			} else if(is_array($field)) {
				
				$str = array_shift($field);
				
				$this->_selectData = array_merge($this->_selectData,$field);
			
			} else {
			
				$str = $field;
			
			}
			
			//$str = $pAliases->replaceWithAlias($str);
			$str = str_replace(array($className.'.',self::selectDynamicThisModel.'.'),$alias.'.',$str);
			
			if($pNode->hasChild()===true) {
				$childUnique = $pNode->getChild()->getUnique();
				$str = str_replace('{next}.','t'.$childUnique.'.',$str);
			}
			
    		$str.= ' AS '.$alias.'_'.$name;
    		$this->_fields[$index][$name] = $str;
		
		}
	
	}
	
    protected function _transformFilter($pTransform,$pTable,$pTableAlias) {
    	
		$transform = is_array($pTransform)?$pTransform:array($pTransform);

		$statement = $transform[0];
		$data = array();

		$matches = array();
		preg_match_all('/\$[1-9][0-9]*?/',$statement,$matches,PREG_OFFSET_CAPTURE);

		if(array_key_exists(0,$matches) && !empty($matches[0])) {

			$offset = 0;
	
			foreach($matches[0] as $key=>$match) {
		
				if(strpos($match[0],'$')===0) {
		
					$index = (int) substr($match[0],1);
			
					if(array_key_exists($index,$transform)) {
					
						$data[] = $transform[$index];				
						$statement = substr_replace($statement,'?',($match[1]+$offset),strlen($match[0]));
						$offset-= (strlen($match[0])-1);
					
					}
		
				}
	
			}

		}
		
		$this->_selectData = array_merge($this->_selectData,$data);
		return preg_replace('/'.$pTable.'\./',$pTableAlias.'.',$statement);  	
		
		
	}

}
?>