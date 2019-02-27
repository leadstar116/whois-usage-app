<?php	/*

 reads a seller's items
 
                                                                       */
namespace ZsBT\misc;

class EBay {
  private $endpoint="http://svcs.ebay.com/services/search/FindingService/v1";
  
  public function __construct($appid){
    $this->appid = $appid;
  }
  
  private function api_call($parms,$results=3){
    $url = $this->endpoint."?SECURITY-APPNAME=".$this->appid
     ."&SERVICE-VERSION=1.0.0"
     ."&RESPONSE-DATA-FORMAT=XML"
     ."&paginationInput.entriesPerPage=3"
     ;
    foreach($parms as $key=>$val)$url.="&$key=$val";
    if(!$s=file_get_contents($url))return false;
    $x = new simpleXMLelement($s);
    return $x;
  }
  
  public function seller_items($seller){
    $p=array(
      "OPERATION-NAME"		=>"findItemsAdvanced",
      "itemFilter(0).name"	=>"Seller",
      "itemFilter(0).value"	=>$seller,
    );
    return $this->api_call($p);
  }
  
}

?>