<?php

namespace Druid\Storage\Entity;

use Druid\Interfaces\DataEntity as IActiveRecordDataEntity;

class DataEntityIterator implements \Iterator {

	protected $properties;
	protected $position;
	protected $data;

	public function __construct(IActiveRecordDataEntity $data,$properties) {
		
		$this->position = 0;
		$this->data = $data;
		$this->properties = $properties;
	
	}

    function rewind() {
        $this->position = 0;
    }

    function current() {
       	$property = $this->properties[$this->position];
       	return $this->data->getProperty($property);
    }

    function key() {
        return $this->properties[$this->position];
    }

    function next() {
        ++$this->position;
    }

    function valid() {
        return isset($this->properties[$this->position]);
    }

}