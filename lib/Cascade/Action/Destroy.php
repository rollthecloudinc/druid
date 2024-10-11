<?php

namespace Druid\Cascade\Action;

use Druid\Core\Query\Query as ActiveRecordQuery;
use Druid\Interfaces\CascadeAction as IActiveRecordCascadeAction;
use Druid\Cascade\CascadeNode as ActiveRecordCascadeNode;
use Druid\Storage\ActiveRecord as ActiveRecord;

//require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/cascade_action.php');
//require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../core/query/query.php');
class Destroy implements IActiveRecordCascadeAction {

	protected $queries;
	
	public function __construct() {
		
		$this->queries = array();
		
	}
	
	public function getQueries() {
	
		return $this->queries;
		
	}
	
	public function query(PDO $db) {
	
		$total = count($this->queries);		
		if($total==0) return;
		
		for($i=($total-1);$i>=0;$i--) {
		
			foreach($this->queries[$i] as $query) {
			
				try {

					ActiveRecord::query($query,$db,ActiveRecordQuery::DELETE,$this);
				
				} catch(\Exception $e) {
				
					return false;
				
				}
				
			}
		
		}
		
		return true;		
	
	}

	public function doSomething(
		ActiveRecordCascadeNode $node
		,$nodes=null
	) {
	
		$nodes = is_null($nodes)?array():$nodes;
		
		$countNodes = count($nodes); 
		$query = new ActiveRecordQuery();
		
		if($countNodes>1) {	

			$field = $node->getConfig()->getRelatedField($nodes[0]->getConfig());	
			
			if(empty($field)) {
				throw new \Exception('Unable to resolve relationship between '.$node->getConfig()->getClassName().' and '.$nodes[0]->getConfig()->getClassName().' inside '.__CLASS__.' class method '.__METHOD__ .' line '.__LINE__.'.');
				return false;
			}
			
			$subquery = $this->_makeSubquery($nodes,$query);
			$sql = 'DELETE FROM `'.$node->getConfig()->getTable().'` WHERE `'.$field.'` IN  ('.$subquery.')';
		
		} else if($countNodes==1) {
		
			$field = $node->getConfig()->getRelatedField($nodes[0]->getConfig());
		
			if(empty($field)) {
				throw new \Exception('Unable to resolve relationship between '.$node->getConfig()->getClassName().' and '.$nodes[0]->getConfig()->getClassName().' inside '.__CLASS__.' class method '.__METHOD__.' line '.__LINE__.'.');
				return false;
			}
		
			$sql = 'DELETE FROM `'.$node->getConfig()->getTable().'` '.$this->_makeWhereClause(array($node,$nodes[0]),$query,false,$field);
		
		} else {
		
			$sql = 'DELETE FROM `'.$node->getConfig()->getTable().'` '.$this->_makeWhereClause(array($node),$query,false);
		
		}
		
		$query->setSql($sql);
		
		if(array_key_exists($countNodes,$this->queries)) {
			$this->queries[$countNodes][] = $query; 
		} else {
			$this->queries[$countNodes] = array($query); 
		}
		
		return true;
		
	
	}
	
	protected function _makeSubQuery($nodes,ActiveRecordQuery $query) {
	
		$str = '';	
		$p=null;
	
		foreach($nodes as $key=>$c) {
			
			if(!is_null($p)) {
			
				$str.= ' INNER JOIN `'.$c->getConfig()->getTable().'` AS t'.$key.' ON t'.($key-1).'.`'.$p->getConfig()->getRelatedField($c->getConfig()).'` = t'.$key.'.`'.$c->getConfig()->getRelatedField($p->getConfig()).'`';
				$p = $c;
			
			} else {
			
				$str.= 'SELECT DISTINCT t'.$key.'.`'.$c->getConfig()->getPrimaryKey().'` FROM `'.$c->getConfig()->getTable().'` AS t'.$key;
				$p = $c;
			
			}
			
		}	
	
		return $str.$this->_makeWhereClause($nodes,$query);
	
	
	}
	
	protected function _makeWhereClause($nodes,ActiveRecordQuery $query,$subquery=true,$field=null) {
	
		$countNodes = (count($nodes)-1);
		$filters = array();
	
		for($i=$countNodes;$i>=0;$i--) {
		
			if($nodes[$i]->hasRecords()===true) {
				
				$placeholders = array();
				$primaryKey = $nodes[$i]->getConfig()->getPrimaryKey();
				foreach($nodes[$i]->getRecords() as $record) {
					$query->addData($record->getProperty($primaryKey));
					$placeholders[] = '?';
				}
				
				if(!empty($placeholders)) {
				
					$field = !is_null($field) && $i==$countNodes?$field:$primaryKey;
				
					if($subquery===true) {
						
						if(count($placeholders)==1) {
							$filters[] = '(t'.$i.'.`'.$field.'` = ?)';
						} else {
							$filters[] = '(t'.$i.'.`'.$field.'` IN ('.implode(',',$placeholders).'))';
						}
						
					} else {
						
						if(count($placeholders)==1) {
							$filters[] = '(`'.$field.'` = ?)';
						} else {
							$filters[] = '(`'.$field.'` IN ('.implode(',',$placeholders).'))';
						}
						
					}
				}
				
			}
		
		}
		
		return empty($filters)?'':' WHERE '.implode(' AND ',$filters);
	
	}

}
?>