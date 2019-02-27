<?php	/*

  simple interface for GoDaddy API v1.
  
  for details, see https://developer.godaddy.com/doc 

                                    - Zsombor 06/2016
  
  EXAMPLE:
  
  $GD = new godaddy($key,$secret);

  $domaininfo = $GD->get( "domains/zsombor.net/records/CNAME/myfunnysubdomain" );
  
  $successupdate = $GD->put( "domains/zsombor.net/records/CNAME/myfunnysubdomain", [
    "data" => "www.example.com".
  ]);

  */
  

namespace ZsBT\misc;
  
class GoDaddy {
  const API_URL = "https://api.godaddy.com/v1";
  
  
  private $key, $secret;
  
  function __construct($key, $secret){
    $this->key = $key;
    $this->secret = $secret;
  }
  
  
  public function get($path, $parms = [] ){
    return $this->apicall($path, "GET", $parms);
  }
  
  public function post($path, $parms = [] ){
    return $this->apicall($path, "POST", $parms);
  }
  
  public function put($path, $parms = [] ){
    return $this->apicall($path, "PUT", $parms);
  }
  
  public function patch($path, $parms = [] ){
    return $this->apicall($path, "PATCH", $parms);
  }
  
  public function del($path, $parms = [] ){
    return $this->apicall($path, "DELETE", $parms);
  }
  
  public function delete($path, $parms = [] ){	// alias for del
    return $this->del($path, $parms=[] );
  }
  
  
  
  private function apicall($path, $method="GET", $parms=[] ){    
    $ch = curl_init();

    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER => false,
      CURLOPT_BINARYTRANSFER => true,
#      CURLOPT_VERBOSE => true,
      CURLOPT_CUSTOMREQUEST => $method,
      
      CURLOPT_HTTPHEADER => [
        sprintf('Authorization: sso-key %s:%s', $this->key,$this->secret),
        "Accept: application/json",
        "Content-Type: application/json",
      ],
      
    ]);
    
    
    $url=sprintf("%s/%s", self::API_URL, $path);
    
    switch($method){
      case "GET":
        $url.="?".http_build_query($parms);
        break;
        
      default:
        curl_setopt($ch, CURLOPT_POSTFIELDS,  @json_encode([$parms]) );
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    $ret = @curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    // if no response in body, let's return with true/false
    if($ret == "{}") return $info["http_code"]==200;
    
    return @json_decode($ret);
    
  }
  
}

