<?php


use \ZsBT\misc\Cache;
#Cache::$TIMEOUT = 3600;

abstract class mxcheck {
  const TIMEOUT = 5;
  
  // reads the welcome string from SMTP protocol 
  public function welcomeString($host, $port=25){
    ini_set("default_socket_timeout", self::TIMEOUT);
    $fp = fsockopen($host,$port);
    if(!$fp)return false;
    fwrite($fp,"quit\r\n");
    $ret = fgets($fp,256);
    fclose($fp);
    return $ret;
  }
  
  // reads a HTTPS page, following redirections if needed
  public function httpsPage($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_CERTINFO, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
    curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)');
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
  }
  
  
  public function isOWA($url){	
    $check = preg_match("/microsoft outlook|outlook web/i", $page=self::httpsPage($url) );
    die( $check ? "true":"false" );
  }
  
  
  public function mxrecord($host){
    $mxa=Cache::get($ckey="mx-$host");
    
    if(is_array($mxa))return $mxa;
    $mxa = [];
    getmxrr($host, $mxa);
    Cache::set($ckey,$mxa);
    return $mxa;
  }
  
  
  public function is_google($host, $port=25, $recursive=true){
    // check MX hostnames only
    return !!strpos( implode(",",self::mxrecord($host)), 'google' );
    
    if($recursive && $mxa=self::mxrecord($host))return self::is_google($mxa[0], $port, false);
    $banner = self::welcomeString($host,$port);
    if(strpos($banner,"gsmtp"))return true;
    return false;
  }
  

  // finds out whether the host has microsoft exchange service 
  public function is_ms($host, $port=25, $recursive=true){
    
    // check MX hostnames only
    return !!strpos( implode(",",self::mxrecord($host)), 'protection.outlook' );
    
    
     
    if($recursive && $mxa=self::mxrecord($host))return self::is_ms($mxa[0], $port, false);
    
    $ret = false;
    
    // shall we get URL, pick its host. 
    if(preg_match("/([a-z0-9_\-\.]{6,50})/i", $host, $ma)) $host = $ma[1];
  
    $mbanner = self::welcomeString($host,$port);
    if( preg_match("/microsoft/i", $mbanner ))
      return "smtp";
    
    if( preg_match("/microsoft outlook/i", self::httpsPage("https://$host/exchange/") ))
      return "exchange";
      
    if( preg_match("/microsoft outlook|outlook web/i", self::httpsPage("https://$host/owa/") ))
      return "owa";
    
    return false;
  }
  
}
