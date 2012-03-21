<?php
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../core/model/model_config.php');
class ActiveRecordDOMElement extends DOMDocument {

	public function __construct($active,$version='1.0',$encoding=null) {
	
		parent::__construct($version,$encoding);
		
		$this->_init($active);
	
	}
	
	protected function _init($value) {
	
		if($value instanceof IActiveRecordDataEntity) {
			
			$this->_parseObject($value);
		
		} else if($value instanceof ActiveRecordCollection) {
			
			$this->_parseCollection($value);
		
		} else {
		
			throw new Exception('First argument of '.__CLASS__.'must be instance of either IActiveRecordDataEntity or ActiveRecordCollection. Exception thrown from method '.__METHOD__.' at line '.__LINE__.'.');
		
		}
	
	}
	
	protected function _parseObject(ActiveRecord $entity,DOMElement $node=null) {
		
		$className = get_class($entity);
		if(is_null($node)) {
			$node = $this->createElement(Inflector::underscore($className));
			$this->appendChild($node);	
		}
		
		$config = ActiveRecordModelConfig::getModelConfig($className);
		
		$model = $config->getClassName();
		$table = $config->getTable();
		
		$node->setAttribute('model',$model);
		$node->setAttribute('table',$table);
		
		$pk = false;
		
		foreach($entity as $property=>$value) {
		
			//if($property=='link') { echo '<p>',$config->getClassName(),'</p>';continue; }
		
			$objectNode = $this->createElement(strcmp($property,'link')==0 || strcmp($property,'meta')==0?'_'.$property:$property);
			$node->appendChild($objectNode);
			
			if($pk===false && strcmp($config->getPrimaryKey(),$property)==0) {
				$node->setAttribute(IActiveRecordModelConfig::defaultPrimaryKeyName,$value);
				$pk = true;
			}
		
			if($value instanceof IActiveRecordDataEntity) {
		
				$this->_parseObject($value,$objectNode);
		
			} else if($value instanceof ActiveRecordCollection) {
				
				$this->_parseCollection($value,$objectNode);
		
			} else {
				
				// here 
				$convertedString = is_null($value)?'':mb_convert_encoding((is_bool($value)?intval($value):$value),'UTF-8',array('ASCII','ISO-8859-1','CP1252','UTF-8'));
				//$convertedString = is_null($value)?'':$this->_convertEncoding($value);
				$textNode = $this->createTextNode($convertedString);
				$objectNode->appendChild($textNode);
		
			}
		
		}
	
		return $node;	
	
	}
	
	protected function _parseCollection(ActiveRecordCollection $collection,DOMElement $node=null) {
		
		if(is_null($node)) {
			$name = count($collection)!=0?Inflector::pluralize(Inflector::underscore(get_class($collection[0]))):'active_records';
			$node = $this->createElement($name);
			$this->appendChild($node);	
		}
	
		if(count($collection)!=0) {
			foreach($collection as $object) {
		
				$childNode = $this->_parseObject($object);	
				$node->appendChild($childNode);
	
			}
		}	
	
	}

}