<?php

namespace Druid\Interfaces;

use Druid\Cascade\CascadeNode as ActiveRecordCascadeNode;

interface CascadeAction {

  public function doSomething(ActiveRecordCascadeNode $node,$nodes=null); // bool

}