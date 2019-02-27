<?php /*

  Zsombor PayPal class v0.1	2011-09
  
  create object with:
  
  new PayPal(array(
    "UserName"	=>	"yourpaypal@api.name",
    "Password"	=>	"apiPassword",
    "Signature" =>	"paypalApiSignature",
  ));
  
  
  CHANGELOG
  
  2016-03	HA! HA! What an old code of mine:) Everybody uses their API instead. - Zsombor
  
*/
namespace ZsBT\misc;

class PayPal {
  
  function __construct($APIUSER){$this->APIUSER=$APIUSER;}
  function isodate($time){if(is_string($time))$time=strtotime($time);return date('Y-m-d\T00:00:00\Z',$time);}
  function msg($msg){echo "zsPayPal: $msg\n";}
  function error($msg,$ret=FALSE){$this->msg("error: $msg");return $ret;}
  function fatal($msg,$errorcode=1){$this->error("FATAL: $msg");exit($errorcode);}

  function Post($methodName_, $parmA) {

	if(!$this->APIUSER) die("No API user\n");
	$APIUSER=(object)$this->APIUSER;
	
	$API_UserName = urlencode($APIUSER->UserName);
	$API_Password = urlencode($APIUSER->Password);
	$API_Signature = urlencode($APIUSER->Signature);
	
	$API_Endpoint = "https://api-3t.paypal.com/nvp";
#	if("sandbox" === $environment || "beta-sandbox" === $environment) {$API_Endpoint = "https://api-3t.$environment.paypal.com/nvp";}
	$version = urlencode('51.0');
	
	$nvpStr_='';foreach($parmA as $k=>$v)$nvpStr_.="&$k=".urlencode($v);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
//	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
	curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
	$httpResponse = curl_exec($ch);
	if(!$httpResponse) return $this->error("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
	$httpResponseAr = explode("&", $httpResponse);
	$ret = (object)array();
	$ret->data=array();
	foreach ($httpResponseAr as $i => $value) {
		$tmpAr = explode("=", $value);
		if(sizeof($tmpAr) > 1) {
		  $k=&$tmpAr[0];$v=urldecode($tmpAr[1]);
		  if(preg_match("/([a-zA-Z_]+)([0-9]+)/",$k,$macsa)){
		    $ret->data[$macsa[2]]->{$macsa[1]}=$v;
                  }else
                  $ret->{$k} = $v;
		}
	}
	if((0 == sizeof($ret)) || !array_key_exists('ACK', $ret)) return
	  $this->error("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
	$ret->success = (("SUCCESS" == strtoupper($ret->ACK) || "SUCCESSWITHWARNING" == strtoupper($ret->ACK)) ? 1:0 ) ;
	return $ret;
  }
  
  function trxlist($from=0,$to=0,$status='Success'){
    if(!$from)$from=time()-7*24*3600;
    $PA=array("STARTDATE"=>$this->isodate($from),"STATUS"=>$status);
    if($to)$PA["ENDTIME"]=$this->isodate($to);
    return $this->Post('TransactionSearch',$PA);
  }
  
  function trxdetail($TRXID){return $this->Post('GetTransactionDetails',array("TRANSACTIONID"=>$TRXID));}

}
?>
