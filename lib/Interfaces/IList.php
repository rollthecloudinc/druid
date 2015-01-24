<?php

namespace Druid\Interfaces;

interface IList {

  public function addSibling(IList $pItem); // void
  public function getSibling(); // IList
  public function setSibling(IList $pItem); // void
  public function hasSibling(); // boolean

}
?>