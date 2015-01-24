<?php

namespace Druid\Select;

use Druid\Interfaces\FindConfig as IActiveRecordFindConfig;
use Druid\Core\Inflector\Inflector as Inflector;
use Druid\Core\Query\Query as ActiveRecordQuery;
use Druid\Core\Model\ModelConfig as ActiveRecordModelConfig;
use Druid\Core\Model\DynamicModel as ActiveRecordDynamicModel;
use Druid\Select\SelectNode as ActiveRecordSelectNode;
use Druid\Select\Find\FindConfig as ActiveRecordFindConfig;
use Druid\Select\Clause\SelectClause as ActiveRecordSelectClause;
use Druid\Select\Clause\WhereClause as ActiveRecordWhereClause;
use Druid\Select\Clause\SortClause as ActiveRecordSortClause;
use Druid\Select\Clause\GroupClause as ActiveRecordGroupClause;
use Druid\Select\Clause\HavingClause as ActiveRecordHavingClause;
use Druid\Select\Clause\LimitClause as ActiveRecordLimitClause;
use Druid\Storage\ActiveRecord as ActiveRecord;

//require_once('select_node.php');
//require_once('find/find_config.php');
//require_once('clause/select_clause.php');
//require_once('clause/where_clause.php');
//require_once('clause/sort_clause.php');
//require_once('clause/group_clause.php');
//require_once('clause/having_clause.php');
//require_once('clause/limit_clause.php');
//require_once( str_replace('//','/',dirname(__FILE__).'/') .'../core/query/query.php');
//require_once( str_replace('//','/',dirname(__FILE__).'/') .'../core/model/dynamic_model.php');
class SelectStatement {

  protected static $_selectPrimaryKey = true;

  protected $_root;
  protected $_options;

  protected $_fromClause = '';
  protected $_selectClause;
  protected $_whereClause;
  protected $_havingClause;
  protected $_sortClause;
  protected $_groupClause;
  protected $_limitClause;

  protected $_sql = '';

  protected $_fromData;

  // added to make magical primary key replacement "simple"
  protected $_nodeLog;

  public function __construct(ActiveRecordSelectNode $pRootNode,$pOptions=null) {

    $this->_root = $pRootNode;
    $this->_options = is_null($pOptions)?array():$pOptions;

    $this->_fromData = array();
    $this->_nodeLog = array();

    $this->_init();

  }

  public function getNode() {
    return $this->_root;
  }

  protected function _init() {

    $this->_selectClause = new ActiveRecordSelectClause();
    $this->_whereClause = new ActiveRecordWhereClause();
    $this->_havingClause = new ActiveRecordHavingClause();
    $this->_sortClause = new ActiveRecordSortClause();
    $this->_groupClause = new ActiveRecordGroupClause();
    $this->_limitClause = new ActiveRecordLimitClause();

    /*
    * When root node references a Dynamic model (select statement) include its columns and bind data
    */
    if($this->_root->getConfig() instanceof ActiveRecordDynamicModel) {
      $this->_selectClause->select($this->_root,$this->_root->getConfig()->getFields(true));
      $this->_fromData = array_merge($this->_fromData,$this->_root->getConfig()->getBindData());
    }

    $this->_applyLimit();

  }

  public function toSql() {

    if(empty($this->_sql)) {
      $this->makeSelectTree();
      $this->makeQuery();
      $this->_buildSql();
    }
    return $this->_sql;

  }

  public function query(PDO $pDb) {

    $sql = $this->toSql();
    $bindData = $this->getBindData();
    $query =  new ActiveRecordQuery($sql,$bindData);

    try {

      return ActiveRecord::query($query,$pDb,ActiveRecordQuery::SELECT,$this);


    } catch(\Exception $e) {

      throw new \Exception('Unable to execute SQL query in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__);
      return false;

    }

  }

  public function getBindData() {

    return array_merge($this->_selectClause->getSelectData(),$this->_fromData,$this->_whereClause->getFilterData(),$this->_havingClause->getHavingData());

  }

  public function getFromClause() {

    return $this->_fromClause;

  }

  public function getSelectClause() {

    return $this->_selectClause;

  }

  public function getWhereClause() {

    return $this->_whereClause;

  }

  public function getSortClause() {

    return $this->_sortClause;

  }

  public function getGroupClause() {

    return $this->_groupClause;

  }

  public function getHavingClause() {

    return $this->_havingClause;

  }

  public function setLimit($pLimit) {

    $this->_limitClause->setLimit($pLimit);

  }

  public function setOffset($pOffset) {

    $this->_limitClause->setOffset($pOffset);

  }

  public function makeQuery(ActiveRecordSelectNode $pNode=null,ActiveRecordSelectNode $pParent=null,$pRunner=1) {

    $node = is_null($pNode)?$this->_root:$pNode;

    /*
    * 3/6/10 - Added condition to skip this segment if the root node is a dynamic mode.
    *
    * Segment added: && $pRunner != 2
    */
    if(!is_null($pParent) && $pParent->getConfig() instanceof ActiveRecordDynamicModel && $pRunner != 2) {
      // momentary fix for subqueries that have includes.
      // otherwise the include is not seen
      if($node->hasSibling()) {
        $this->_applyAll($node->getSibling(),$pParent);
      }
      return;
    }

    $this->_applyAll($node,$pParent);

    if($node->hasChild()) {
      $this->makeQuery($node->getChild(),$node,($pRunner+1));
    }

    if($node->hasSibling()) {
      $this->makeQuery($node->getSibling(),$pParent,$pRunner);
    }

  }

  public function makeSelectTree(ActiveRecordSelectNode $pNode=null,$pRunner=0) {

    $node = is_null($pNode)?$this->_root:$pNode;

    if(!is_null($this->_options) && !empty($this->_options)) {

      $findConfig = new ActiveRecordFindConfig(array_shift($this->_options));

      if($findConfig->hasInclude()===true) {

        $modelIncludes = $findConfig->getInclude();

        foreach($modelIncludes as $modelInclude) {

          /*
          * modifed to accept arrays and FindConfig objects
          */
          if(!empty($this->_options)) {
            if($this->_options[0] instanceof IActiveRecordFindConfig) {
              $includeFindConfig = $this->_options[0];
            } else {
              $includeFindConfig = new ActiveRecordFindConfig($this->_options[0]);
            }
          } else {
            $includeFindConfig = new ActiveRecordFindConfig(array());
          }

          if($modelInclude instanceof SelectStatement) {

            $modelInclude->toSql();
            $includeModel = new ActiveRecordDynamicModel($modelInclude);
            //$includeModel = $modelInclude->getNode()->getConfig();
            $this->_fromData = array_merge($this->_fromData,$modelInclude->getBindData());

          } else {

            $includeModelClassName = Inflector::classify($modelInclude);
            $includeModel = ActiveRecordModelConfig::getModelConfig($includeModelClassName);
            if($includeModel === false) {
              $includeModel = new ActiveRecordModelConfig($includeModelClassName);
            }

          }

          $includeNode = new ActiveRecordSelectNode($includeModel,$includeFindConfig);

          if($modelInclude instanceof SelectStatement) {
            $this->_selectSubqueryFields($includeNode,$modelInclude);
            $includeNode->addChild($modelInclude->getNode());
          }

          $node->addChild($includeNode);
          $this->makeSelectTree($includeNode,++$pRunner);

        }

      }

    }

  }

  protected function _selectSubqueryFields(ActiveRecordSelectNode $dynamic,SelectStatement $select) {

    $fields = $select->getSelectClause()->getFields();
    $nodes =  $select->getSelectClause()->getNodes();

    foreach($fields as $key=>$columns) {
      $cols = array();
      foreach($columns as $col=>$alias) {
        $cols[] = $col;
      }
      $this->_selectClause->select($nodes[$key],$cols,false,'t'.$nodes[$key]->getUnique().'_','t'.$dynamic->getUnique());
    }

  }

  public function makeFromClause(ActiveRecordSelectNode $pNode=null,ActiveRecordSelectNode $pParent=null,$pRunner=1) {

    $node = is_null($pNode)?$this->_root:$pNode;

    $this->_applyFrom($node,$pParent);

    if($node->hasChild()===true) {

      $this->makeFromClause($node->getChild(),$node,($pRunner+1));

    }

    if($node->hasSibling()===true) {

      $this->makeFromClause($node->getSibling(),$pParent,$pRunner);

    }

  }

  public function makeSelectClause(ActiveRecordSelectNode $pNode=null,ActiveRecordSelectNode $pParent=null,$pRunner=1) {

    $node = is_null($pNode)?$this->_root:$pNode;

    $this->_applySelect($node);

    if($node->hasChild()) {
      $this->makeSelectClause($node->getChild(),$node,($pRunner+1));
    }

    if($node->hasSibling()) {
      $this->makeSelectClause($node->getSibling(),$pParent,$pRunner);
    }



  }

  public function makeWhereClause(ActiveRecordSelectNode $pNode=null,ActiveRecordSelectNode $pParent=null,$pRunner=1) {

    $node = is_null($pNode)?$this->_root:$pNode;

    $this->_applyFilter($node);

    if($node->hasChild()) {
      $this->makeWhereClause($node->getChild(),$node,($pRunner+1));
    }

    if($node->hasSibling()) {
      $this->makeWhereClause($node->getSibling(),$pParent,$pRunner);
    }

  }

  public function makeSortClause(ActiveRecordSelectNode $pNode=null,ActiveRecordSelectNode $pParent=null,$pRunner=1) {

    $node = is_null($pNode)?$this->_root:$pNode;

    $this->_applySort($node);

    if($node->hasChild()) {
      $this->makeSortClause($node->getChild(),$node,($pRunner+1));
    }

    if($node->hasSibling()) {
      $this->makeSortClause($node->getSibling(),$pParent,$pRunner);
    }

  }

  public function makeGroupClause(ActiveRecordSelectNode $pNode=null,ActiveRecordSelectNode $pParent=null,$pRunner=1) {

    $node = is_null($pNode)?$this->_root:$pNode;

    $this->_applyGroup($node);

    if($node->hasChild()) {
      $this->makeGroupClause($node->getChild(),$node,($pRunner+1));
    }

    if($node->hasSibling()) {
      $this->makeGroupClause($node->getSibling(),$pParent,$pRunner);
    }

  }

  public function makeHavingClause(ActiveRecordSelectNode $pNode=null,ActiveRecordSelectNode $pParent=null,$pRunner=1) {

    $node = is_null($pNode)?$this->_root:$pNode;

    $this->_applyHaving($node);

    if($node->hasChild()) {
      $this->makeHavingClause($node->getChild(),$node,($pRunner+1));
    }

    if($node->hasSibling()) {
      $this->makeHavingClause($node->getSibling(),$pParent,$pRunner);
    }

  }

  public function replaceClassWithAlias(ActiveRecordSelectNode $pNode=null,ActiveRecordSelectNode $pParent=null,$pRunner=1) {

    $node = is_null($pNode)?$this->_root:$pNode;

    $this->_applyAliasReplacement($node);

    if($node->hasChild()) {
      $this->replaceClassWithAlias($node->getChild(),$node,($pRunner+1));
    }

    if($node->hasSibling()) {
      $this->replaceClassWithAlias($node->getSibling(),$pParent,$pRunner);
    }

  }

  protected function _addLogNode(ActiveRecordSelectNode $pNode) {

    $unique = $pNode->getUnique();
    $this->_nodeLog[$unique] = $pNode;

  }

  protected function _applyAll(ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParent=null) {

    $this->_addLogNode($pNode);

    $this->_applyFrom($pNode,$pParent);

    $this->_applySelect($pNode);
    $this->_applyFilter($pNode,$pParent);
    $this->_applySort($pNode);
    $this->_applyGroup($pNode);
    $this->_applyHaving($pNode);

  }

  protected function _applyFrom(ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParent=null) {

    if($pNode->getFindConfig()->hasAssociation()===true && !is_null($pParent)) {
      $this->_applyAssociation($pNode,$pParent);
      return;
    }

    // 3/7/10: changed LEFT to LEFT OUTER
    $joinType = $pNode->getFindConfig()->hasRequireJoin() && $pNode->getFindConfig()->getRequireJoin()===false?'LEFT OUTER':'INNER';
    $joinType = $pNode->getFindConfig()->hasJoinType()?$pNode->getFindConfig()->getJoinType():$joinType;

    if(is_null($pParent)===false && $pParent->getConfig()->hasBelongsToAndHasMany()===true && $pParent->getConfig()->getRelatedField($pNode->getConfig())=='') {
      $pParent = $this->_handleManyToMany($pNode,$pParent,$joinType);
    }

    if(!is_null($pParent)) {

      $this->_fromClause.= ' '.$joinType.' JOIN '.$pNode->getConfig()->getTable().' AS t'.$pNode->getUnique().' ON t'.$pParent->getUnique().'.'.$pParent->getConfig()->getRelatedField($pNode->getConfig()).' = t'.$pNode->getUnique().'.'.$pNode->getConfig()->getRelatedField($pParent->getConfig()).' ';

      // add model filter to the mix
      $this->_applyModelFilter($pNode,$pParent);

    } else {
      $this->_fromClause.= ' '.$pNode->getConfig()->getTable().' AS t'.$pNode->getUnique().' ';
    }

  }

  // should make sure indexes exists
  protected function _applyAssociation(ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParent=null) {

    // 3/7/10: changed LEFT to LEFT OUTER
    $joinType = $pNode->getFindConfig()->hasRequireJoin() && $pNode->getFindConfig()->getRequireJoin()===false?'LEFT OUTER':'INNER';
    $joinType = $pNode->getFindConfig()->hasJoinType()?$pNode->getFindConfig()->getJoinType():$joinType;

    $associations = array();

    foreach($pNode->getFindConfig()->getAssociation() as $parent=>$current) {

      $strLeft = 't'.$pParent->getUnique().'.`'.$parent.'`';
      $strRight = 't'.$pNode->getUnique().'.`'.$current.'`';
      $strOperator = '=';
      $boolReverse = false;

      /*
      * Special case none field comparisions
      *
      * t1.column IS NULL
      * t1.column = 22 (int)
      * t1.status = 'active' (string)
      * t1.status BETWEEN 2 AND 5 (sql)
      *
      * NOTE: NULL and (sql) only override the base equality comparision. All
      * other (int) and (string) comparisions are made using the standard
      * equality operator.
      *
      * For now these seem like the most likly scenarios. It seems highly
      * unlikely to begin adding an override for the operator considering
      * the performance hit in most cases.
      *
      * @TODO: Make sure binding within FROM clause is allowed.
      * If not allowed the int and string values will be embedded
      */
      if(strcasecmp('NULL',$parent) == 0) {
        $strOperator = 'IS NULL';
        $strLeft = '';
        $boolReverse = true;
      } else if(strpos($parent,' (int)') !== false) {
        $this->_fromData[] = (int) str_replace(' (int)','',$parent);
        $strLeft = '?';
        $boolReverse = true;
      } else if(strpos($parent,' (string)') !== false) {
        $strLeft = '"'.str_replace(' (string)','',$parent).'"';
        //$strLeft = '?';
        $boolReverse = true;
      } else if(strpos($parent,' (sql)') !== false) {
        $strLeft = str_replace(' (sql)','',$parent);
        $strOperator = '';
        $boolReverse = true;
      }

      if(strcasecmp('NULL',$current) == 0) {
        $strOperator = 'IS NULL';
        $strRight = '';
      } else if(strpos($current,' (int)') !== false) {
        $this->_fromData[] = str_replace(' (int)','',$current);
        $strRight = '?';
      } else if(strpos($current,' (string)') !== false) {
        $this->_fromData[] = sprintf("'%s'",str_replace(' (string)','',$current));
        $strRight = '?';
      } else if(strpos($current,' (sql)') !== false) {
        $strRight = str_replace(' (sql)','',$current);
        $strOperator = '';
      }

      /*
      * Reverses format
      */
      if($boolReverse === true) {
        $associations[] = "$strRight $strOperator $strLeft";
      } else {
        $associations[] = "$strLeft $strOperator $strRight";
      }

    }

    if(!is_null($pParent)) {

      $sql = ' '.$joinType.' JOIN '.$pNode->getConfig()->getTable().' AS t'.$pNode->getUnique().' ON '.implode(' AND ',$associations).' ';
      $this->_fromClause.= $sql;

      // add model filter to the mix
      $this->_applyModelFilter($pNode,$pParent);

    } else {
      $sql = ' '.$pNode->getConfig()->getTable().' AS t'.$pNode->getUnique().' ';
      $this->_fromClause.= $sql;
    }

  }

  protected function _applySelect(ActiveRecordSelectNode $pNode) {

    $fields = $this->_determineNativeFieldsToSelect($pNode);
    $this->_selectClause->select($pNode,$fields,true);

    if($pNode->getFindConfig()->hasDynamic()) {
      $this->_selectClause->selectDynamic($pNode,$pNode->getFindConfig()->getDynamic(),false);
    }

  }

  protected function _determineNativeFieldsToSelect(ActiveRecordSelectNode $pNode) {

    $config = $pNode->getConfig();
    $find = $pNode->getFindConfig();
    $primaryKey = $config->getPrimaryKey();

    $fields = $config->hasFields()?$config->getFields():array();

    if($find->hasSelect()) {

      // replace magical primary key constant
      $select = $find->getSelect();
      $searchForMagicalPrimaryKey = array_search(ActiveRecordFindConfig::id,$select);

      if($searchForMagicalPrimaryKey !== false) {
        $select[$searchForMagicalPrimaryKey] = $primaryKey;
      }

      $fields = $find->getSelect()?array_intersect($fields,$select):array();

    }

    $empty = $find->hasEmpty()===true?$find->getEmpty():false;
    if($empty===true) {
      return $find->hasSelect()===true?$fields:array();
    }

    if(!empty($fields) && $find->hasNonSelect()) {

      // can't force a deselect of the primary key
      // no need to handle magical primary key here
      $fields = array_diff($fields,$find->getNonSelect());

    }

    if(!empty($primaryKey) && self::$_selectPrimaryKey===true && !in_array($primaryKey,$fields)) {
      $fields[] = $primaryKey;
    }

    return $fields;

  }

  protected function _applyFilter(ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParent=null) {

    $find = $pNode->getFindConfig();

    /*
    * KILL if has child or is child - included in from clause
    * Models default filter
    * apply filter by default if exists but allow find config to override this default behavior
    */
    if($pNode->hasChild() === false && $pParent === null && $pNode->getConfig()->hasDefaultFilter() === true && ($find->hasIgnoreModelFilter() === false || $find->getIgnoreModelFilter() === true)) {
      $this->_whereClause->addFilter($pNode,$pNode->getConfig()->getDefaultFilter(),true);
    }

    if($find->hasConditionMap()===true && $find->hasCondition()===true) {

      $this->_whereClause->addCondition($pNode,$find->getConditionMap(),$find->getCondition());

    }

    if($find->hasFilter()) {

      $this->_whereClause->addFilter($pNode,$find->getFilter(),true);

    }

    if($find->hasMagicalFilter()) {

      $this->_whereClause->addFilter($pNode,$find->getMagicalFilter(),true);

    }

  }

  protected function _applySort(ActiveRecordSelectNode $pNode) {

    $find = $pNode->getFindConfig();

    if($find->hasSort()) {

      $this->_sortClause->addSort($pNode,$find->getSort());

    }

  }

  protected function _applyGroup(ActiveRecordSelectNode $pNode) {

    $find = $pNode->getFindConfig();

    if($find->hasGroup()) {

      $this->_groupClause->addGroup($pNode,$find->getGroup());

    }

  }

  protected function _applyHaving(ActiveRecordSelectNode $pNode) {

    $find = $pNode->getFindConfig();

    if($find->hasHaving()) {

      $this->_havingClause->addHaving($pNode,$find->getHaving());

    }

  }

  protected function _applyAliasReplacement(ActiveRecordSelectNode $pNode) {

    // this is causing subqueries to be wrong
    $className = $pNode->getConfig()->getClassName();

    if(!empty($className)) {
      $this->_sql = str_replace($className.'.','t'.$pNode->getUnique().'.',$this->_sql);
    }

  }

  protected function _applyMagicalPrimaryKeyReplacement() {

    /*
    * Seems like the most benefical way to handle replacement of
    * of magical primary key and mapping it to the correct node. Otherwise
    * if two tables where joined and a condition for the first included
    * references to both then each magical key would be mapped to the
    * table that the find config belongs. Instead we are going to logically
    * match each occurance and map it to correct node manually to get this correct.
    * However, for efficiency sake use strpos() to make sure we even need to begin
    * doing this. strpos is relativly cheap while what is being done below may not.
    */
    if(strpos($this->_sql,IActiveRecordFindConfig::id)===false) return;

    $matches = array();
    preg_match_all('/t[0-9]+?(.|_)'.IActiveRecordFindConfig::id.'/',$this->_sql,$matches);

    $matches = array_unique($matches[0]);
    foreach($matches as $key=>$match) {

      $pieces = explode(',',preg_replace('/^(.*?)([0-9]+)(.|_)(.*?)$/','$1,$2,$3,$4',$match));
      $unique = (int) $pieces[1];

      if(isset($this->_nodeLog[$unique])) {

        $pieces[3] = $this->_nodeLog[$unique]->getConfig()->getPrimaryKey();
        $this->_sql = str_replace($match,implode('',$pieces),$this->_sql);

      }

    }

  }

  protected function _handleManyToMany(ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParent,$pJoinType='INNER') {

    $belongsToAndHasMany = $pParent->getConfig()->getBelongsToAndHasMany();
    foreach($belongsToAndHasMany as $reference) {

      $relatedClassName = $pNode->getConfig()->getClassName();
      $relatedModelName = Inflector::pluralize(Inflector::tableize($relatedClassName));

      if(is_array($reference)===true && count($reference)>1 && strcmp($relatedModelName,array_shift($reference))==0) {

        foreach($reference as $through) {

          $throughModelConfig = ActiveRecordModelConfig::getModelConfig(Inflector::classify($through));
          $throughNode = new ActiveRecordSelectNode($throughModelConfig);
          $this->_fromClause.= ' '.$pJoinType.' JOIN '.$throughNode->getConfig()->getTable().' AS t'.$throughNode->getUnique().' ON t'.$pParent->getUnique().'.'.$pParent->getConfig()->getRelatedField($throughNode->getConfig()).' = t'.$throughNode->getUnique().'.'.$throughNode->getConfig()->getRelatedField($pParent->getConfig()).' ';

          // add model filter to the mix... I think
          $this->_applyModelFilter($throughNode,$pParent);

          $pParent =  $throughNode;

        }

        break;

      }

    }

    return $pParent;

  }

  protected function _applyLimit() {

    $find = $this->_root->getFindConfig();

    if($find->hasLimit()) {
      $this->setLimit($find->getLimit());
    }

    if($find->hasOffset()) {
      $this->setOffset($find->getOffset());
    }


  }

  protected function _applyModelFilter(ActiveRecordSelectNode $pNode,ActiveRecordSelectNode $pParent) {

    // add model filter into mix
    $boolNodeModelFilter = false;
    $boolParentModelFilter = false;

    $objWhereClause = new ActiveRecordWhereClause();

    if($pParent->getConfig()->hasDefaultFilter() === true && ($pParent->getFindConfig()->hasIgnoreModelFilter() === false || $pParent->getFindConfig()->getIgnoreModelFilter() === true)) {
      $boolParentModelFilter = true;
      $objWhereClause->addFilter($pParent,$pParent->getConfig()->getDefaultFilter(),true);
    }

    if($pNode->getConfig()->hasDefaultFilter() === true && ($pNode->getFindConfig()->hasIgnoreModelFilter() === false || $pNode->getFindConfig()->getIgnoreModelFilter() === true)) {
      $boolNodeModelFilter = true;
      $objWhereClause->addFilter($pNode,$pNode->getConfig()->getDefaultFilter(),true);
    }

    if($boolNodeModelFilter === true || $boolParentModelFilter === true) {
      $this->_fromClause.= ' AND '.$objWhereClause->toSql();
      $this->_fromData = array_merge($this->_fromData,$objWhereClause->getFilterData());
    }

  }

  protected function _buildSql() {

    $sql = '';
    $findConfig = $this->_root->getFindConfig();

    $selectSql 		= 		$this->_selectClause->toSql();
    $fromSql 		= 		$this->_fromClause;
    $whereSql	 	= 		$this->_whereClause->toSql();
    $groupSql 		= 		$this->_groupClause->toSql();
    $havingSql 		= 		$this->_havingClause->toSql();
    $sortSql	 	= 		$this->_sortClause->toSql();
    $limitSql		=		$this->_limitClause->toSql();

    $sqlCalcFoundRows = $findConfig->getCount() === true?' SQL_CALC_FOUND_ROWS ':'';

    $sql.= !empty($selectSql)?'SELECT '.$sqlCalcFoundRows.$selectSql:'';
    $sql.= !empty($fromSql)?' FROM '.$fromSql:'';
    $sql.= !empty($whereSql)?' WHERE '.$whereSql:'';
    $sql.= !empty($groupSql)?' GROUP BY '.$groupSql:'';
    $sql.= !empty($havingSql)?' HAVING '.$havingSql:'';
    $sql.= !empty($sortSql)?' ORDER BY '.$sortSql:'';
    $sql.= !empty($limitSql)?'  LIMIT '.$limitSql:'';

    $this->_sql= $sql;

    $this->replaceClassWithAlias();
    $this->_applyMagicalPrimaryKeyReplacement();

  }

}