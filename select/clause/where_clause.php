<?php
class ActiveRecordWhereClause {	

	const transformFilter = 'filter';
	const filterThisModel = '{this}';

	private $_filters;
	private $_filterData;
	private $_nodes;

	public function __construct() {
		
		$this->_filterData = array();
		$this->_nodes = array();
		$this->_filters = array();	
	
	}
	
	public function getFilters() {
	
		return $this->_filters;
	
	}
	
	public function getFilterData() {
	
		return $this->_filterData;
		
	}
	
	public function getNodes() {
	
		return $this->_nodes;
	
	}
	
	public function toSql($pDiv=' AND ') {
		
		$filters = array();
		foreach($this->_filters as $filter) {
			
			if(empty($filter)) continue;
			
			$filters[] = implode($pDiv,$filter);
		
		}
		
		return implode($pDiv,$filters);
	
	}
	
	public function addFilter(ActiveRecordSelectNode $pNode,$pFilter,$pApplyTransform=true,$pTransformKey='') {
	
		$index = array_search($pNode,$this->_nodes);
		
		if($index===false) {
			$this->_filters[] = array();
			$this->_nodes[] = $pNode;
			$index = array_search($pNode,$this->_nodes);
		}
		
		$config = $pNode->getConfig();
		$alias = 't'.$pNode->getUnique();
		$className = $config->getClassName();
		$transformKey = empty($pTransformKey)?self::transformFilter:$pTransformKey;
		
        $transform = $config->hasTransformations()?$config->getTransformations():array();
       
    	foreach($pFilter as $field=>$filter) {
    		
    		// break apart the selector into component parts
    		$s = $this->_breakApartFilterSelector($field);
    		
    		// set some variables equal to selector parts to make this less confusing
    		$selector = $s['selector'];
    		$operand = $s['operand'];
    		$column = $s['column'];
    		
    		if($filter instanceof ActiveRecordSelectStatement) {
    		
    			$this->_filters[$index][] = $alias.'.'.$selector.' ('.$filter->toSql().')';
    			$this->_filterData = array_merge($this->_filterData,$filter->getBindData()); 
    			
    		} else if($filter instanceof ActiveRecord) {
    			
    			// this filter is special becasue it ignores the field name
    			// doesn't even need a key
				$this->_activeRecordFilter($pNode,$filter,$operand,$index,$alias);
    		
    		} else if(	
    			$pApplyTransform===true &&
    			!empty($transform) && // check for class dependent column transformations
    			array_key_exists($column,$transform) && // check for specific column transformation
    			array_key_exists($transformKey,$transform[$column]) // does column have a transformation of the specific type
    		) {
    			
    			$this->_filters[$index][] = $this->_transformFilter($transform[$column][$transformKey],$className,$alias,$selector,$filter,$operand);
    			
    		} else if(is_array($filter)) { // data needs to be broken apart
    			
				$this->_filters[$index][] = $this->_multifilter($className,$alias,$selector,$filter,$operand);			
    			
    		} else { // data primitive type and can be directly bound
    			
    			$this->_filters[$index][] = $this->_defaultFilter($alias,$selector);
    			$this->_filterData[] = $filter;
    			
    		}
    		
    	}
	
	}
	
    protected function _transformFilter($pTransform,$pTable,$pTableAlias,$pSelector,$pFilter,$pOperand='') {
    	
		$transform = is_array($pTransform)?$pTransform:array($pTransform);
		$values = is_array($pFilter)?$pFilter:array($pFilter);
		$num = count($values);

		$statement = $transform[0];
		$data = array();

		$matches = array();
		preg_match_all('/\$[1-9][0-9]*?|\{this\}/',$statement,$matches,PREG_OFFSET_CAPTURE);

		if(array_key_exists(0,$matches) && !empty($matches[0])) {

			$offset = 0;
			$totalMatches = count($matches[0]);
	
			foreach($matches[0] as $key=>$match) {
		
				if(strpos($match[0],'$')===0) {
		
					$index = (int) substr($match[0],1);
			
					if(array_key_exists($index,$transform)) {
					
						for($i=0;$i<$num;$i++) $data[(($i*$totalMatches)+$key)] = $transform[$index];				
						$statement = substr_replace($statement,'?',($match[1]+$offset),strlen($match[0]));
						$offset-= (strlen($match[0])-1);
					
					}
		
				} else {
		
					for($i=0;$i<$num;$i++) $data[(($i*$totalMatches)+$key)] = $values[$i];			
					$statement = substr_replace($statement,'?',($match[1]+$offset),strlen($match[0]));
					$offset-= (strlen($match[0])-1);
		
				}
	
			}
	
			ksort($data);

		}
		
		$this->_filterData = array_merge($this->_filterData,$data);
		$statement = preg_replace('/'.$pTable.'\./',$pTableAlias.'.',$statement);  
		
		if(is_array($pFilter)) {
			$pSelector = strcmp($pOperand,'=')==0?rtrim($pSelector,'=').'IN':$pSelector;
			return $pTableAlias.'.'.$pSelector.' ('.rtrim(str_repeat($statement.',',$num),',').')';
		} else {
			return $pTableAlias.'.'.$pSelector.' '.$statement;
		}
    	
    }
    
    protected function _multiFilter($pTable,$pTableAlias,$pSelector,$pFilter,$pOperand='') {
    	
    	$str = '';
    	$t = $pTableAlias;
    	
    	// if parenthesis is the first character of selector remove and reset to separate variable
		if(strcmp(substr($pSelector,0,1),'(')==0) {
     		$parenthesis = '( ';
       		$pSelector = preg_replace('/^\(/','',$pSelector);
       	} else {
     		$parenthesis = '';
        }
        
        // allows functions to be used in selector
        if(strpos($pSelector,')')!==false && preg_match('/^.+?\(.+?\).*?$/',$pSelector)) {
        	$pSelector = str_replace(array('{this}'.'.',$pTable.'.'),$pTableAlias.'.',$pSelector);
        	$t ='';
        } else {
        	$t.='.';
        }
        
        // integrated this to support array() syntax shorthand as a list without embedding first item
        // this may break current implementation with a embedded subquery string (be warned)
        if(
        	(array_key_exists(0,$pFilter) && !is_string($pFilter[0]))
        	|| (strcasecmp($pOperand,'NOT IN')==0 || strcasecmp($pOperand,'IN')==0)
        ) { 
        	$magicalClosingParenthesis = strcmp($parenthesis,'( ')==0?')':'';
        	$pSelector = strcmp($pOperand,'=')==0?rtrim($pSelector,'=').'IN':$pSelector;
        	$str = $parenthesis.$t.$pSelector.' ('.rtrim(str_repeat('?,',count($pFilter)),',').')'.$magicalClosingParenthesis;        
		} else {
        	$str = $parenthesis.$t.$pSelector.' '.preg_replace(array('/'.$pTable.'\./','/\{this\}\./'),$pTableAlias.'.',array_shift($pFilter));        
		}
		
		$this->_filterData = array_merge($this->_filterData,$pFilter);
        
        return $str;
    	
    }
    
    protected function _defaultFilter($pTableAlias,$pSelector) {
    	
    	$t = $pTableAlias;
    	
    	// allows functions to be used in selector
        if(strpos($pSelector,')')!==false && preg_match('/^.+?\(.+?\).*?$/',$pSelector)) {
        	$pSelector = str_replace(array('{this}.'),$pTableAlias.'.',$pSelector);
        	$t ='';
        } else {
        	$t.='.';
        }
    	
    	return $t.$pSelector.' ?';
    	
    }
    
    /*
     * breaks apart filter selector into component pieces
     */
    protected function _breakApartFilterSelector($pSelector) {
    	
    	$operand = trim($this->_extractFilterSelectorOperand($pSelector));
    	$column = trim(str_replace($operand,'',$pSelector));
    	$pSelector = $column.' '.$operand;
    	return array('selector'=>$pSelector,'operand'=>$operand,'column'=>$column);
    	
    }
    
    /*
     * finds operator in selector and returns it separetly
     */
    protected function _extractFilterSelectorOperand($pSelector) {
    	
    	// list of known oporators
      	$operand = preg_replace('/^.*?(\<|\>|\<\>|\<=|\>=|=|NOT\sLIKE|\sIS|\sIS NOT|\sLIKE|\!|\!=|\sbetween|\sBETWEEN|\sIN|\sNOT\sIN)$/','$1',$pSelector);
    	// if no operator present append default (=) operator
    	return strcasecmp($operand,$pSelector)==0?'=':$operand;  	
    	
    }
	
	public function addCondition(ActiveRecordSelectNode $pNode,$pConditionMap,$pConditions) {
	
		$index = array_search($pNode,$this->_nodes);
		
		if($index===false) {
			$this->_filters[] = array();
			$this->_nodes[] = $pNode;
			$index = array_search($pNode,$this->_nodes);
		}
	
		$config = $pNode->getConfig();
		$className = $config->getClassName();
		
		$matches = array();
		preg_match_all('/{.*?}/',$pConditionMap,$matches);

		foreach($matches[0] as $key=>$match) {

			$conditionName =  str_replace(array('{','}'),'',$match);
	
			if(array_key_exists($conditionName,$pConditions)) {
			
				if($pConditions[$conditionName] instanceof ActiveRecordSelect) {
				
					$pConditionMap = str_replace('{'.$conditionName.'}','('.$pConditions[$conditionName]->toSql().')',$pConditionMap);
					$this->_filterData = array_merge($this->_filterData,$pConditions[$conditionName]->getBindData());
	
				} else if(is_array($pConditions[$conditionName])) {
			
					$copyData = $pConditions[$conditionName];
					$pConditionMap = str_replace('{'.$conditionName.'}',array_shift($copyData),$pConditionMap);
					$this->_filterData = array_merge($this->_filterData,$copyData);			
		
				} else {
			
					$str = $pConditions[$conditionName];
					$pConditionMap = str_replace('{'.$conditionName.'}',$str,$pConditionMap);
		
				}
				
			}
	
		}
		
		//$pConditionMap = $pAliases->replaceWithAlias($pConditionMap);
		if(strpos($pConditionMap,self::filterThisModel.'.')!==false) {
			$pConditionMap = str_replace(self::filterThisModel.'.','t'.$pNode->getUnique().'.',$pConditionMap);
		}
		
		$this->_filters[$index][] = $pConditionMap;		
	
	}
	
	protected function _activeRecordFilter(ActiveRecordSelectNode $pNode,ActiveRecord $filter,$operand,$index,$alias) {
	
    	$relatedConfig = ActiveRecordModelConfig::getModelConfig(get_class($filter));
    	$f1 = $pNode->getConfig()->getRelatedField($relatedConfig);
    	$f2 = $relatedConfig->getRelatedField($pNode->getConfig());
    			
    	if(empty($f1) || empty($f2)) {
    	
    		throw new Exception($pNode->getConfig()->getClassName().' could be related to '.$relatedConfig->getClassName().' in filter. Exception generated inside class '.__CLASS__.' in method '.__METHOD__.' on line '.__LINE__.'.');  		
    		
    	} else {
    			
    		$this->_filters[$index][] = $alias.'.'.$f1.' '.$operand.' ?';  
    		$this->_filterData[] = $filter->getProperty($f2);	
    		
    	}
	
	}

}
?>