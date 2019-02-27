<?php	/*

  simple class to create and check a lockfile
  
  for linux command line only.
                                      - Zsombor, 2015



  CLASS SYNOPSIS


  public function __construct($name=null, $folder="/run/lock" )	// what name to use and where.
  
  public function locked()	// check if locked by a running process (or other host)
  
  public function lock($pid=null)	// lock with my PID
  
  public function unlock()	// remove lockfile
  
  

*/

namespace ZsBT\misc;

class LockFile {

  public function __construct($name=null, $folder="/run/lock" ){	// what name to use and where.
    if(!$name)$name = str_replace(".php","",basename($_SERVER['SCRIPT_NAME']));
    if(!$name)throw new Exception("Specify a name");
    $this->name = $name;
    
    $this->lockfile = "$folder/$name.lock";
    $this->hostname = gethostname();
  }
  
  
  public function locked(){	// check if locked by a running process (or other host)
    if(file_exists($this->lockfile) && ($run=file_get_contents($this->lockfile))){
      list($runpid,$runhost) = explode("@",$run);
      if($runhost!=$this->hostname) return 2;
      if ( file_exists("/proc/$runpid") ) return 1;
      unlink($this->lockfile);
      return 0;
    }
    return 0;
  }
  

  public function lock($pid=null){	// lock with my PID
    if(!$pid)$pid = posix_getpid();
    return $this->locked()
      ? false
      : (file_put_contents($this->lockfile, "$pid@{$this->hostname}" )
          ? true
          : false
        );
  }
  
  
  public function unlock(){	// remove lockfile
    return unlink($this->lockfile);
  }
  
  
}

?>