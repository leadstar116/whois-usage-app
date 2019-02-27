<?php /*

	This class sits on the great PHP-DB and helps to query/store data with one call.
	Therefore we support often-used select statements.
	
	Syntax tries to be the same as in my PDO class.
	
	https://github.com/ZsBT


CLASS SYNOPSIS

    public function begin(){    // transaction 
    public function commit(){   // transaction 
    public function rollback(){ // transaction 

    public function nextID($seq_name = "NEXTID"){       // next id from sequence 

    public function oneValue($sql, $parms = array() ){  // returns the first column of the first row of the query 
    public function oneCol($sql, $parms=array() ){  // returns the first column of all rows of the query 
    public function oneRow($sql, $parms=array() ){  // returns the first row of a statement, as stdClass object  
    public function allRow($sql, $parms=array() ){  // returns an array of stdClass objects - be sure to use only reasonable number of records. 

    public function iterate($sql, $function, $parms=array() ){  // pass every record object as parameter to $function  

    public function insert($table, $datArr){  // insert data to a table. datArr is a mapped array. no BLOB support 
    public function update($table, $datArr, $cond){  // update data in a table. datArr is a mapped array. $cond is the condition string 


DEPENDENCIES

	Needs php 5

	
CHANGELOG
	
*/


namespace ZsBT\misc;

require_once("DB.php");	// depends on PEAR DB

class PDB {

    function __construct($DSN){
        $this->db =& DB::connect($DSN);
        if(PEAR::isError($this->db))zsPDB::fatal($db->getMessage());
        $this->db->setFetchMode(DB_FETCHMODE_OBJECT);
    }
    
    
    private function fatal($msg){ throw new Exception($msg); }


    public function begin(){	// transaction 
        $begin = $this->db->autoCommit(false);
        if (PEAR::isError($begin)) zsPDB::fatal($begin->getMessage() );
        return $begin;
    }


    public function commit(){	// transaction 
        $c = $this->db->commit();
        if (PEAR::isError($c)) zsPDB::fatal($c->getMessage() );
        return $c;
    }
    
    
    public function rollback(){	// transaction 
        $c = $this->db->rollback();
        if (PEAR::isError($c)) zsPDB::fatal($c->getMessage() );
        return $c;
    }
    
    
    public function nextID($seq_name = "NEXTID"){	// next id from sequence 
        $id = $this->db->nextId($seq_name);
        if (PEAR::isError($id)) zsPDB::fatal($id->getMessage() );
        return $id;
    }


    public function oneValue($sql, $parms = array() ){  // returns the first column of the first row of the query 
        $data = &$this->db->getOne($sql, $parms);
        if (PEAR::isError($data)) zsPDB::fatal($data->getMessage() );
        return $data;
    }

    
    public function oneCol($sql, $parms=array() ){  // returns the first column of all rows of the query 
        $data = &$this->db->getCol($sql, 0, $parms);
        if (PEAR::isError($data)) zsPDB::fatal($data->getMessage() );
        return $data;
    }
    

    public function oneRow($sql, $parms=array() ){  // returns the first row of a statement, as stdClass object  
        $data = &$this->db->getRow($sql, $parms, $mode);
        if (PEAR::isError($data)) zsPDB::fatal($data->getMessage() );
        return $data;
    }
    

    public function allRow($sql, $parms=array() ){  // returns an array of stdClass objects - be sure to use only reasonable number of records. 
        $data = &$this->db->getAll($sql, $parms, $mode);
        if (PEAR::isError($data)) zsPDB::fatal($data->getMessage() );
        return $data;
    }
    

    public function iterate($sql, $function, $parms=array() ){	// pass every record object as parameter to $function  
        $res =& $this->db->query($sql, $parms);
        if (PEAR::isError($res))zsPDB::fatal($res->getMessage() );
        while( $row = &$res->fetchRow() ){
            if (PEAR::isError($row))zsPDB::fatal($row->getMessage() );
            if(FALSE===$function($row))return false;
        }
        return true;
    }
    
    
    public function insert($table, $datArr){  // insert data to a table. datArr is a mapped array. no BLOB support 
        $res = $this->db->autoExecute($table, $datArr, DB_AUTOQUERY_INSERT);
        if (PEAR::isError($res))zsPDB::fatal($res->getMessage() );
        return $res;
    }


    public function update($table, $datArr, $cond){  // update data in a table. datArr is a mapped array. $cond is the condition string 
        $res = $this->db->autoExecute($table, $datArr, DB_AUTOQUERY_UPDATE, $cond);
        if (PEAR::isError($res))zsPDB::fatal($res->getMessage() );
        return $this->db->affectedRows();
    }
    
}
?>
