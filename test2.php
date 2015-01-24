<?php

require 'vendor/autoload.php';

Druid\Storage\ActiveRecord::loadModelFiles();

$products = BedCatalogProductEntity::all(
  ['limit'=> 30, 'include'=> 'bed_catalog_product_super_link' ]//,
//[ 'sort'=> ['product_id'=>'DESC'] ]
);

$products->each(function() {
  /*$this->bed_catalog_product_entity_varchars->each(function() {
    if($this->bed_eav_attribute->attribute_code == 'name') {
      echo $this->value.PHP_EOL;
    }
  });*/
  //echo $this->entity_id.PHP_EOL;
});

//require 'main.php';