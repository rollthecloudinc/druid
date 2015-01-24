<?php

namespace Druid\Save;

require_once('insert/insert.php');
require_once('update/update.php');
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../core/query/action/set_primary_key_action.php');
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../core/query/action/cast_action.php');
class Save {

  protected $insert;
  protected $update;

  protected $db;

  protected $updateQueries;
  protected $insertQueries;

  protected $records;

  public function __construct() {

    $this->_init();
    $args = func_get_args();

    if(!empty($args)) $this->records = $args;

  }

  protected function _init() {

    $this->insert = new ActiveRecordInsert();
    $this->update = new ActiveRecordUpdate();

    $this->updateQueries = array();
    $this->insertQueries = array();

  }

  public function getInsert() {

    return $this->insert;

  }

  public function getUpdate() {

    return $this->update;

  }

  public function addInsert(ActiveRecord $pRecord) {

    $this->insert->add($pRecord);

  }

  public function addUpdate(ActiveRecord $pRecord) {

    $this->update->add($pRecord);

  }

  public function addRecord(ActiveRecord $pRecord) {

    if(is_null($this->records)) {
      $this->records = array($pRecord);
    } else {
      $this->records[] = $pRecord;
    }

  }

  public function query(PDO $db) {

    if(!empty($this->records)) {

      $this->db = $db;
      $this->save($this->records);

      $this->insert();
      $this->update();

      $this->updateQuery($db);
      $this->insertQuery($db);

      return true;

    } else {

      throw new Exception('Nothing to save. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' inside method '.__METHOD__);
      return false;

    }


  }

  protected function updateQuery(PDO $db) {


    foreach($this->updateQueries as $query) {

      try {

        ActiveRecord::query($query,$db,ActiveRecordQuery::UPDATE,$this);

      } catch(Exception $e) {

        throw new Exception('Unable to execute SQL query in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__);
        return false;

      }

    }

    return true;

  }


  protected function insertQuery(PDO $db) {


    foreach($this->insertQueries as $query) {

      try {

        ActiveRecord::query($query,$db,ActiveRecordQuery::INSERT,$this);

      } catch(Exception $e) {

        throw new Exception('Unable to execute SQL query in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__);
        return false;

      }

    }

    return true;

  }

  protected function singleInsertQuery(ActiveRecordInsert $insert,ActiveRecord $record,$pk) {

    $sql = $insert->toSql();
    $data = $insert->getData();

    $query = new ActiveRecordQuery($sql,$data[0],new ActiveRecordSetPrimaryKeyAction($record));

    try {

      // $query->showQuery();
      if($query->query($this->db)) {
        return true;
      } else {
        return false;
      }


    } catch(Exception $e) {

      throw new Exception('Unable to execute SQL query in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__);
      return false;

    }

  }

  protected function update(ActiveRecordUpdate $update=null) {

    if(is_null($update)) $update = $this->update;

    $sql = $update->toSql();
    $data = $update->getData();

    if(!empty($sql)) {
      $query = new ActiveRecordQuery($sql,array(),new ActiveRecordCastAction($update->getRecords()));

      foreach($data as $value) {
        $query->addData($value);
      }

      $this->updateQueries[] = $query;
    }

    if($update->hasSibling()===true) $this->update($update->getSibling());

  }

  protected function insert(ActiveRecordInsert $insert=null) {

    if(is_null($insert)) $insert = $this->insert;

    $sql = $insert->toSql();
    $data = $insert->getData();

    if(!empty($sql)) {

      if(count($insert)==1) {
        $query = new ActiveRecordQuery($sql,array(),new ActiveRecordSetPrimaryKeyAction($insert->getRecord()));
      } else {
        $query = new ActiveRecordQuery($sql,array(),new ActiveRecordCastAction($insert->getRecords()));
      }

      foreach($data as $row) {
        foreach($row as $value) {
          if(is_array($row)) {
            $query->addData($value);
          }
        }
      }

      $this->insertQueries[] = $query;
    }

    if($insert->hasSibling()===true) $this->insert($insert->getSibling());

  }

  public function add(ActiveRecord $pRecord) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($pRecord));
    $primaryKey = $config->getPrimaryKey();

    if($pRecord->hasProperty($primaryKey)===false  || $pRecord->hasChanged($primaryKey)===true) {

      $this->addInsert($pRecord);

    } else {

      $this->addUpdate($pRecord);

    }

  }

  protected function save($record,$parent=null) {

    //if(!is_null($parent)) {

    $this->addDependencies($record,$parent);

    //}

    foreach($record as $ar)
      $this->handleSave($ar);

    $this->saveHasOne($record,$parent);
    $this->saveHasMany($record,$parent);
    $this->saveManyToMany($record,$parent);

  }

  protected function addDependencies($record,$parent=null) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($record[0]));
    $parentConfig = $parent?ActiveRecordModelConfig::getModelConfig(get_class($parent[0])):null;

    if($config->hasBelongsTo()) {

      foreach($config->getBelongsTo() as $model) {

        $modelClassName = Inflector::classify($model);
        $relatedClassConfig = ActiveRecordModelConfig::getModelConfig($modelClassName);
        $field = $config->getRelatedField($relatedClassConfig);
        $rField = $relatedClassConfig->getRelatedField($config);

        foreach($record as $ar) {

          // insert any dependencies that have not been saved (lacking primary key)
          if($ar->hasProperty($model) && !is_null($ar->getProperty($model)) && $ar->getProperty($model)->hasProperty($relatedClassConfig->getPrimaryKey())===false) {
            $save = new ActiveRecordSave($ar->getProperty($model));
            $save->query($this->db);
          }

          // resolving parent model object to fk on child
          if(!is_null($parentConfig) && ($ar->hasProperty($model)===false || is_null($ar->getProperty($model)))  && strcmp($parentConfig->getClassName(),$modelClassName)==0) {

            $copy = $this->makeFlatCopy($parent[0]);
            $ar->setProperty($model,$copy);

            if($ar->hasProperty($field)===false) {
              $ar->setProperty($field,$copy->getProperty($rField));
            }

            // changed fk by changing related model
          } else if($ar->hasProperty($model)===true && !is_null($ar->getProperty($model)) && $ar->hasProperty($config->getPrimaryKey())===true && $ar->hasChanged($model)===true) {

            $ar->setProperty($field,$ar->getProperty($model)->getProperty($rField));

          } else if($ar->hasProperty($config->getPrimaryKey())===false && $ar->hasProperty($model)===true && !is_null($ar->getProperty($model))) {

            //echo "<p>",$ar->getProperty($model)->getProperty($rField),"</p>";
            $ar->setProperty($field,$ar->getProperty($model)->getProperty($rField));

          }

        }

      }

    }

  }


  protected function saveHasOne($record,$parent=null) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($record[0]));

    if($config->hasOne()) {

      foreach($config->getHasOne() as $model) {

        $modelClass = Inflector::classify($model);

        foreach($record as $ar) {

          if($ar->hasProperty($model) && !is_null($ar->getProperty($model))) {

            $this->save(array($ar->getProperty($model)),array($ar));

          }

        }

      }

    }

  }


  protected function saveHasMany($record,$parent=null) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($record[0]));

    if($config->hasMany()) {

      foreach($config->getHasMany() as $model) {

        $modelClass = Inflector::classify($model);

        foreach($record as $ar) {

          if($ar->hasProperty($model) && !is_null($ar->getProperty($model))) {

            $this->save($ar->getProperty($model),array($ar));

          }

        }

      }

    }

  }


  protected function saveManyToMany($record,$parent=null) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($record[0]));

    if($config->hasBelongsToAndHasMany()) {

      foreach($config->getBelongsToAndHasMany() as $models) {

        $model = is_array($models)?$models[0]:$models;
        $modelClass = Inflector::classify($model);

        foreach($record as $ar) {

          if($ar->hasProperty($model) && !is_null($ar->getProperty($model))) {

            $through = ActiveRecordModelConfig::getModelConfig(Inflector::classify($models[1]));

            $this->handleManyToManySave($ar->getProperty($model),$ar,$through);

          }

        }

      }

    }

  }

  protected function handleManyToManySave($records,$parent,$through) {

    if(count($records)==0) return;

    $parentConfig = ActiveRecordModelConfig::getModelConfig(get_class($parent));
    $config = ActiveRecordModelConfig::getModelConfig(get_class($records[0]));

    $throughClass = $through->getClassName();

    $r1 = $through->getRelatedField($parentConfig);
    $r2 = $parentConfig->getRelatedField($through);
    $method = 'get'.Inflector::pluralize($throughClass);

    $parentCopy = $this->makeFlatCopy($parent);

    foreach($records as $ar) {

      if($ar->hasProperty($config->getPrimaryKey())===false) {

        $ar->save();

        $middleRecord = new $throughClass();
        $middleRecord->setProperty(Inflector::underscore($config->getClassName()),$this->makeFlatCopy($ar));
        $middleRecord->setProperty(Inflector::underscore($parentConfig->getClassName()),$parentCopy);
        $this->save(array($middleRecord));

      } else {

        // check to make sure record isn't already associated
        // if it isn't then we insert the middle record.
        // otherwise we continue to save the current record ar
        $find = array('limit'=>1);
        $find[$r1] =  $parent->getProperty($r2);
        $record = $ar->$method($find);

        if(count($record)==0) {
          $middleRecord = new $throughClass();
          $middleRecord->setProperty(Inflector::underscore($config->getClassName()),$this->makeFlatCopy($ar));
          $middleRecord->setProperty(Inflector::underscore($parentConfig->getClassName()),$parentCopy);
          $this->save(array($middleRecord));
        }

        $this->save(array($ar),array($parent));
      }

    }

  }

  protected function makeFlatCopy(ActiveRecord $record) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($record));
    $class = $config->getClassName();
    $copy = new $class();

    $this->resolveForeignKeys($copy,$record);
    $this->resolveBelongsToAssociations($copy,$record);
    $this->copyOverModelFields($copy,$record);

    return $copy;

  }

  protected function resolveForeignKeys(ActiveRecord $copy,ActiveRecord $record) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($copy));

    if($config->hasForeignKeys()) {

      foreach($config->getForeignKeys() as $index=>$reference) {

        $relatedClassName = is_array($reference)?array_shift($reference):$reference;
        $relatedConfig = ActiveRecordModelConfig::getModelConfig($relatedClassName);
        $model = Inflector::underscore(Inflector::singularize($relatedClassName));

        $relatedKey = is_array($reference) && !empty($reference)?array_shift($reference):$relatedConfig->getPrimaryKey();

        if($record->hasProperty($model) && $record->getProperty($model) instanceof $relatedClassName) {

          $copy->setProperty($index,$record->getProperty($model)->getProperty($relatedKey));

        }

      }

    }

  }

  protected function resolveBelongsToAssociations(ActiveRecord $copy,ActiveRecord $record) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($copy));

    if($config->hasBelongsTo()) {

      foreach($config->getBelongsTo() as $model) {

        if(!$copy->hasProperty($model) && $record->hasProperty($model)) {

          $relatedClassName = Inflector::classify(Inflector::singularize($model));
          $relatedConfig = ActiveRecordModelConfig::getModelConfig($relatedClassName);

          $relatedKey = $relatedConfig->getPrimaryKey();
          $index = Inflector::foreign_key($config->getClassName(),true,$relatedKey);

          if($record->getProperty($model) instanceof $relatedClassName) {

            $copy->setProperty($index,$record->getProperty($model)->getProperty($relatedKey));

          }

        }

      }

    }

  }

  protected function copyOverModelFields(ActiveRecord $copy,ActiveRecord $record) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($copy));

    if($config->hasFields()) {

      foreach($config->getFields() as $field) {

        if($copy->hasProperty($field)=== false && $record->hasProperty($field)) {

          $copy->setProperty($field,$record->getProperty($field));

        }

      }

    }

  }

  /*
  * Need to see if item is not active and has record. If this is true we need
  * to isolate theinsert of this object so that we can grab the primary key.
  * So that it can be added to the dependencies.
  */
  protected function handleSave(ActiveRecord $record) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($record));
    $primaryKey = $config->getprimaryKey();
    $isoSave = false;

    if($record->hasProperty($primaryKey)===false) {

      if($isoSave===false && $config->hasOne()===true) {

        foreach($config->getHasOne() as $model) {

          if($record->hasProperty($model) && !is_null($record->getProperty($model))) $isoSave = true;

        }

      }

      if($isoSave===false && $config->hasMany()===true) {

        foreach($config->getHasMany() as $model) {

          if($record->hasProperty($model) && !is_null($record->getProperty($model))) $isoSave = true;

        }

      }

      if($isoSave===false && $config->hasBelongsToAndHasMany()===true) {

        foreach($config->getBelongsToAndHasMany() as $model) {

          if($record->hasProperty($model[0]) && !is_null($record->getProperty($model[0]))) $isoSave = true;

        }

      }

    }

    if($isoSave===true) {

      $this->singleInsertQuery(new ActiveRecordInsert($record),$record,$primaryKey);

    } else {

      $this->add($record);

    }

  }

}