<?php
class Connection extends PDO {
  
    public function __construct($pHost,$pUser,$pDbName,$pPwd) {
  
        try {
      
           
	parent::__construct("mysql:dbname=$pDbName;host=$pHost",$pUser,$pPwd);
          
        } catch(PDOException $e) {
      
            echo ('Unable to Connect');
            die;
        
        }
      
    }
  
}
?>