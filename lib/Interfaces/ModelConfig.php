<?php

namespace Druid\Interfaces;

interface ModelConfig {

  const defaultPrimaryKeyName = 'id';

  const table					= 'table';
  const fields 				= 'fields';
  const primaryKey 			= 'primaryKey';
  const uniqueKeys			= 'uniqueKeys';
  const foreignKeys 			= 'foreignKeys';
  const validation			= 'validate';
  const transformations		= 'transformations';
  const dataTypes				= 'dataTypes';
  const requiredFields		= 'requiredFields';
  const defaultValues			= 'defaults';
  const defaultFilter			= 'defaultFilter';
  const cascadeDelete			= 'cascadeDelete';
  const links					= 'links';
  const hasOne 				= 'hasOne';
  const hasMany 				= 'hasMany';
  const belongsTo 			= 'belongsTo';
  const belongsToAndHasMany 	= 'belongsToAndHasMany';
  // const fullText				= 'fullText';

  public function getClassName();
  public function getTable();
  public function getFields();
  public function getPrimaryKey();
  public function getUniqueKeys();
  public function getForeignKeys();
  public function getValidation();
  public function getTransformations();
  public function getDataTypes();
  public function getRequiredFields();
  public function getDefaultValues();
  public function getDefaultFilter();
  public function getCascadeDelete();
  public function getLinks();
  public function getHasOne();
  public function getHasMany();
  public function getBelongsTo();
  public function getBelongsToAndHasMany();

  public function hasClassName();
  public function hasTable();
  public function hasFields();
  public function hasPrimaryKey();
  public function hasCompositeKey();
  public function hasUniqueKeys();
  public function hasForeignKeys();
  public function hasValidation();
  public function hasTransformations();
  public function hasDataTypes();
  public function hasRequiredFields();
  public function hasDefaultValues();
  public function hasDefaultFilter();
  public function hasCascadeDelete();
  public function hasLinks();
  public function hasOne();
  public function hasMany();
  public function hasBelongsTo();
  public function hasBelongsToAndHasMany();

  public function getRelatedField(ModelConfig $pConfig);
  public static function getModelConfig($pClassName);

}