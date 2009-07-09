<?php
interface IActiveRecordList {
	
	public function addSibling(IActiveRecordList $pItem); // void
	public function getSibling(); // IActiveRecordList
	public function setSibling(IActiveRecordList $pItem); // void
	public function hasSibling(); // boolean

}
?>