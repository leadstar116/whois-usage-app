#!/usr/bin/php
<?php	/*

  this script needs to be run regularly, e.g. add to CRON
  it avoids flooding whois requests
  
  */ 

error_reporting(E_ERROR);

include_once(__DIR__."/../setup.php");

$lockfile = new \ZsBT\misc\LockFile;
$dis = new dis;

if($lockfile->locked() ){
  $dis->log->warn("I am already running");
}else{
  $lockfile->lock();
  $dis->process_all();
}

$lockfile->unlock();

exit(0);
