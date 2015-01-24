<?php

namespace Druid\Core\Query\Action;

use Druid\Interfaces\QueryAction as IActiveRecordQueryAction;
use Druid\Core\Model\ModelConfig as ActiveRecordModelConfig;
use Druid\Storage\ActiveRecord as ActiveRecord;

//require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../../interface/query_action.php');
//require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../model/model_config.php');
class ActiveRecordSetPrimaryKeyAction implements IActiveRecordQueryAction {

	protected $record;

	public function __construct(ActiveRecord $record) {
	
		$this->record = $record;
	
	}

	public function doAction(\PDO $db,\PDOStatement $statement) {
	
		$className = get_class($this->record);
		$config = ActiveRecordModelConfig::getModelConfig($className);
		
		$this->record->setProperty($config->getPrimaryKey(),$db->lastInsertId());
		$this->record->cast();
		
		return true;
	
	}

}
?>