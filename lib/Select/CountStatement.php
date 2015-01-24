<?php

namespace Druid\Select;

use Druid\Select\SelectStatement as ActiveRecordSelectStatement;
use Druid\Select\SelectNode as ActiveRecordSelectNode;
use Druid\Select\Clause\SelectClause as ActiveRecordSelectClause;
use Druid\Select\Clause\WhereClause as ActiveRecordWhereClause;
use Druid\Select\Clause\SortClause as ActiveRecordSortClause;
use Druid\Select\Clause\GroupClause as ActiveRecordGroupClause;
use Druid\Select\Clause\HavingClause as ActiveRecordHavingClause;
use Druid\Select\Clause\LimitClause as ActiveRecordLimitClause;

//require_once('select_statement.php');
class CountStatement extends ActiveRecordSelectStatement {

  public function __construct(ActiveRecordSelectNode $pRootNode,$pOptions=null) {

    parent::__construct($pRootNode,$pOptions);

  }

  protected function _init() {

    $this->_selectClause = new ActiveRecordSelectClause();
    $this->_whereClause = new ActiveRecordWhereClause();
    $this->_havingClause = new ActiveRecordHavingClause();
    $this->_sortClause = new ActiveRecordSortClause();
    $this->_groupClause = new ActiveRecordGroupClause();
    $this->_limitClause = new ActiveRecordLimitClause();

    $primaryKeyField = $this->_root->getConfig()->getPrimaryKey();
    $selectDynamic = array('total'=>'COUNT(DISTINCT t'.$this->_root->getUnique().'.'.$primaryKeyField.')');

    $this->_selectClause->selectDynamic($this->_root,$selectDynamic);

  }


  protected function _applyAll(ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParent=null) {

    $this->_applyFrom($pNode,$pParent);

    //$this->_applySelect($pNode);
    $this->_applyFilter($pNode,$pParent);
    //$this->_applySort($pNode);
    //$this->_applyGroup($pNode);
    //$this->_applyHaving($pNode);

  }

}