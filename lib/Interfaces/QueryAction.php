<?php

namespace Druid\Interfaces;

interface QueryAction {

  public function doAction(\PDO $db,\PDOStatement $stmt);

}
?>