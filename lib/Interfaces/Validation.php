<?php

namespace Druid\Interfaces;

interface Validation {

	public function clear();
	public function clearMemory();
	public function convertFromSql($pDataType);
	public function validate($pValue,$pType,$pInMemoryName='');
	public function invalid(); // boolean
	public function fullReport(); // array
	public function allMessages(); // array

}
?>