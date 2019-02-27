<?php   /*

  Simple class to log something somewhere
        https://github.com/ZsBT
  

  Example: 
    $logr = new ZsBT\misc\logger;
    
    $logr->debug(  array("debug"=>23)  );
    $logr->info( "some information to note" );
    $logr->warn( "this is a warning" );
    $logr->error( "server made a boo-boo" );

  */



namespace ZsBT\misc;

class Logger {
  public $DATEFORMAT = "Y-m-d H:i:s"
      ,$OUTFILE_DEBUG= "php://stdout"    // can be stderr or file or whatever
      ,$OUTFILE_INFO = "php://stdout"    // as above
      ,$OUTFILE_WARN = "php://stdout"    // as above
      ,$OUTFILE_ERROR= "php://stdout"    // as above
      ,$trace = false		// print filename & line no
    ; 

  private function printout($type, $msg, $trace){
    // format to JSON if not string
    $msg = (is_string($msg)? $msg:json_encode($msg,JSON_NUMERIC_CHECK) );
    // strip newlines
    $msg = str_replace("\r", "", $msg);
    $msg = str_replace("\n", "\\n", $msg);
    $line = sprintf("%s $type %s ", date($this->DATEFORMAT), $msg);

    // include IP if exists
    if( isset($_SERVER["REMOTE_ADDR"]) )$line.=sprintf("<%s>", $_SERVER["REMOTE_ADDR"]);

    // more details here
    if( $trace || $this->trace ){
      $trca = $ta = debug_backtrace(0);
      array_shift($trca);
      foreach($trca as $trc){
        $ob = (object)$trc;
        if($ob->file == __FILE__ ) continue;
        $line.= sprintf("[%s:{$ob->line}] ",basename($ob->file));
#        break;
      }
    }

    // where to put text
    switch($type){
      case "DEBUG": $outfile = $this->OUTFILE_DEBUG; break;
      case "WARN": $outfile = $this->OUTFILE_WARN; break;
      case "ERROR": $outfile = $this->OUTFILE_ERROR; break;
      default: $outfile = $this->OUTFILE_INFO; break;
    }
    
    return file_put_contents($outfile, "$line\n", FILE_APPEND);
  }
  public function debug($msg, $trace=true){
    return $this->printout("DEBUG", $msg, $trace);
  }
  
  public function info($msg, $trace=false){
    return $this->printout("INFO", $msg, $trace);
  }
  
  public function warn($msg, $trace=false){
    return $this->printout("WARN", $msg, $trace);
  }
  
  public function error($msg, $trace=true){
    return $this->printout("ERROR", $msg, $trace);
  }
  
  public function fatal($msg){
    $this->error("FATAL: $msg");
    throw new \Exception($msg);
  }
  
  

  public function setlogfile($filename)		// set all log file name to this
  {
    return
      $this->OUTFILE_DEBUG =
      $this->OUTFILE_INFO =
      $this->OUTFILE_WARN =
      $this->OUTFILE_ERROR = 
        $filename;
  }
  
  
}
?>
