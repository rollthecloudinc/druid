<?php

namespace Druid\Rake;

use Druid\Interfaces\ModelConfig as IActiveRecordModelConfig;
use Druid\Core\Inflector\Inflector as Inflector;
use Druid\Core\Model\ModelConfig as ActiveRecordModelConfig;

//require_once( str_replace('//','/',dirname(__FILE__).'/') .'../core/inflector/inflector.php');
//require_once( str_replace('//','/',dirname(__FILE__).'/') .'../core/model/model_config.php');
class Generate {

	protected $db;
	protected $configs;
	
	protected $table = true;
	protected $fields = true;
	protected $primaryKey = true;
	protected $foreignKeys = true;
	protected $dataTypes = true;
	protected $defaultValues = true;
	protected $requiredFields = true;

	public function __construct(\PDO $db) {
	
		$this->db = $db;
		$this->configs = array();
		
		$this->_init();
	
	}
	
	protected function _init() {
		
		$this->disableAll();
		
		// enable fields, requiredFields and foreign keys only be default. Disable everything else
		// enable table by default just in case table doesn't follow plural rule
		$this->enableTable();
		$this->enablePrimaryKey();
		$this->enableFields();
		$this->enableRequiredFields();
		$this->enableForeignKeys();
    $this->enableDataTypes();
	
	}
	
	protected function _generateBaseConfigs($tables=null) {

		foreach($this->db->query('show tables;') as $table) {
			
			if(!is_null($tables) && is_array($tables) && !in_array($table[0],$tables)) continue;
	
			$config = new ActiveRecordModelConfig();
			$config->setTable($table[0]);
			$config->setClassName(Inflector::classify($table[0]));
			$this->configs[] = $config;

		}	
	
	}
	
	protected function _generateDetailedConfig() {

		foreach($this->configs as $config) {

			$fields = array();
			$dataTypes = array();
			$requiredFields = array();
			$defaultValues = array();
			$foreignKeys = array();
			$belongsTo = array();
      $trueForeignKeys = array();
      $primaryKey = array();

      // Automated discovery or foreign keys based on reading create table statement.
      if($this->foreignKeys===true) {
        $trueForeignKeys = $this->_discoverSchemaForeignKeys($config->getTable());
      }

			foreach($this->db->query('describe '.$config->getTable().';') as $model) {
		
				if(!empty($model['Key']) && strcasecmp($model['Key'],'PRI')==0) $primaryKey[] = $model['Field'];;
				if(strcasecmp($model['Null'],'NO')==0 && is_null($model['Default']) && strcasecmp($model['Extra'],'auto_increment')!=0) $requiredFields[] = $model['Field'];		
				if(!empty($model['Default'])) $defaultValues[$model['Field']] = $model['Default'];	
				$fields[] = $model['Field'];

        // @todo: Ignore enums for now because they cause invalid PHP in model files currently.
        if(stripos($model['Type'],'enum(') !== 0) {
          $dataTypes[$model['Field']] = $model['Type'];
        }

        // Check whether current field is a foreign key field.
        if(array_key_exists($model['Field'],$trueForeignKeys)) {

          // Relation table name raw
          $m = $trueForeignKeys[$model['Field']][0];

          // Convert relation table to name to local model name.
          $className = Inflector::classify($m);

          // Create concrete mapping.
          $foreignKeys[$model['Field']] = array($className,$trueForeignKeys[$model['Field']][1]);

          // Set belongs to.
          $belongsTo[] = $m;
        }

        //  @todo: I really don't link this when comparing to the new method :/
				/*if($this->foreignKeys===true && preg_match('/^.*?_'.IActiveRecordModelConfig::defaultPrimaryKeyName.'$/',$model['Field'])) {
					$m = substr($model['Field'],0,-3);
					$className = Inflector::classify($m);
					$foreignKeys[$model['Field']] = array($className);
					$belongsTo[] = $m;
				}*/
	
			}

      // Composite key support.
      // @todo: Discover has relationship / though -- Good chance this is a belongToAndHasMany relationship
      if(count($primaryKey) > 1) {
        $config->setPrimaryKey($primaryKey);
      } else if($primaryKey) {
        $config->setPrimaryKey($primaryKey[0]);
      }
	
			if($this->fields===true) $config->setFields($fields);
			if($this->dataTypes===true) $config->setDataTypes($dataTypes);
			if($this->requiredFields===true) $config->setRequiredFields($requiredFields);
			if($this->defaultValues===true) $config->setDefaultValues($defaultValues);
			if($this->foreignKeys===true) $config->setForeignKeys($foreignKeys);
			if($this->foreignKeys===true) $config->setBelongsTo($belongsTo);
	
		}	
	
	}

  /**
   * Automated discover of foreign keys in table using a single
   * algorithm. This is useful for innodb tables or any engine
   * that supports true foreign key columns. Otherwise, proper
   * naming is requiring or manually added them after generating
   * the active record files.
   *
   * @param str $table
   *   The table to discover foreign keys in.
   *
   * @return arr
   *   Array of discovered foreign key mappings.
   */
  protected function _discoverSchemaForeignKeys($table) {

    // Pattern that will be used to match foreign keys.
    $pattern = '/CONSTRAINT\s`.*?`\sFOREIGN KEY\s\(`([a-zA-Z1-9_]*?)`\)\sREFERENCES\s`([a-zA-Z1-9_]*?)`\s\(`([a-zA-Z1-9_]*?)`\)/i';

    // Foreign key matches.
    $matches = array();

    // The return array of discovered foreign keys.
    $foreignKeys = array();

    // Use create table statement to derive foreign keys.
    $result = $this->db->query(sprintf('show create table %s;',$table));

    // Get data row.
    $row = $result->fetch(\PDO::FETCH_ASSOC);

    if(isset($row['Create Table'])) {

      // Match columns.
      preg_match_all($pattern, $row['Create Table'], $matches);

      // Remove the first item (full strings)
      array_shift($matches);

      if ($matches) {

        // NUmber of foreign keys found.
        $count = count($matches[0]);

        // Collect them.
        for ($i = 0; $i < $count; $i++) {
          $foreignKeys[$matches[0][$i]] = array($matches[1][$i], $matches[2][$i]);
        }

      }

    }

    return $foreignKeys;

  }
	
	protected function _resolveBasicHasRelationships() {

		foreach($this->configs as $config) {

			if($config->hasForeignKeys()===true) {
	
				foreach($config->getForeignKeys() as $index=>$reference) {
		
					$class = $reference[0];
		
					// cycle through configs and look for model MATCH
					foreach($this->configs as $config2) {
				
						if(strcmp($config2->getClassName(),$class)==0) {
					
							$has = $config2->hasMany()===true?$config->getHasMany():array();
							$has[] = Inflector::pluralize(Inflector::underscore($config->getClassName()));
							$config2->setHasMany($has);
							break;
					
						}
				
					}
		
				}
	
			}

		}	
	
	}
	
	protected function _makeBaseFile(IActiveRecordModelConfig $config) {
	
		$str = 'abstract class Base'.$config->getClassName().' extends ActiveRecord { '."\n\n";	
	
		$str.= 
		"\t".'public static function find() {'."\n".
			"\t\t".'$args = func_get_args();'."\n".
			"\t\t".'return parent::_find(ltrim(__CLASS__,\'Base\'),$args);'."\n".
		"\t".'}'."\n\n";
		
		$str.= 
		"\t".'public static function count() {'."\n".
			"\t\t".'$args = func_get_args();'."\n".
			"\t\t".'array_unshift($args,self::findCount);'."\n".
			"\t\t".'return parent::_find(ltrim(__CLASS__,\'Base\'),$args);'."\n".
		"\t".'}'."\n\n";
	
		$str.= 
		"\t".'public static function one() {'."\n".
			"\t\t".'$args = func_get_args();'."\n".
			"\t\t".'array_unshift($args,self::findOne);'."\n".
			"\t\t".'return parent::_find(ltrim(__CLASS__,\'Base\'),$args);'."\n".
		"\t".'}'."\n\n";
	
		$str.= 
		"\t".'public static function all() {'."\n".
			"\t\t".'$args = func_get_args();'."\n".
			"\t\t".'array_unshift($args,self::findAll);'."\n".
			"\t\t".'return parent::_find(ltrim(__CLASS__,\'Base\'),$args);'."\n".
		"\t".'}'."\n\n";
	
		$str.= 
		"\t".'public static function select() {'."\n".
			"\t\t".'$args = func_get_args();'."\n".
			"\t\t".'array_unshift($args,self::findSelect);'."\n".
			"\t\t".'return parent::_find(ltrim(__CLASS__,\'Base\'),$args);'."\n".
		"\t".'}'."\n\n";
		
		$str.= '}';
	
		return $str;
	
	}
	
	protected function _makeClassFile(IActiveRecordModelConfig $config) {
		
		//$str = 'require_once(\'base/base_'.Inflector::underscore($config->getClassName()).'.php\');'."\n";
		//$str.= 'class '.$config->getClassName().' extends Base'.$config->getClassName().' { '."\n\n";

    //$str = 'namespace Druid\Model;'.PHP_EOL;
    $str = 'use Druid\Storage\ActiveRecord as ActiveRecord;'.PHP_EOL.PHP_EOL;

    $str.= 'class '.$config->getClassName().' extends ActiveRecord { '."\n\n";
	
		if($this->table === true && $config->hasTable()===true) $str.= "\t".'public static $'.IActiveRecordModelConfig::table.' = \''.trim($config->getTable()).'\';'."\n\n"; 
		
		if($this->primaryKey === true && $config->hasPrimaryKey()===true) {
      /*
       * Adding composite key support.
       */
      if($config->hasCompositeKey()) {

        $str.= "\t".'public static $'.IActiveRecordModelConfig::primaryKey.' = array('."\n\n";
        foreach($config->getPrimaryKey() as $key=>$field) {
          $str.= $key!=0?"\t\t".',\''.trim($field).'\''."\n":"\t\t".'\''.trim($field).'\''."\n";
        }
        $str.= "\n\t".');'."\n\n";

      } else {
        $str .= "\t" . 'public static $' . IActiveRecordModelConfig::primaryKey . ' = \'' . trim($config->getPrimaryKey()) . '\';' . "\n\n";
      }
    }
	
		if($config->hasFields()===true) {
		
			$str.= "\t".'public static $'.IActiveRecordModelConfig::fields.' = array('."\n\n";
			foreach($config->getFields() as $key=>$field) {
				$str.= $key!=0?"\t\t".',\''.trim($field).'\''."\n":"\t\t".'\''.trim($field).'\''."\n";
			}
			$str.= "\n\t".');'."\n\n";
	
		}
	
		if($config->hasDataTypes()===true) {
		
			$str.= "\t".'public static $'.IActiveRecordModelConfig::dataTypes.' = array('."\n\n";
			$first = true;
			foreach($config->getDataTypes() as $field=>$dataType) {
				$str.= $first===true?"\t\t".'\''.trim($field).'\' => \''.trim($dataType).'\''."\n":"\t\t".',\''.trim($field).'\' => \''.trim($dataType).'\''."\n";
				$first=false;
			}
			$str.= "\n\t".');'."\n\n";
		
		}
	
		if($config->hasRequiredFields()===true) {
		
			$str.= "\t".'public static $'.IActiveRecordModelConfig::requiredFields.' = array('."\n\n";
			foreach($config->getRequiredFields() as $key=>$field) {
				$str.= $key!=0?"\t\t".',\''.trim($field).'\''."\n":"\t\t".'\''.trim($field).'\''."\n";
			}
			$str.= "\n\t".');'."\n\n";
	
		}
	
		if($config->hasDefaultValues()===true) {
		
			$str.= "\t".'public static $'.IActiveRecordModelConfig::defaultValues.' = array('."\n\n";
			$first = true;
			foreach($config->getDefaultValues() as $field=>$default) {
				$str.= $first===true?"\t\t".'\''.trim($field).'\' => \''.trim($default).'\''."\n":"\t\t".',\''.trim($field).'\' => \''.trim($default).'\''."\n";
				$first=false;
			}
			$str.= "\n\t".');'."\n\n";
		
		}
	
		if($config->hasForeignKeys()===true) {
		
			$str.= "\t".'public static $'.IActiveRecordModelConfig::foreignKeys.' = array('."\n\n";
			$first = true;
			foreach($config->getForeignKeys() as $index=>$reference) {
				$str.= $first===true?"\t\t".'\''.trim($index).'\' => array(\''.trim($reference[0]).'\')'."\n":"\t\t".',\''.trim($index).'\' => array(\''.trim($reference[0]).'\')'."\n";
				$first=false;
			}
			$str.= "\n\t".');'."\n\n";
		
		}
	
		if($config->hasBelongsTo()===true) {
		
			$str.= "\t".'public static $'.IActiveRecordModelConfig::belongsTo.' = array('."\n\n";
			foreach($config->getBelongsTo() as $key=>$model) {
				$str.= $key!=0?"\t\t".',\''.trim($model).'\''."\n":"\t\t".'\''.trim($model).'\''."\n";
			}
			$str.= "\n\t".');'."\n\n";
	
		}
	
		if($config->hasMany()===true) {
		
			$str.= "\t".'public static $'.IActiveRecordModelConfig::hasMany.' = array('."\n\n";
			foreach($config->getHasMany() as $key=>$model) {
				$str.= $key!=0?"\t\t".',\''.trim($model).'\''."\n":"\t\t".'\''.trim($model).'\''."\n";
			}
			$str.= "\n\t".');'."\n\n";
	
		}
		
		$str.= '}';
	
		return $str;

	}
	
	protected function _prepDirectory($target) {
	
		if(!file_exists($target)) {
			throw new \Exception('Specified directory '.$target.' does not exist. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.'.');
			return false;
		}

		if(is_writable($target)!==true) {
		
            if (!chmod($target, 0777)) {
				
				throw new \Exception('Specified directory '.$target.' could not be made writable. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.'.');
                return false;

            }
			
		
		}
	
	}
	
	protected function _makeBaseDirectory($target) {
		
		if(is_dir($target.'/base')) return;		
		mkdir($target.'/base');
	
	}
	
	public function generate($target,$tables=null) {
	
		$this->read($tables);
		$this->write($target);
	
	}
	
	public function read($tables=null) {
	
		$this->_generateBaseConfigs($tables);
		$this->_generateDetailedConfig();
		$this->_resolveBasicHasRelationships();
	
	}
	
	public function write($target) {
	
		$this->_prepDirectory($target);
		//$this->_makeBaseDirectory($target);
	
		foreach($this->configs as $config) {
			
			/*$stub = '<?php'."\n".$this->_makeBaseFile($config)."\n".'?>';*/
			$contents = '<?php'."\n".$this->_makeClassFile($config)."\n".'?>';
			
			//$base = $target.'/base/base_'.Inflector::underscore($config->getClassName()).'.php';
			$model = $target.'/'.$config->getClassName().'.php';
			
			//file_put_contents($base,$stub);
			file_put_contents($model,$contents);
			
			//@chmod($base,0777);
			@chmod($model,0777);
	
		}
	
	}
	
	// disable and enable methods
	
	public function disableAll() {
		
		$this->disableTable();
		$this->disableFields();
		$this->disablePrimaryKey();
		$this->disableForeignKeys();
		$this->disableDataTypes();
		$this->disableDefaultValues();
		return $this->disableRequiredFields();
	
	}
	
	public function enableAll() {
		
		$this->enableTable();
		$this->enableFields();	
		$this->enablePrimaryKey();
		$this->enableForeignKeys();
		$this->enableDataTypes();
		$this->enableDefaultValues();
		return $this->enableRequiredFields();
	
	}
	
	public function disableTable() {
		$this->table = false;
		return $this;
	}
	
	public function disableFields() {
		$this->fields = false;
		return $this;
	}
	
	public function disablePrimaryKey() {
		$this->primaryKey = false;
		return $this;
	}
	
	public function disableForeignKeys() {
		$this->foreignKeys = false;
		return $this;
	}
	
	public function disableDataTypes() {
		$this->dataTypes = false;
		return $this;
	}
	
	public function disableDefaultValues() {
		$this->defaultValues = false;
		return $this;
	}
	
	public function disableRequiredFields() {
		$this->requiredFields = false;
		return $this;
	}
	
	public function enableTable() {
		$this->table = true;
		return $this;
	}
	
	public function enableFields() {
		$this->fields = true;
		return $this;
	}
	
	public function enablePrimaryKey() {
		$this->primaryKey = true;
		return $this;
	}
	
	public function enableForeignKeys() {
		$this->foreignKeys = true;
		return $this;
	}
	
	public function enableDataTypes() {
		$this->dataTypes = true;
		return $this;
	}
	
	public function enableDefaultValues() {
		$this->defaultValues = true;
		return $this;
	}
	
	public function enableRequiredFields() {
		$this->requiredFields = true;
		return $this;
	}

}
?>