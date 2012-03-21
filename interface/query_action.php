<?php
interface IActiveRecordQueryAction {

	public function doAction(PDO $db,PDOStatement $stmt);
	
}
?>