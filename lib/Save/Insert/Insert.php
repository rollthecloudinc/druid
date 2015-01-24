<?php

namespace Druid\Save\Insert;

require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../core/model/model_config.php');
class Insert implements Countable {

  const insertTransform = 'save';

  protected $sibling;

  protected $record;
  protected $records;

  protected $data;
  protected $structure;

  public function __construct(ActiveRecord $pRecord=null) {


    if(!is_null($pRecord) && $this->checkRequiredFields($pRecord)===true) {
      $this->record = $pRecord;
    }

    $this->records = array();
    $this->data = array();
    $this->structure = array();

  }

  public function setSibling(ActiveRecordInsert $pSibling) {

    $this->sibling = $pSibling;

  }

  public function getSibling() {

    return $this->sibling;

  }

  public function hasSibling() {

    return is_null($this->sibling)?false:true;

  }

  public function add(ActiveRecord $pRecord,$valid=false) {

    if(is_null($this->record)) {


      if($this->checkRequiredFields($pRecord)===true) {
        $this->record = $pRecord;
      }


    } else if($this->isCompatible($pRecord)===true) {

      $this->records[] = $pRecord;


    } else {

      if($this->hasSibling()===true) {

        $this->getSibling()->add($pRecord);

      } else {

        $this->setSibling(new ActiveRecordInsert($pRecord));

      }

    }

  }

  public function isCompatible(ActiveRecord $pRecord) {

    if($this->classCompatible($pRecord)===false) {

      return false;

    }


    if($this->structureCompatible($pRecord)===false) {

      return false;

    }

    return true;

  }

  public function classCompatible(ActiveRecord $pRecord) {

    $class = get_class($this->record);
    return $pRecord instanceof $class?true:false;

  }

  public function structureCompatible(ActiveRecord $pRecord) {

    return $this->fieldsCompatible($pRecord);

  }

  public function fieldsCompatible(ActiveRecord $pRecord) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($this->record));

    if($config->hasFields()===true) {

      foreach($config->getFields() as $field) {

        if(($pRecord->hasProperty($field)===true && $this->record->hasProperty($field)===false) || ($this->record->hasProperty($field)===true && $pRecord->hasProperty($field)===false)) {

          return false;

        }

      }

    }

    return true;

  }

  public function toSql() {

    if(is_null($this->record)) return '';

    // validate data - fk,uk,data type and whatever else

    $fields = $this->collectFields();

    $structure = array();

    foreach($this->structure as $str) {

      $structure[] = '('.implode(',',$str).')';

    }

    return 'INSERT INTO '.$this->getTable().' ('.implode(',',$fields).') VALUES '.implode(',',$structure);

  }

  public function getData() {

    return $this->data;

  }

  public function getStructure() {

    return $this->structure;

  }

  public function collectFields() {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($this->record));
    $fields = array();

    if($config->hasFields()===true) {

      foreach($config->getFields() as $field) {

        if($this->record->hasProperty($field)===true) {

          $fields[] = $field;
          $this->collectData($field);

        }

      }

    }

    return $fields;


  }

  public function getTable() {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($this->record));
    return $config->getTable();

  }

  public function collectData($pField) {

    $records = $this->records;
    $records[] = $this->record;

    $config = ActiveRecordModelConfig::getModelConfig(get_class($this->record));
    $allTransforms = $config->hasTransformations()===true?$config->getTransformations():array();
    $fieldTransform = (!empty($allTransforms) && array_key_exists($pField,$allTransforms) && array_key_exists(self::insertTransform,$allTransforms[$pField]))?($allTransforms[$pField][self::insertTransform]):(array());

    foreach($records as $key=>$record) {

      if(!array_key_exists($key,$this->data)) {
        $this->data[$key] = array();
        $this->structure[$key] = array();
      }

      if(!empty($fieldTransform)) {

        $this->structure[$key][] = $this->applyFieldTransform($record,$pField,$key,$fieldTransform,$allTransforms);

      } else {

        $this->data[$key][] = $record->getProperty($pField);
        $this->structure[$key][] = '?';

      }

    }


  }

  public function applyFieldTransform(ActiveRecord $pRecord,$pField,$pKey,$pFieldTransform,$pAllTransform) {

    $statement = is_array($pFieldTransform)?$pFieldTransform[0]:$pFieldTransform;
    $transform = is_array($pFieldTransform)?$pFieldTransform:array();

    /*
    * Extract post query transformation identified keywords self::, php:: and $this->
    * at the beginning of transform string. Transformations that fall under these specifications
    * will be applied after the query has been executed during the collection step. This is a great
    * way to cast 0 or 1 fields to true booleans or unserialize a serialized field.
    */
    $modifier = null;
    if(strpos($statement,'$this->') === 0 || strpos($statement,'self::') === 0 || strpos($statement,'php::') === 0) {
      list($callback,$statement) = explode(' ',preg_replace('/(\$this->|self::|php::)([a-zA-Z_][a-zA-Z0-9_]*?)\((.*?)\)$/',"$1#$2 $3",$statement),2);
      $modifier = explode('#',$callback,2);
    }

    $matches = array();
    preg_match_all('/\$[1-9][0-9]*?|\{.*?\}/',$statement,$matches,PREG_OFFSET_CAPTURE);

    if(array_key_exists(0,$matches) && !empty($matches[0])) {

      $offset = 0; $args = array();
      foreach($matches[0] as $match) {

        if(strcmp(substr($match[0],0,1),'$')==0) {

          $index = (int) substr($match[0],1);
          $index;

          if(array_key_exists($index,$pFieldTransform)) {

            if($modifier === null) $this->data[$pKey][] = $pFieldTransform[$index];

            // arguments passed to callback tranform
            $args[] = $pFieldTransform[$index];

            $statement = substr_replace($statement,'?',($match[1]+$offset),strlen($match[0]));
            $offset-= (strlen($match[0])-1);

          }

        } else {

          $property = substr($match[0],1,(strlen($match[0])-2));

          if(strcmp($property,'this')==0) {
            $property = $pField;
          }

          if($pRecord->hasProperty($property)===true) {

            if(strcmp($pField,$property)!=0 && array_key_exists($property,$pAllTransform) && array_key_exists(self::insertTransform,$pAllTransform[$property])) {

              $nestedStatement = $this->applyFieldTransform($pRecord,$property,$pKey,$pAllTransform[$property][self::insertTransform],$pAllTransform);
              $statement = preg_replace('/\{'.$property.'\}/',$nestedStatement,$statement,1);
              $offset+= (strlen($nestedStatement)-strlen($match[0]));


            } else {

              if($modifier === null) $this->data[$pKey][] = $pRecord->getProperty($property);

              // arguments passed to transform callback
              $args[] = $pRecord->getProperty($property);

              $statement = substr_replace($statement,'?',($match[1]+$offset),strlen($match[0]));
              $offset-= (strlen($match[0])-1);

            }

          }


        }

      }

    }

    // PHP/callback modifier
    if($modifier !== null) {

      switch($modifier[0]) {
        case 'self::':
          $this->data[$pKey][] = call_user_func_array(get_class($pRecord)."::{$modifier[1]}",$args);
          return '?';

        case '$this->':
          $this->data[$pKey][] = call_user_func_array(array($pRecord,$modifier[1]),$args);
          return '?';

        default:
          $this->data[$pKey][] =  call_user_func_array($modifier[0],$args);
          return '?';
      }
    }

    return $statement;

  }

  public function checkRequiredFields(ActiveRecord $pRecord) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($pRecord));

    if($config->hasRequiredFields()) {

      $undeclaredRequiredProperties = array();

      foreach($config->getRequiredFields() as $requiredColumn) {

        if($pRecord->hasProperty($requiredColumn)===false) {

          $undeclaredRequiredProperties[] = $requiredColumn;

        }

      }

      if(!empty($undeclaredRequiredProperties)) {

        throw new Exception('Fields {'.implode(',',$undeclaredRequiredProperties).'} are required to insert a '.$config->getClassName().'. Exception generated in '.__CLASS__.' on line '.__LINE__);
        return false;

      }

    }

    return true;

  }

  public function getRecord() {

    return $this->record;

  }

  public function count() {

    if(is_null($this->record)) return 0;
    return (count($this->records)+1);

  }

  public function getRecords() {

    $records = $this->records;
    $records[] = $this->record;
    return $records;

  }

}
?>