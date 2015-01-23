<?php

/*$db = new PDO('mysql:dbname=druid_magento;host=127.0.0.1','root','-Password1');

$result = $db->query('show create table bed_catalog_product_entity;');

while($row=$result->fetch(PDO::FETCH_ASSOC)) {

  $def = $row['Create Table'];
  break;

}

$matches = array();

preg_match_all('/CONSTRAINT\s`.*?`\sFOREIGN KEY\s\(`([a-zA-Z1-9_]*?)`\)\sREFERENCES\s`([a-zA-Z1-9_]*?)`\s\(`([a-zA-Z1-9_]*?)`\)/i',$def,$matches);
array_shift($matches);

print_r($matches);*/

include 'main.php';

// @todo: autoloader and PSR-4 for EVERYTHING!!!
include 'model/bed_catalog_product_entity.php';

$record = BedCatalogProductEntity::one([':id'=>1704,'include'=> ['bed_catalog_product_entity_varchars'] ], [ 'include'=> ['bed_eav_attribute'] ]);

$record->bed_catalog_product_entity_varchars->loop(function() {
  if($this->bed_eav_attribute->attribute_code == 'name') {
    echo $this->value . PHP_EOL;
  }
});

