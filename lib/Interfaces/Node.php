<?php

namespace Druid\Interfaces;

use Druid\Interfaces\IList as IActiveRecordList;

//require_once('list.php');
interface Node extends IActiveRecordList {

  public function addChild(Node $pItem); // void
  public function getChild(); // Node
  public function setChild(Node $pItem); // void
  public function hasChild(); // boolean

}
?>