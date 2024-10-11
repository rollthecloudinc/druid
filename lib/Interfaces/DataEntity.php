<?php
namespace Druid\Interfaces;

interface DataEntity extends \IteratorAggregate {

  public function getProperty($pName);
  public function setProperty($pName,$pValue);
  public function hasProperty($pName);
  public function hasChanged($pName);
  public function addRecord($pPropertyName,DataEntity $pRecord,$pArrayByDefault=false);
  public function getRecord($pPropertyName,$pPrimaryKey,$pField);
  public function removeProperty($pPropertyName);
  public function getData();
  public function cast();

}