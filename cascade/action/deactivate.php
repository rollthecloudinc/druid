<?php
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/cascade_action.php');
class ActiveRecordDeactivate implements IActiveRecordCascadeAction {

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