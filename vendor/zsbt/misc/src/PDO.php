<?php /*

	This class extends PDO and helps to query/insert data with one call.
	Therefore we support often-used select statements.
	
	https://github.com/ZsBT


CLASS SYNOPSIS

    public function oneValue($sql)  // returns the first column of the first row of the query 
    public function oneCol($sql)  // returns the first column of all rows of the query 
    public function oneRow($sql, $mode=PDO::FETCH_OBJ)  // returns the first row of a statement, as stdClass object  
    public function allRow($sql, $mode=PDO::FETCH_CLASS)  // returns an array of stdClass objects - be sure to use only reasonable number of records. 

    public function iterate($sql, $function, $mode=PDO::FETCH_OBJ)	// pass every record object as parameter to $function  
    public function insert($table, $datArr)  // insert data to a table. datArr is a mapped array. no BLOB support 
    public function insert_multi($table, $datArrArr)  // insert multiple data to a table. datArr is an array of mapped array. no BLOB support
    public function update($table, $datArr, $cond)  // update data in a table. datArr is a mapped array. $cond is the condition string 

    public function lastError()      // return last error message


DEPENDENCIES

	Needs php 5.3

	
CHANGELOG
	
	2016-05		added insert_multi()
	2016-03		moving under namespace
	2015-11		added lastError()
	2015-03		added iterate(), consolidated statement preparations
	
*/

namespace ZsBT\misc;

class PDO extends \PDO {
    
    public function __construct($dsn, $username=NULL, $password=NULL, $options=[] ){
        // use persistent connection if not specified
        if(!isset($options[PDO::ATTR_PERSISTENT]))$options[PDO::ATTR_PERSISTENT] = true;
        
        parent::__construct($dsn, $username, $password, $options);
        self::setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
    }


    private function prep($sql){	/* tests errors in statement. drops error on failure */
        if(!$st = $this->prepare($sql))throw new \Exception(
            "Prepare statement error: ".json_encode($this->errorInfo())." SQL=[$sql]"
        );
        return $st;
    }
    
    
    public function begin(){	/* alias for beginTransaction() */
        return $this->beginTransaction();
    }


    private function prepexec($sql){	/* safely prepares and executes statement */
        $st = $this->prep($sql);
        $st->execute();
        return $st;
    }


    public function oneValue($sql){  /* returns the first column of the first row of the query */
        return $this->prepexec($sql)->fetch(PDO::FETCH_NUM)[0];
    }

    
    public function oneCol($sql){  /* returns the first column of all rows of the query */
        return $this->prepexec($sql)->fetchAll(PDO::FETCH_COLUMN,0);
    }
    

    public function oneRow($sql,$mode=PDO::FETCH_OBJ){  /* returns the first row of a statement, as stdClass object  */
        return $this->prepexec($sql)->fetch($mode);
    }
    

    public function allRow($sql,$mode=PDO::FETCH_CLASS){  /* returns an array of stdClass objects - be sure to use only reasonable number of records. */
        return $this->prepexec($sql)->fetchAll($mode);
    }
    

    public function iterate($sql, $function, $mode=PDO::FETCH_OBJ){	/* pass every record object as parameter to $function  */
        $st = $this->prepexec($sql);
        while($fo = $st->fetch($mode))
            if(false===$function($fo))return false;
        return true;
    }
    
    
    public function insert($table, $datArr){  /* insert data to a table. datArr is a mapped array. no BLOB support */
        $keys = @array_keys($datArr);
        $sql = @sprintf("insert into $table (\"%s\") values (:%s)", implode('","',$keys), implode(",:",$keys) );

        $st = $this->prep($sql);
        
        // bind parameters
        foreach($datArr as $key => $value)
            $st->bindParam(":$key", $tmp=$value );
        
        if(!$st->execute())return false;
        return ($ID=$this->lastInsertId())? $ID:true ;
    }


    public function insert_multi($table, $datArrArr){  /* insert multiple data to a table. datArr is an array of mapped array. no BLOB support */
        $keys = @array_keys($datArrArr[0]);
        $sql = @sprintf("insert into $table (%s) values ", @implode(",",$keys) );
        
        // build values() statement
        foreach($datArrArr as $i=>$datArr)
            $valA[] = ":".implode("$i,:", $keys).$i;
        $sql.= "(".implode("), (",$valA).")";
        
        $st = $this->prep($sql);
        
        // bind parameters
        foreach($datArrArr as $i=>$datArr){
            foreach($datArr as $key => $value)
                $st->bindParam(":{$key}{$i}", $tmp=$value );
        }

        if(!$st->execute())
            return false;
        
        return ($ID=$this->lastInsertId())? $ID:true ;
    }


    public function update($table, $datArr, $cond){  /* update data in a table. datArr is a mapped array. $cond is the condition string */
        $keys = @array_keys($datArr);
        
        // will be the SET values in statement
        $sets = array();
        foreach($keys as $key)
            $sets[] = "{$key}=:{$key}";
            
        $sql = @sprintf("update {$table} set %s where {$cond}", implode(",",$sets) );
        
        $st = $this->prep($sql);
        
        // bind parameters
        $tmp=null;
        foreach($datArr as $key => $value)
            $st->bindParam(":$key", $tmp=$value);
        
        if(!$st->execute())return false;
        return $st->rowCount();
    }


     public function lastError(){      /* return last error message */
         return $this->errorInfo()[2];
     }

}
?>
