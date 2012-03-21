<?php
class ActiveRecordSelectClause {	

	const selectTransformation = 'select';
	const selectDynamicThisModel = '{this}';

	private $_selectData;
	
	private $_nodes;
	private $_fields;
	
	/*
	* Transformations that will be applied after query has been executed
	* using callback php function, static method or object method. 
	*/
	private $_transform;
	
	public function __construct() {
	
		$this->_selectData = array();
		$this->_nodes = array();
		$this->_fields = array();
		$this->_transform = array();
	
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
	
	public function getTransform() {
	
		return $this->_transform;
	
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
    			
    			$str = $this->_transformFilter($transformations[$field][self::selectTransformation],$className,$alias,$index,$field);
    			
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
	
    protected function _transformFilter($pTransform,$pTable,$pTableAlias,$pIndex,$pField) {
    	
		$transform = is_array($pTransform)?$pTransform:array($pTransform);

		$statement = $transform[0];
		$data = array();
		
    	/*
		* Extract post query transformation identified keywords self::, php:: and $this->
		* at the beginning of transform string. Transformations that fall under these specifications
		* will be applied after the query has been executed during the collection step. This is a great
		* way to cast 0 or 1 fields to true booleans or unserialize a serialized field.
		*/
		if(strpos($statement,'$this->') === 0 || strpos($statement,'self::') === 0 || strpos($statement,'php::') === 0) {
			list($callback,$statement) = explode(' ',preg_replace('/(\$this->|self::|php::)([a-zA-Z_][a-zA-Z0-9_]*?)\((.*?)\)$/',"$1#$2 $3",$statement),2);
			$this->_transform[$pIndex][$pField] = explode('#',$callback,2);
		}

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