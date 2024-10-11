<?php

namespace Druid\Cascade\Action;

use Druid\Interfaces\CascadeAction as IActiveRecordCascadeAction;
use Druid\Cascade\CascadeNode as ActiveRecordCascadeNode;

//require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/cascade_action.php');
class Deactivate implements IActiveRecordCascadeAction {

	public function doSomething(
		ActiveRecordCascadeNode $node
		,$nodes=null
	) {
	
		if($node->hasRecords()===true) {
		
			$primaryKey = $node->getConfig()->getPrimaryKey();
		
			foreach($node->getRecords() as $record) {
			
				$record->removeProperty($primaryKey);
			
			}
			
			return true;
			
		}
		
		return false;
	
	}

}
?>