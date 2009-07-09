<?php
interface IActiveRecordCascadeAction {

	public function doSomething(ActiveRecordCascadeNode $node,$nodes=null); // bool
	
}
?>