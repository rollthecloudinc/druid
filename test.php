<?php

require 'vendor/autoload.php';

/*
 * @todo:
 * This should be auto-magically handled at the very least.
 * Best case scenario is place model files in namespaces to
 * use composer. However, that comes with several complications currently.
 */
Druid\Storage\ActiveRecord::loadModelFiles();

//loadProduct();
testCompositeKey();

function testCompositeKey() {

  $items = BedCatalogProductWebsite::one([':id'=>['product_id'=>35,'website_id'=>1]]);

  $items->each(function() {
    echo "product: {$this->product_id} | website: {$this->website_id}".PHP_EOL;
  });

}

function loadProduct() {

  $configurables = BedCatalogProductEntity::all(['type_id'=> 'configurable', 'limit'=> 5]);

  $configurables->each(function() {

    $name = '';

    $this->bed_catalog_product_entity_varchars->each(function() use (&$name) {
      if($this->bed_eav_attribute->attribute_code == 'name' && $this->bed_core_store->store_id == 0) {
        $name = $this->value;
        return false;
      }
    });

    echo $name.PHP_EOL;

    // Load child products.
    $children = BedCatalogProductEntity::all(
      ['include'=> 'bed_catalog_product_super_links' ],
      ['parent_id'=> $this->entity_id, 'require'=> true ]
    );

    $children->each(function() {

      $name = '';

      $this->bed_catalog_product_entity_varchars->each(function() use (&$name) {
        if($this->bed_eav_attribute->attribute_code == 'name' && $this->bed_core_store->store_id == 0) {
          $name = $this->value;
          return false;
        }
      });

      echo $name.PHP_EOL;

    });

  });

}

