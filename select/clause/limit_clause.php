<?php
class ActiveRecordLimitClause {	

	private $_limit;
	private $_offset;
	
	public function __construct() {
	}
	
	public function toSql() {
	
		if(!is_null($this->_limit) && !is_null($this->_offset)) {
			$str = $this->_offset.','.$this->_limit;
		} else if(!is_null($this->_limit)) {
			$str = $this->_limit;
		} else {
			$str='';
		}
		
		return $str;
	
	}
	
	public function setLimit($pLimit) {
	
		if(is_numeric($pLimit)) {
		
			$this->_limit = $pLimit;
		
		}
	
	}
	
	public function setOffset($pOffset) {
	
		if(is_numeric($pOffset)) {
			
			$this->_offset = $pOffset;
			
		}
	
	}

}
?>