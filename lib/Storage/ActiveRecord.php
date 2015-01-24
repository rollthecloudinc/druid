<?php
namespace Druid\Storage;

use Druid\Interfaces\DataEntity as IActiveRecordDataEntity;
use Druid\Interfaces\Savable as IActiveRecordSavable;
use Druid\Interfaces\Destroyable as IActiveRecordDestroyable;
use Druid\Interfaces\Xml as IActiveRecordXML;
use Druid\Interfaces\Dumpable as IActiveRecordDumpable;
use Druid\Interfaces\Validation as IActiveRecordValidation;
use Druid\Interfaces\FindConfig as IActiveRecordFindConfig;
use Druid\Storage\Entity\DataEntity as ActiveRecordDataEntity;
use Druid\Core\Connection\Connection as ActiveRecordConnection;
use Druid\Core\Inflector\Inflector as Inflector;
use Druid\Select\SelectNode as ActiveRecordSelectNode;
use Druid\Select\CountStatement as ActiveRecordCountStatement;
use Druid\Select\CollectionAgent as ActiveRecordCollectionAgent;
use Druid\Save\Save as ActiveRecordSave;
use Druid\Cascade\Cascade as ActiveRecordCascade;
use Druid\Cascade\CascadeNode as ActiveRecordCascadeNode;
use Druid\Cascade\Action\Destroy as ActiveRecordDestroy;
use Druid\Cascade\Action\Deactivate as ActiveRecordDeactivate;
use Druid\Core\Query\Query as ActiveRecordQuery;
use Druid\Storage\Dom\DomElement as ActiveRecordDOMElement;
use Druid\Core\Model\ModelConfig as ActiveRecordModelConfig;
use Druid\Select\Find\FindConfig as ActiveRecordFindConfig;

//$d = str_replace('//','/',dirname(__FILE__).'/');
//require_once($d.'entity/data_entity.php');
//require_once($d.'collection/collection.php');
//require_once($d.'../core/connection/connection.php');
//require_once($d.'../core/inflector/inflector.php');
//require_once($d.'../select/select_node.php');
//require_once($d.'../select/count_statement.php');
//require_once($d.'../select/collection_agent.php');
//require_once($d.'../save/save.php');
//require_once($d.'../cascade/cascade.php');
//require_once($d.'../cascade/cascade_node.php');
//require_once($d.'../cascade/action/destroy.php');
//require_once($d.'../cascade/action/deactivate.php');
//require_once($d.'../core/validation/validation.php');

/*abstract*/ class ActiveRecord implements IActiveRecordDataEntity ,\arrayaccess,IActiveRecordSavable, IActiveRecordDestroyable ,IActiveRecordXML,IActiveRecordDumpable {

  const

    /*
    * Optional [first] argument for ActiveRecord::find() method to change intended
    * behavior in respect to what is returned or statement object used. The default
    * argument is ActiveRecord::findAll which performs a basic find operation that returns
    * a ActiveRecord collection of mateched items.
    *
    * The default behavior of findAll can be changed by using one of the other 3 finder modes.
    * Thye three find modes available are:
    *
    * 1.) ActiveRecord::findOne
    * 2.) ActiveRecord::findCount
    * 3.) ActiveRecord::findSubquery
    *
    * The ActiveRecord::findOne method functions exactly the same as ActiveRecord::findAll but returns
    * a single ActiveRecord rather than a collection of items. This useful for operations in which you
    * know only one record may be returned such as; when setting limit to 1. So rather than getting
    * back an array [collection] of items you get back a single ActiveRecord object or null if nothing
    * was found. Something worth noting is that when nothing is found using the ActiveRecord::findAll method
    * a collection object is still returned but it will be empty unlike ActiveRecord::findOne which will return null.
    *
    * Similarly ActiveRecord::findCount functions the same as ActiveRecord::findAll and ActiveRecord::findOne
    * but returns a single integer representing the number of items matched. Internally, this mode changes
    * the type of select statement used so that only a count is selected rather than every single column of each
    * included model. Thus, this particular mode is most useful when you would like to perform somthing resembling
    * a COUNT(*) operation and get back the total as an integer representing the number of items matched.
    *
    * Entirly different than all those before is ActiveRecord::findSelect. This mode is unique in respect
    * to it returning the raw select statement (ActiveRecordSelectStatement) object used to compile the
    * end query for the find operation. This mode was manifested for the purpose of using subqueries and
    * calculated columns. For example, often times when locating the group wise maximum a subquery is required
    * to first perform a selection of the maximum time and joining on the outer most query to actually resolve
    * the row that relates. The ActiveRecord::findSelect supports this functionality within the system by allowing
    * ActiveRecordSelectSatetment objects to be directory included in dynamic columns, where clause and include
    * flag when finding items. In then end making it possible to have 1,2,3,4... levels of nested subqueries.
    *
    * @TODO: Future development will include the ability to use a ActiveRecordSelect statement as the primary
    * model (first table). Currently this is a functionality that is not directly supported in the system.
    *
    * SELECT
    *      ...
    *   FROM
    *      (SELECT (first table can't be a dynamic model - dynamic model being any model generated from a ActiveRecordSelectStatement at runtime)
    *         FROM
    *       ) t1
    *   INNER
    *    JOIN
    *       users t2
    *      ON
    *       ...
    *
    * @TODO: Union (merging) abilities for multiple models
    *
    */
    findCount = 'count'
  ,findAll = 'all'
  ,findOne = 'one'
  ,findSelect	= 'subquery'

    /*
    * The below represent the inital string to identify dynamic find methods for instantiated
    * ActiveRecord objects. Once a ActiveRecord has been instaniated locating related entities
    * may be done by bypassing the static ActiveRecord::find() method in replacement
    * of one of the following methods.
    *
    * 1.) $record->get[Entity]();
    * 2.) $record->count[Entity]();
    * 3.) $record->add[Entity]();
    *
    * The dynamic get method is probably the simplest of all options. This method was manifested
    * on behalf of the need for a way to rapidly locate entities related to the base entity. At its core
    * the get method provides a lazy load functionality.
    *
    *  $user->blogs == $user->getBlogs()
    *
    * The former example shows the syntax used in most syntax to perform lazy loading. The ladder
    * represents the syntax used in this system to load a collection of related items. The method
    * call may seem uneeded to some but it has its purpose. The purpose it rooted in the abilility
    * to further filter or sort those related items without resoring to some contribed string manipulation
    * such as; $user->blogs_status_1_desc_created (yuck)
    *
    * Instead the below syntax would be used to sort the returned collection and additionally filter
    * for a statuc of 1.
    *
    * $user->getBlogs(array('status'=>1,'sort'=>array('created'=>'DESC')));
    *
    * So the above scenario would return all the users blogs that have a status of 1 and sort them by
    * last created. Additionally, the result set will not be saved within the scope of the objects blogs
    * property. This method is meant to act as a short-cut finder for relational mapping.
    *
    * The count method functions exactly the same way but will return the total of items matched. So if you need
    * to know the number of blogs a user has you could achieve such a task using the below syntax.
    *
    * $user->countBlogs();
    *
    * Additonally, you could count the number of inactive blogs by passing in the proper filter.
    *
    * $user->countBlogs(array('status'=>0));
    *
    * The count method is mostly intended for performing quick counts for pagination or summary information.
    *
    *
    */
  ,findRelatedPrefix = 'get'
  ,countRelatedPrefix = 'count'
  ,addRelatedPrefix = 'add';

  private

    /*
    * Data entity object that stores ActiveRecord data as key=>value combos. Data entity
    * implements interface for arrayaccess so data may be accesed using array syntax IE. $data['username'].
    * A ActiveRecord instance essentially decorates a ActiveRecordDataEntity thus, a ActiveRecord is interchangable
    * with a ActiveRecordDataEntity. This was done soley for the purpose of separation and organization within
    * the system. So that the ActiveRecord manages behavior and ActiveRecordDataEntity storage.
    *
    * Another vital purpose of ActiveRecordDataEntity is to have a way to distinguish between arrays and storage
    * object when creating a ActiveRecord. When one passes an array on construction every value within the array
    * is cached as changed. Which means that when the ActiveRecord is saved all those columns will be updated or
    * generate a new record in the associated table to the model. In contrast when you send in a ActiveRecordDataEntity
    * the information is not chached as saved. So calling save will have to affect unless you modify a property
    * after construction. This differentiation is vital to the functionality of the ActiveRecordCollectionAgent
    * which collects raw result data into a meaningful object hierarchy of records so that below syntax may be achieved
    * through query point eager loading.
    *
    * $arrUser = User::find(array('include'=>'blogs'));
    * $arrUser->blogs[0]->title;
    *
    */
    $_data

    /*
    * The fields that have changed since ActiveRecord was created. This information is used
    * by the save mechanism to update only fields that have changed rather than every field
    * for the model.
    */
  ,$_changed;

  private static

    /*
    * PDO database adapter
    */
    $_db

    /*
    * Absolute path to configuration file
    */
  ,$_config

    /*
    * Whether or not model files have been loaded
    */
  ,$_boolLoadedModels

    /*
    * Validation object
    */
  ,$_validation;

  public function __construct() {

    $this->_data = new ActiveRecordDataEntity();
    $this->_changed = array();

    if(self::getConnection() === null) {
      throw new Exception('A database connection could not be established');
    }

    // auto load model files
    self::loadModelFiles();

    $args = func_get_args();

    if(!empty($args)) {

      $this->_init($args);

    }

  }

  protected function _init($pArgs) {

    if($pArgs[0] instanceof IActiveRecordDataEntity) {

      $this->_data = $pArgs[0];

    } else if(is_array($pArgs[0])===true) {

      $this->_initInactive($pArgs);

    } else if(count($pArgs)>1) {

      $str = '';
      foreach($pArgs as $arg) {
        $pos = strpos($arg,':');
        if($pos!==false) $str.= '\''.substr($arg,0,$pos).'\'=>'.substr($arg,($pos+1)).',';
      }

      eval('$properties = array('.rtrim($str,',').');');
      $this->_initInActive(array($properties));

    } else {

      $this->_initActive($pArgs);

    }

  }

  protected function _initInactive($pArgs) {

    foreach($pArgs[0] as $property=>$value) {

      //not certain but this might break something
      //$this->_data->setProperty($property,$value);
      $this->setProperty($property,$value);

    }

    if(array_key_exists(1,$pArgs) && is_bool($pArgs[1] && $pArgs[1]===true)) {

      $this->save();

    }

  }

  protected function _initActive($pArgs) {

    $className = get_class($this);
    $model = ActiveRecordModelConfig::getModelConfig($className);

    if($model->hasPrimaryKey()===false) {
      throw new \Exception('A model must have a primary key static property specified as a string to be initialized as a ActiveRecord. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__);
    }

    $primaryKeyField = $model->getPrimaryKey();

    $record = self::_find($className,array(self::findOne,array($primaryKeyField=>$pArgs[0])));

    if(is_null($record)===true) {

      throw new \Exception('A '.$model->getClassName().' with a primary key of '.$pArgs[0].' could not be located. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__);

    } else {

      $this->_data = $record->getData();
      unset($record);

    }

  }

  public function offsetSet($offset,$value) {

    $this->setProperty($offset,$value);

  }

  public function offsetExists($offset) {

    return $this->hasProperty($offset);

  }

  public function offsetUnset($offset) {

    $this->removeProperty($offset);

  }

  public function offsetGet($offset) {

    return $this->hasProperty($offset) ? $this->getProperty($offset) : null;

  }

  public function getIterator() {
    return $this->_data->getIterator();
  }

  public function __set($pName,$pValue) {

    $this->setProperty($pName,$pValue);

  }

  public function __get($pName) {

    return $this->getProperty($pName);

  }

  /*
  * Decorates setProperty() of ActiveRecordDataEntity adding
  * magical primary key support, validation and change logging
  */
  public function setProperty($pName,$pValue) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($this));

    // check for magical primary key and handle appropriatly
    $pName = $pName === IActiveRecordFindConfig::id?$config->getPrimaryKey():$pName;

    // run validation if any exists
    $validation = $config->getValidation();
    if(array_key_exists($pName,$validation) && method_exists($this,$validation[$pName])) {

      $this->{$validation[$pName]}($pValue);

    }

    if(!in_array($pName,$this->_changed)) $this->_changed[] = $pName;

    $this->_data->setProperty($pName,$pValue);

  }

  public function getProperty($pName) {

    if($this->_data->hasProperty($pName)===true) {

      return $this->_data->getProperty($pName);

    } else {

      return $this->load($pName);

    }

  }

  public function hasProperty($pName) {

    return $this->_data->hasProperty($pName);

  }

  public function removeProperty($pName) {

    return $this->_data->removeProperty($pName);

  }

  public function getRecord($pPropertyName,$pPrimaryKey,$pField) {

    return $this->_data->getRecord($pPropertyName,$pPrimaryKey,$pField);

  }

  public function addRecord($pPropertyName,IActiveRecordDataEntity $pRecord,$pArrayByDefault=false) {

    $this->_data->addRecord($pPropertyName,$pRecord,$pArrayByDefault);

  }

  public function getData() {

    return $this->_data;

  }

  /*
  * The reason as to why I named this method casts alludes me to
  * this day. However, due to uncertaintity of dependendencies
  * am not going to change its name to something more appropriate like
  * reset or flatten.
  *
  * Essentially though this called after saving a
  * ActiveRecord do that its changed history is deleeted. This is done
  * to avoid resaving the same information when a ActiveRecord is
  * saved multiple times.
  */
  public function cast() {

    $this->_changed = array();
    $this->_data->cast();

  }

  public function hasChanged($pName) {

    //return $this->_data->hasChanged($pName);
    return in_array($pName,$this->_changed)?true:false;

  }

  public function __call($pName,$pArgs) {

    if(preg_match('/^'.self::findRelatedPrefix.'/',$pName)) {

      $mode = self::findRelatedPrefix;
      $propertyName = substr($pName,strlen($mode));
      $relatedClassName = Inflector::classify($propertyName);

      $modelConfig = ActiveRecordModelConfig::getModelConfig(get_class($this));
      $relatedModelConfig =  ActiveRecordModelConfig::getModelConfig($relatedClassName);

      $modelField = $modelConfig->getRelatedField($relatedModelConfig);
      $relatedModelField = $relatedModelConfig->getRelatedField($modelConfig);

      if(empty($modelField) || empty($relatedModelField)) {

        // voodoo to make this work
        if($modelConfig->hasBelongsToAndHasMany()) {
          foreach($modelConfig->getBelongsToAndHasMany() as $index=>$reference) {

            $class = Inflector::classify($reference[0]);
            if(strcmp($class,$relatedModelConfig->getClassName())==0) {
              $manyToMany = true;
              // 'views' array('include'=>'viewazations'),array('controler_id'=1)
              $class = Inflector::classify($reference[1]);
              $tModel = ActiveRecordModelConfig::getModelConfig($class);
              $relatedField = $modelConfig->getRelatedField($tModel);
              $tRelatedField = $tModel->getRelatedField($modelConfig);
              $pArgs = empty($pArgs)===true?array(array()):$pArgs;
              $pArgs[0][IActiveRecordFindConfig::findInclude] = $reference[1];
              $invisible = IActiveRecordFindConfig::findInvisible;
              $pArgs[1] = array($tRelatedField=>$this->getProperty($relatedField),$invisible=>true);
              return self::_find($relatedModelConfig->getClassName(),$pArgs);
            }

          }
        }

        throw new \Exception('Relationship between '.get_class($this).' and '.$relatedClassName.' could not be resolved. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.' for '.$pName.'()');

      }

      $pArgs = empty($pArgs)===true?array(array()):$pArgs;
      array_unshift($pArgs,$modelConfig->getRelatedType($relatedModelConfig));
      $pArgs[1][$relatedModelField] = $this->$modelField;

      return self::_find($relatedClassName,$pArgs);

    } else if(preg_match('/^'.self::countRelatedPrefix.'/',$pName)) {

      $mode = self::countRelatedPrefix;
      $relatedClassName = Inflector::classify(substr($pName,strlen($mode)));

      $modelConfig = ActiveRecordModelConfig::getModelConfig(get_class($this));
      $relatedModelConfig =  ActiveRecordModelConfig::getModelConfig($relatedClassName);

      $modelField = $modelConfig->getRelatedField($relatedModelConfig);
      $relatedModelField = $relatedModelConfig->getRelatedField($modelConfig);

      if(empty($modelField) || empty($relatedModelField)) {

        // voodoo to make this work
        if($modelConfig->hasBelongsToAndHasMany()) {
          foreach($modelConfig->getBelongsToAndHasMany() as $index=>$reference) {

            $class = Inflector::classify($reference[0]);
            if(strcmp($class,$relatedModelConfig->getClassName())==0) {
              $manyToMany = true;
              // 'views' array('include'=>'viewazations'),array('controler_id'=1)
              $class = Inflector::classify($reference[1]);
              $tModel = ActiveRecordModelConfig::getModelConfig($class);
              $relatedField = $modelConfig->getRelatedField($tModel);
              $tRelatedField = $tModel->getRelatedField($modelConfig);
              $pArgs = empty($pArgs)===true?array(array()):$pArgs;
              $pArgs[0][IActiveRecordFindConfig::findInclude] = $reference[1];
              $invisible = IActiveRecordFindConfig::findInvisible;
              $pArgs[1] = array($tRelatedField=>$this->getProperty($relatedField),$invisible=>true);
              array_unshift($pArgs,$mode);
              return self::_find($relatedModelConfig->getClassName(),$pArgs);
            }

          }
        }

        throw new \Exception('Relationship between '.get_class($this).' and '.$relatedClassName.' could not be resolved. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.' for '.$pName.'()');

      }

      $pArgs = empty($pArgs)===true?array(array()):$pArgs;
      $pArgs[0][$relatedModelField] = $this->$modelField;
      array_unshift($pArgs,$mode);

      return self::_find($relatedClassName,$pArgs);

    } else if(preg_match('/^'.self::addRelatedPrefix.'/',$pName)) {

      $mode = self::addRelatedPrefix;
      $relatedClassName = Inflector::classify(substr($pName,strlen($mode)));

      $modelConfig = ActiveRecordModelConfig::getModelConfig(get_class($this));

      if(empty($pArgs)===true) {

        throw new \Exception('The dynamic '.self::addRelatedPrefix.' method for instance of class '.$modelConfig->getClassName().' requires at least one argument. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__);

      }

      $relatedModelConfig =  ActiveRecordModelConfig::getModelConfig($relatedClassName);
      $modelField = $modelConfig->getRelatedField($relatedModelConfig);
      $relatedModelField = $relatedModelConfig->getRelatedField($modelConfig);

      if(empty($modelField) || empty($relatedModelField)) {

        $manyToMany = false;

        // voodoo to make this work
        if($modelConfig->hasBelongsToAndHasMany()) {
          foreach($modelConfig->getBelongsToAndHasMany() as $index=>$reference) {

            $class = Inflector::classify($reference[0]);
            if(strcmp($class,$relatedModelConfig->getClassName())==0) {
              $manyToMany = true;
              break;
            }

          }
        }

        if($manyToMany===false) {

          throw new \Exception('Relationship between '.get_class($this).' and '.$relatedClassName.' could not be resolved. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.' for '.$pName.'()');

        }

      }

      $property = Inflector::pluralize(Inflector::underscore($relatedClassName));
      $invalid = array();
      foreach($pArgs as $key=>$arg) {

        if(!$arg instanceof $relatedClassName) {

          $invalid[] = $key;

        }

      }

      if(!empty($invalid)) {

        throw new \Exception('Dynamic method '.$pName.' only accepts objects of type '.$relatedClassName.' as arguments. Exception thrown in class '.__CLASS__.' on line '.__LINE__.' in method '.__METHOD__.'.');

      }

      foreach($pArgs as $arg) {

        /*if($arg->hasProperty($relatedModelField)===false) {
          $arg->setProperty($relatedModelField,$clone);
        }*/

        $this->addRecord($property,$arg,true);

      }

      return $this;

    } else {

    }

  }

  // lazy load mechanism
  public function load($pPropertyName) {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($this));

    try {

      $relatedConfig = ActiveRecordModelConfig::getModelConfig(Inflector::classify($pPropertyName));
      //$relatedField = $config->getRelatedField($relatedConfig);

      $method = self::findRelatedPrefix.ucfirst(Inflector::camelize($pPropertyName));
      $record = $this->$method();
      $this->setProperty($pPropertyName,$record);
      return $this->getProperty($pPropertyName);

    } catch(\Exception $e) {

      return null;

    }

  }

  public function destroy() {

    $config = ActiveRecordModelConfig::getModelConfig(get_class($this));
    $node = new ActiveRecordCascadeNode($config);
    $node->addRecord($this);

    try {

      $delete = new ActiveRecordDestroy();
      $cascade = new ActiveRecordCascade($delete);
      $cascade->cascade($node);

    } catch(\Exception $e) {

      throw new \Exception('Error initializing delete. Exception caught and rethrown from line '.__LINE__.' in class '.__CLASS__.' inside method '.__METHOD__.': '.$e->getMessage());
      return false;

    }

    try {

      if($delete->query(self::getConnection())===true) {

        $unset = new ActiveRecordDeactivate();
        $cascade = new ActiveRecordCascade($unset);
        $cascade->cascade($node);
        return true;

      } else {

        return false;

      }

    } catch(\Exception $e) {

      throw new \Exception('Error executing delete queries. Exception caught and rethrown from line '.__LINE__.' in class '.__CLASS__.' inside method '.__METHOD__.': '.$e->getMessage());
      return false;

    }

  }

  public function save($pValidate=true) {

    $save = new ActiveRecordSave($this);
    return $save->query(self::$_db);

  }

  /*
  * The driving force behind the entire ActiveRecord finder. This is the method
  * indrectly called by a ActiveRecord::find() that handles all the heavy labor and management
  * of library dependencies.
  */
  protected static function _find($pClassName,$pOptions) {

    if(self::getConnection() === null) {
      throw new \Exception('A database connection could not be established');
    }

    // load model files
    self::loadModelFiles();

    // ability to handle subqueries as root

    /*
    * Moved the below responsbility to the ActiveRecordModelConfig::getModelConfig() method. When the method
    * is passed a ActiveRecordSelectStatement it transforms it into a ActiveRecordDynamicModel object
    *
    * $model = $pClassName instanceof IActiveRecordModelConfig?$pClassName:ActiveRecordModelConfig::getModelConfig($pClassName);
    */
    $model = ActiveRecordModelConfig::getModelConfig($pClassName);

    $mode = !empty($pOptions) && isset($pOptions[0]) && is_array($pOptions[0])===false?array_shift($pOptions):self::findAll;

    $node = new ActiveRecordSelectNode($model,new ActiveRecordFindConfig(!empty($pOptions)?$pOptions[0]:array()));
    $select = strcasecmp($mode,self::findCount)==0?new ActiveRecordCountStatement($node,$pOptions):new ActiveRecordSelectStatement($node,$pOptions);

    if(strcmp($mode,self::findSelect)==0) return $select; // return without reseting count

    /*echo '<p>',$select->toSql(),'</p>';
    echo '<pre>',print_r($select->getBindData()),'</pre>';
    return;*/

    $stmt = $select->query(self::$_db);
    ActiveRecordSelectNode::resetCount();
    $collectionAgent = new ActiveRecordCollectionAgent($select);

    if(strcmp($mode,self::findCount)==0) { return $stmt->fetchColumn(); }

    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $collectionAgent->process($row,$node);
    }

    $records = $collectionAgent->getRecords();

    return strcmp($mode,self::findOne)==0?count($records)!=0?$records[0]:null:$records;

  }

  public static function findDynamic() {

    $arrArgs = func_get_args();
    $model = array_shift($arrArgs);

    return call_user_func_array(__CLASS__.'::_find',array($model,$arrArgs));

  }

  /*
  * The lazymanes object dump
  *
  * User::find()->dump();
  *
  * vs.
  *
  * $users = User::find();
  * echo '<pre>',print_r($users),'</pre>';
  *
  * "ton" O work avoided
  *
  * NOTE: Both ActiveRecord and ActiveRecordCollection implement
  * the dumpable interface so a individual or collection of records
  * may be dumped by calling the dump method on either.
  */
  public function dump() {

    echo '<pre>',print_r($this),'</pre>';

  }

  /*
  * If your feeling like or need to convert the contents of a ActiveRecord
  * to a XML document then this is the method for you. This method
  * handles all the heavy work of converting the ENTIRE
  * object hierarchy to a comprehansive XML document.
  *
  * NOTE: Both ActiveRecord and ActiveRecordCollection implement to
  * xmlable interface so that either be converted to XML by calling the
  * tuXML method.
  */
  public function toXML() {

    $dom = new ActiveRecordDOMElement($this);
    header('Content-Type: text/xml; charset=utf-8');
    $dom->formatOutput = true;
    echo $dom->saveHTML();

  }

  public function toDOMElement() {

    return new ActiveRecordDOMElement($this);

  }

  public static function foundRows() {
    return self::$_db->query('SELECT FOUND_ROWS();')->fetchColumn();
  }

  /*
  * Check for database connection
  *
  * @return bool yes/no
  */
  public static function isConnected() {

    $boolConnected = self::$_db === null?false:true;

    /*
    * Attempt to automate connection
    */
    if($boolConnected === false) {
      self::_establishConnection();
    }

    return self::$_db === null?false:true;
  }

  public static function setConnection(PDO $pDb) {
    self::$_db = $pDb;
  }

  public static function getConnection() {

    if(self::$_db === null) {
      self::_establishConnection();
    }

    return self::$_db;
  }

  public static function setValidation(IActiveRecordValidation $pValidation) {
    self::$_validation = $pValidation;
  }

  /*
  * Establishes database connection from config file
  */
  private static function _establishConnection() {

    /*
    * Get config file
    */
    $objConfig = self::getConfig();

    /*
    * Make sure config file exists and loaded without errors
    */
    if($objConfig === null) {
      return;
    }

    /*
    * Config has been loaded sucessfully continue to extract
    * connection information.
    */
    $strHost = (string) $objConfig->connection->host;
    $strUser = (string) $objConfig->connection->user;
    $strPassword = (string) $objConfig->connection->password;
    $strDBName = (string) $objConfig->connection->name;

    /*
    * Instaniate connection
    */
    self::$_db = new ActiveRecordConnection($strHost,$strUser,$strDBName,$strPassword);

  }

  /*
  * Get config XML object
  *
  * @return obj SimpleXMLObject
  */
  public static function getConfig() {
    $objConfig = simplexml_load_file(self::getConfigFilePath());
    return $objConfig === false?null:$objConfig;
  }

  /*
  * Get absolute path to config file path
  *
  * @return str config file path
  */
  public static function getConfigFilePath() {

    /*
    * When no file path has been created make it
    */
    if(self::$_config === null) {
      // base location is lib root in config.xml
      self::$_config = str_replace('//','/',dirname(__FILE__).'/').'../config.xml';
    }

    return self::$_config;
  }

  /*
  * Set absolute config file path
  *
  * @param str config file path
  */
  public static function setConfigFilePath($strConfig) {
    self::$_config = $strConfig;
  }

  /*
  * Auto-load all model files to avoid manually inclusion
  *
  * @param str model directory path
  * @return bool success/failure
  */
  public static function loadModelFiles() {

    if(self::$_boolLoadedModels !== null) {
      return true;
    }

    /*
    * Get models directory
    */
    $objConfig = self::getConfig();
    $strDirectory = (string) ActiveRecord::getConfig()->models->directory;
    $strModelsDirectory = str_replace('//','/',dirname(__FILE__).'/')."../$strDirectory";

    // check that directory exists
    if(!is_dir($strModelsDirectory)) {
      self::$_boolLoadedModels = false;
      throw new \Exception("Directory $strModelsDirectory not found when attempting to load models.");
      return false;
    }

    // include every model to avoid the requirement of manually including file (yuck)
    foreach(scandir($strModelsDirectory) as $strFile) {
      if((strcmp($strFile,'.') == 0 || strcmp($strFile,'..') == 0) || is_dir("$strModelsDirectory/$strFile")) continue;
      require_once("$strModelsDirectory/$strFile");
    }

    self::$_boolLoadedModels = true;
    return true;

  }

  /*
  * Central method all queries run throuh. This may be used for
  * logging or debugging purposes.
  *
  * @param obj ActiveRecordQuery
  * @param obj PDO adapter
  * @param str type of query (based on active record query contstants)
  *
  * - ActiveRecordQuery::SELECT (select data)
  * - ActiveRecordQuery::DELETE (delete data)
  * - ActiveRecordQuery::UPDATE (update data)
  * - ActiveRecordQuery::INSERT (insert data)
  *
  * NOTE: Use combination of ActiveRecordQuery::UPDATE && ActiveRecordQuery::INSERT for save.
  * Save is either an insert or update depending on whether the record exists yet.
  *
  *
  * @param obj reference to calling object
  */
  public static function query(ActiveRecordQuery $query,\PDO $db,$type,$caller=null) {

    switch($type) {
      case ActiveRecordQuery::SELECT:
      case ActiveRecordQuery::DELETE:
      case ActiveRecordQuery::UPDATE:
      case ActiveRecordQuery::INSERT:
      default:
        //$query->showQuery(); // - display query
        //exit;
        return $query->query($db);
    }

  }

  //abstract public static function find();

  /**
   * Standard finder method.
   */
  public static function find() {
    $args = func_get_args();
    return self::_find(get_called_class(),$args);
  }

  /**
   * Get count of items in collection.
   */
  public static function count() {
    $args = func_get_args();
    array_unshift($args,self::findCount);
    return self::_find(get_called_class(),$args);
  }

  /**
   * Get first record in collection.
   */
  public static function one() {
    $args = func_get_args();
    array_unshift($args,self::findOne);
    return self::_find(get_called_class(),$args);
  }

  /**
   * Short-cut for find... I think
   */
  public static function all() {
    $args = func_get_args();
    array_unshift($args,self::findAll);
    return self::_find(get_called_class(),$args);
  }

  /**
   * Advanced: Get select statement for creating complex
   * queries queries w/ nested subqueries.
   */
  public static function select() {
    $args = func_get_args();
    array_unshift($args,self::findSelect);
    return self::_find(get_called_class(),$args);
  }

}
?>