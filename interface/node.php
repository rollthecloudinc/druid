<?php
require_once('list.php');
interface IActiveRecordNode extends IActiveRecordList {

	public function addChild(IActiveRecordNode $pItem); // void
	public function getChild(); // IActiveRecordNode
	public function setChild(IActiveRecordNode $pItem); // void
	public function hasChild(); // boolean

}
?>