<?php	/*

  this is not an installer script!
  
  it initializes neccessary stuff (DB and php classes)
  this file is included by all 'endpoint' php script.
  
  */

define("DATE_FORMAT", "d/m/Y");


error_reporting(E_WARNING+E_ERROR);


// always use this file's directory as current path 
chdir(__DIR__);

// dependencies
require_once(__DIR__.'/vendor/autoload.php');

// read all files from lib/
foreach(glob(__DIR__.'/lib/*.php')as $filename)
  require_once($filename);


// create an sWhois instance
$whois = new DIS(__DIR__."/config.js");
