<?php

namespace Druid\Storage\Collection;

class CollectionIterator implements \Iterator {

  protected $records;
  protected $cursor;

  public function __construct($records) {

    $this->records = $records;
    $this->cursor = 0;

  }

  public function rewind() {

    $this->cursor=0;

  }

  public function current() {

    return $this->records[$this->cursor];

  }

  public function key() {
    return $this->cursor;
  }

  public function next() {
    ++$this->cursor;
  }

  public function valid() {
    return isset($this->records[$this->cursor]);
  }

}
?>