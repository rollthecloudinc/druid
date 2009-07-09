<?php
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../interface/validation.php');
class ActiveRecordValidation implements IActiveRecordValidation {
	
	protected $_validated;
	protected $_feedback;
	protected $_messages;
	protected $_values;
	protected $_cursor;
	
	protected $_memory=array(
		'feedback'=>array()
		,'messages'=>array()
		,'validated'=>false
		,'value'=>''
	);
	
	protected $_sqlLengthConversions = 
	array(
		'str'=>array(
			'medium'=>1000
			,'large'=>2000
			,'mini'=>1000		
		)
		,'int'=>array(
			'tiny'=>1
			,'small'=>2
			,'medium'=>3
			,'big'=>8
		)
	);
	
	public function __construct() {
		$this->clear();
	}
	
	public function Invalid() {
		return array_keys($this->_validated,false);
	}
	
	public function valid($pName=null) {
		return $pName?$this->_validated[$pName]:$this->_memory['validated'];
	}
	
	public function value($pName=null) {
		return $pName?$this->_values[$pName]:$this->_memory['value'];
	}
	
	public function fullReport() {
		return $this->_validated;
	}
	
	public function allMessages() {
		return $this->_messages;
	}
	
	public function messages($pName=null) {
		return $pName?$this->_messages[$pName]:$this->_memory['messages'];
	}
	
	public function feedback($pName=null) {
		return $pName?$this->_feedback[$pName]:$this->_memory['feedback'];
	}
	
	public function clear() {
		$this->_validated=array();
		$this->_feedback=array();
		$this->_messages = array();
	}
	
	public function clearMemory() {
		$this->_memory=array(
			'feedback'=>array()
			,'messages'=>array()
			,'validated'=>false
		);
	}
	
	protected function _addToValidated($pValid,$pCursor=null) {
	
		$pCursor = $pCursor?$pCursor:$this->_cursor;
		
		if($pCursor) {
			$this->_validated[$pCursor] = $pValid;
		}
		
		$this->_memory['validated'] = $pValid;
	
	}
	
	protected function _addToValues($pValid,$pCursor=null) {
	
		$pCursor = $pCursor?$pCursor:$this->_cursor;
		
		if($pCursor) {
			$this->_values[$pCursor] = $pValid;
		}
		
		$this->_memory['value'] = $pValid;
	
	}
	
	protected function _addMessage($pMessage,$pCursor=null) {
	
		$pCursor = $pCursor?$pCursor:$this->_cursor;
		
		if($pCursor && array_key_exists($pCursor,$this->_messages)) {
		
			if(is_array($pMessage)) {
				$this->_messages[$pCursor] = array_merge($this->_messages[$pCursor],$pMessage);
			} else {
				$this->_messages[$pCursor][] = $pMessage;
			}
			
		} else if($pCursor) {
		
			$this->_messages[$pCursor] = is_array($pMessage)?$pMessage:array($pMessage);
		
		} 
		
		if(is_array($pMessage)) {
			$this->_memory['messages'] = array_merge($this->_memory['message'],$pMessage);
		} else {
			$this->_memory['messages'][] = $pMessage;
		}
	
	}
	
	protected function _addFeedback($pFeedback,$pCursor=null) {
	
		$pCursor = $pCursor?$pCursor:$this->_cursor;
		
		if($pCursor && array_key_exists($pCursor,$this->_feedback)) {
		
			if(is_array($pFeedback)) {
				$this->_feedback[$pCursor] = array_merge($this->_feedback[$pCursor],$pFeedback);
			} else {
				$this->_feedback[$pCursor][] = $pFeedback;
			}
			
		} else if($pCursor) {
		
			$this->_feedback[$pCursor] = is_array($pFeedback)?$pFeedback:array($pFeedback);
		
		}
		
		if(is_array($pFeedback)) {
			$this->_memory['feedback'] = array_merge($this->_memory['feedback'],$pFeedback);
		} else {
			$this->_memory['feedback'][] = $pFeedback;
		}
	
	}
	
	public function validate($pValue,$pType,$pName=null,$pMessages=array()) {
	
		if(is_array($pType)) {
			$type = array_shift($pType);
		} else {
			$pType = $this->convertFromSql($pType);
			$type = array_shift($pType);
		}
	
		switch($type) {
		
			case 'str':
			
				return $this->validStr($pValue,$pType,$pName,$pMessages);
				
			case 'int':
				return $this->validInt($pValue,$pType,$pName,$pMessages);
				
			case 'time':
				return $this->validTime($pValue,$pType,$pName,$pMessages);

			case 'date':
				return $this->validDate($pValue,$pType,$pName,$pMessages);
				
			case 'enum':
				return $this->validEnum($pValue,$pType,$pName,$pMessages);
				
			case 'list':
				return $this->validList($pValue,$pType,$pName,$pMessages);
				
			case 'timestamp':
				return $this->validTimestamp($pValue,$pType,$pName,$pMessages);
		
		}
		
		
	}
	
	public function validStr($pValue,$pExt=null,$pName=null,$pMessages=array()) {
		
		$this->clearMemory();
		$this->_cursor = $pName;
		
		$valid = $this->_initStrValidation($pValue,$pExt,$pMessages);
		
		$this->_addToValidated($valid);
		$this->_addToValues($pValue);
		return $valid;
	
	}
	
	public function validInt($pValue,$pExt,$pName=null,$pMessages=array()) {
		
		$this->clearMemory();
		$this->_cursor = $pName;
		
		$valid = $this->_initIntValidation($pValue,$pExt,$pMessages);
		
		$this->_addToValidated($valid);
		$this->_addToValues($pValue);
		return $valid;
	
	}
	
	public function validDate($pValue,$pExt=10,$pName=null,$pMessages=array()) {
		
		$this->clearMemory();
		$this->_cursor = $pName;
		
		$valid = $this->_initDateValidation($pValue,$pExt,$pMessages);
		
		$this->_addToValidated($valid);
		$this->_addToValues($pValue);
		return $valid;
	
	}
	
	public function validTime($pValue,$pExt,$pName=null,$pMessages=array()) {
		
		$this->clearMemory();
		$this->_cursor = $pName;
		
		$valid = $this->_initTimeValidation($pValue,$pExt,$pMessages);
		
		$this->_addToValidated($valid);
		$this->_addToValues($pValue);
		return $valid;
	
	}
	
	public function validEnum($pValue,$pExt,$pName=null,$pMessages=array()) {
		
		$this->clearMemory();
		$this->_cursor = $pName;
		
		$valid = $this->_initEnumValidation($pValue,$pExt,$pMessages);
		
		$this->_addToValidated($valid);
		$this->_addToValues($pValue);
		return $valid;
	
	}
	
	public function validList($pValue,$pExt,$pName=null,$pMessages=array()) {
		
		$this->clearMemory();
		$this->_cursor = $pName;
		
		$valid = $this->_initListValidation($pValue,$pExt,$pMessages);
		
		$this->_addToValidated($valid);
		$this->_addToValues($pValue);
		return $valid;
	
	}
	
	public function validTimestamp($pValue,$pExt,$pName=null,$pMessages=array()) {
		
		$this->clearMemory();
		$this->_cursor = $pName;
		
		$valid = $this->_initTimestampValidation($pValue,$pExt,$pMessages);
		
		$this->_addToValidated($valid);
		$this->_addToValues($pValue);
		return $valid;
	
	}
	
	protected function _initStrValidation($pValue,$pExt,$pMessages=array()) {
	
   		if(!$this->_isString($pValue)) {
   			$this->_addMessage(is_array($pMessages) && array_key_exists(0,$pMessages)?$pMessages[0]:'Value is not a string');
      		return false;
    	}
    	
    	if(!$pExt || is_null($pExt[0])) {
    		return true;
    	}
    	
    	$stringMaxLength = array_shift($pExt);
    	$stringLength = strlen($pValue);
    	
    	if(!$this->_stringLengthIsLessThanOrEqualTo($pValue,$stringMaxLength)) {
    		$this->_addMessage(is_array($pMessages) && array_key_exists(1,$pMessages)?$pMessages[1]:"String is {$stringLength} characters long which is longer then $stringMaxLength characters");
    		return false;
    	}
    	
    	if(!$pExt) {
    		return true;
    	}
    	
    	if($unmatched = $this->_matchAll($pValue,$pExt)) {
    		$this->_addMessage(is_array($pMessages) && array_key_exists(2,$pMessages)?$pMessages[2]:$pValue.' did not match the following patterns '.implode(',',$unmatched));
    		return false;
    	}
	
		return true;
	
	}
	
	protected function _initIntValidation($pValue,$pExt,$pMessages=array()) {
	
		
		if(!$this->_isNumeric($pValue)) {
			$this->_addMessage('Value is not a integer');
			return false;
		}
		
		if(!$pExt) {
			return true;
		}
      	   	
      	$bits = array_shift($pExt);
      	$sign = array_key_exists(0,$pExt) && $this->_isString($pExt[0])?array_shift($pExt):'signed';  
		
		$bitRange = $this->_generateBitsRange($bits,$sign);
		
		if(!$this->_withinRange($pValue,$bitRange['min'],$bitRange['max'])) {
			$this->_addMessage("Value is not between {$bitRange['min']} and {$bitRange['max']}");
			return false;
		}
           
   		if($pExt && !in_array($pValue,$pExt)) {
   			$this->_addMessage('Value '.$pValue.' is not within list of allowed integers ('.implode(',',$pExt).')');
      		return false;
      	}
	
		return true;
	
	}
	
	protected function _initDateValidation($pValue,$pExt,$pMessages=array()) {
	
		/*$regEx = '/^[1-9][0-9]{3}-(01|02|03|04|05|06|07|08|09|10|11|12)-(01|02|03|04|05|06|07|08|09|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31)$/';
	
  		if(!$this->_initStrValidation($pValue,array('str',11,$regEx))) {
  			$this->_addMessage($pValue.' is not represented in the proper date format YYYY-MM-DD and/or within 1000-01-01 - 9999-12-31 as it must');
          	return false;
     	}*/
	
		return true;
	
	}
	
	protected function _initTimeValidation($pValue,$pExt,$pMessages=array()) {
	
		$regex = '/^(01|02|03|04|05|06|07|08|09|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24):[0-5][0-9]:[0-5][0-9]$/';
		
		if(!$this->_initStrValidation($pValue,array('str',8,$regex))) {
         	$this->_addMessage($pValue.' is not represented in the proper time format HH:MM:SS as it must');
          	return false;
     	}
     	
     	return true;
	
	}
	
	protected function _initEnumValidation($pValue,$pExt,$pMessages=array()) {
	
    	if(!$this->_withinArray($pValue,$pExt)) {
          	$this->_addMessage($pValue.' is not within the list of allowed values ('.implode(',',$pExt).')');
         	return false;
     	}
	
		return true;
	
	}
	
	protected function _initListValidation($pValue,$pExt,$pMessages=array()) {
	
    	if(!$this->_withinArray($pValue,$pExt)) {
          	$this->_addMessage($pValue.' is not within the list of allowed values ('.implode(',',$pExt).')');
         	return false;
     	}
	
		return true;
	
	}
	
	protected function _initTimestampValidation($pValue,$pExt,$pMessages=array()) {
	
		return true;
	
	}
	
	protected function _isNumeric($pValue) {
		return is_numeric($pValue);
	}
	
	protected function _isString($pValue) {
		return is_string($pValue);
	}
	
	protected function _generateBitsRange($pBits=null,$pSign=null) {
	
		$minValue = 0;
 		$maxValue = pow(2,($pBits*8))-1;
           
        if($pSign && !strcasecmp('unsigned',$pSign)==0) {
      		$minValue = ceil($maxValue/2)*-1;
          	$maxValue = floor($maxValue/2);
  		}
  		
  		return array('min'=>$minValue,'max'=>$maxValue);
	
	}
	
	protected function _withinRange($pValue,$pMin,$pMax) {
	
		return ($pValue<$pMin || $pValue>$pMax)?false:true;
	
	}
	
	protected function _stringLengthIsLessThanOrEqualTo($pValue,$pLength) {
		return strlen($pValue)<=$pLength;	
	}
	
	protected function _matchAll($pValue,$pRegEx=array()) {
	
		$matches = array();
     	foreach($pRegEx as $key=>$value) {
     		if(preg_match($value,$pValue)) {
            	$matches[] = $value;
           	}
     	}   
                
        return array_diff($pRegEx,$matches);
	
	}
	
	protected function _withinArray($pValue,$pArray) {
      	
      	return in_array($pValue,$pArray);
      	
	}
	
	public function convertFromSql($pType) {
	
		if((stripos($pType,'int')===false && stripos($pType,'char')===false && stripos($pType,'text')===false) || !is_string($pType)) { return $pType; }

		$sign = stripos($pType,'unsigned')===false?' signed':' unsigned';

		$type = str_replace($sign,'',$pType);

		$primitive = strripos($type,'int')===false?'str':'int';
	
		if($pos = stripos($type,'int')) {
			$length = substr($type,0,$pos);
		} else if($pos = stripos($type,'text')) {
			$length = substr($type,0,$pos);
		} else if(preg_match('/^.*?\([0-9]*?\).*?$/',$type)) {
			$length = (int) preg_replace('/^.*?\(([0-9]*?)\).*?$/','$1',$type);
		} else {
			$length = null;
		}

		if(is_string($length)) {
			$length = $this->_sqlLengthConversions[$primitive][strtolower($length)];
		}

		return strcmp($primitive,'int')==0?array($primitive,$length,substr($sign,1)):array($primitive,$length);
	
	}

}
?>