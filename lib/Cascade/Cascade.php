<?php

namespace Druid\Cascade;

use Druid\Cascade\CascadeNode as ActiveRecordCascadeNode;
use Druid\Core\Model\ModelConfig as ActiveRecordModelConfig;
use Druid\Interfaces\CascadeAction as IActiveRecordCascadeAction;

require_once( str_replace('//','/',dirname(__FILE__).'/') .'../core/model/model_config.php');
class Cascade {

	protected $action;

	public function __construct(IActiveRecordCascadeAction $action) {
	
		$this->action = $action;
	
	}
	
	public function cascade(ActiveRecordCascadeNode $node,$nodes = null) {
		
		if($this->action->doSomething($node,$nodes)===true) {
	
			$this->_resolveHasOne($node,$nodes);
			$this->_resolveHasMany($node,$nodes);
			$this->_resolveBelongsToAndHasMany($node,$nodes);
		
		}
	
	}

	protected function _resolveHasOne(ActiveRecordCascadeNode $node,$nodes = null) {

		if($node->getConfig()->hasOne()===true) {
	
			foreach($node->getConfig()->getHasOne() as $model) {
			
				$class = Inflector::classify($model);
				$relatedConfig = ActiveRecordModelConfig::getModelConfig($class);	
				$relatedNode = new ActiveRecordCascadeNode($relatedConfig);
				$this->_collectRecord($node,$relatedNode,$model);
				$relatedNodes = is_null($nodes)?array():$nodes;
				array_unshift($relatedNodes,$node);
				$this->cascade($relatedNode,$relatedNodes);
		
			}
	
		}

	}

	protected function _resolveHasMany(ActiveRecordCascadeNode $node,$nodes = null) {

		if($node->getConfig()->hasMany()===true) {
	
			foreach($node->getConfig()->getHasMany() as $model) {
				
				$class = Inflector::classify($model);
				$relatedConfig = ActiveRecordModelConfig::getModelConfig($class);	
				$relatedNode = new ActiveRecordCascadeNode($relatedConfig);
				$this->_collectRecords($node,$relatedNode,$model);
				$relatedNodes = is_null($nodes)?array():$nodes;
				array_unshift($relatedNodes,$node);
				$this->cascade($relatedNode,$relatedNodes);
		
			}
	
		}

	}
	
	protected function _resolveBelongsToAndHasMany(ActiveRecordCascadeNode $node,$nodes = null) {
	
		if($node->getConfig()->hasBelongsToAndHasMany()===true) {
		
			foreach($node->getConfig()->getBelongsToAndHasMany() as $index=>$reference) {
			
				$class = Inflector::classify($reference[1]);
				$relatedConfig = ActiveRecordModelConfig::getModelConfig($class);	
				$relatedNode = new ActiveRecordCascadeNode($relatedConfig);
				$this->_collectRecords($node,$relatedNode,$reference[1]);
				$relatedNodes = is_null($nodes)?array():$nodes;
				array_unshift($relatedNodes,$node);
				$this->cascade($relatedNode,$relatedNodes);
			
			}
		
		}
	
	}
	
	protected function _collectRecords(ActiveRecordCascadeNode $node,ActiveRecordCascadeNode $relatedNode,$property) {
		
		if($node->hasRecords()===true) {
			foreach($node->getRecords() as $record) {
			
				if($record->hasProperty($property)===true && !is_null($record->getProperty($property))) {
				
					foreach($record->getProperty($property) as $relatedRecord) {
						$relatedNode->addRecord($relatedRecord);
					}
				
				}
			
			}
		}
	
	}
	
	protected function _collectRecord(ActiveRecordCascadeNode $node,ActiveRecordCascadeNode $relatedNode,$property) {
		
		if($node->hasRecords()===true) {
		
			foreach($node->getRecords() as $record) {
			
				if($record->hasProperty($property)===true && !is_null($record->getProperty($property))) {
				
					$relatedNode->addRecord($record->getProperty($property));
					
				}
			}
		}
	
	}

}
?>