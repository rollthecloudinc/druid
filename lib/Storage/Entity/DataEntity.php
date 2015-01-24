<?php

namespace Druid\Storage\Entity;

use Druid\Interfaces\DataEntity as IActiveRecordDataEntity;
use Druid\Storage\Collection\Collection as ActiveRecordCollection;

/*require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/data_entity.php');
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../collection/collection.php');*/
require_once('data_entity_iterator.php');

class DataEntity implements IActiveRecordDataEntity {

  private $_data;

  public function __construct() {

    $this->_data = array();

  }

  public function setProperty($pName,$pValue) {

    if($this->hasProperty($pName)===true) {

      array_unshift($this->_data[$pName],$pValue);

    } else {

      $this->_data[$pName] = array($pValue);

    }

  }

  public function getProperty($pName) {

    if($this->hasProperty($pName)===true) {

      return $this->_data[$pName][0];

    }

  }

  public function hasProperty($pName) {

    return array_key_exists($pName,$this->_data);

  }

  public function getRecord($pPropertyName,$pPrimaryKey,$pField) {

    if(!array_key_exists($pPropertyName,$this->_data)) return false;

    if(!($this->_data[$pPropertyName][0] instanceof IActiveRecordDataEntity) && $this->_data[$pPropertyName][0] instanceof arrayaccess) {

      foreach($this->_data[$pPropertyName][0] as $record) {

        if($record->$pField == $pPrimaryKey) return $record;

      }

    } else {

      if($this->_data[$pPropertyName][0]->$pField == $pPrimaryKey) {

        return $this->_data[$pPropertyName][0];

      }

    }

    return false;

  }

  public function addRecord($pPropertyName,IActiveRecordDataEntity $pRecord,$pArrayByDefault=false) {

    if(array_key_exists($pPropertyName,$this->_data)===true) {

      if(!($this->_data[$pPropertyName][0] instanceof IActiveRecordDataEntity) && $this->_data[$pPropertyName][0] instanceof arrayaccess) {

        $this->_data[$pPropertyName][0][] = $pRecord;

      } else {

        //$this->_data[$pPropertyName][0] = array($this->_data[$pPropertyName][0]);
        $this->_data[$pPropertyName][0] = new ActiveRecordCollection($this->_data[$pPropertyName][0]);

      }

    } else {

      // for a hasMany relationship with only one item. Otehrwise if something
      // only has one item in its result set but has a hasMany relationship
      // a array would not exists which seems wrong.
      if($pArrayByDefault === true) {
        $this->_data[$pPropertyName] = array(new ActiveRecordCollection($pRecord));
      } else {
        $this->_data[$pPropertyName] = array($pRecord);
      }

    }

  }

  public function removeProperty($pPropertyName) {

    if($this->hasProperty($pPropertyName)===true) {

      unset($this->_data[$pPropertyName]);
      return true;

    } else {

      return false;

    }

  }

  public function getData() {

    return $this;

  }

  public function hasChanged($pName) {

    if($this->hasProperty($pName)===true) {

      return count($this->_data[$pName])>1?true:false;

    }


  }

  public function cast() {


    foreach($this->_data as $key=>$data) {

      $value = $this->_data[$key][0];
      $this->_data[$key] = array($value);

    }


  }


  public function getIterator() {
    return new ActiveRecordDataEntityIterator($this,array_keys($this->_data));
  }



}