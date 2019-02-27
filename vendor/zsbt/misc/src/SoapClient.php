<?php /*		Zsombor's soap client v0.1
			2011-08

*/

namespace ZsBT\misc;

if(!defined("EOL"))define("EOL","\r\n");

class SoapClient {
	var $mbpfix="@zsSoapClient";			/* basetext for mime boundary */
	var $logfile="/var/log/bedszclient.log";
	var $UserAgent="ZsSOAPclient";
	var $DEBUG=FALSE;
	var $SoapAction='""';
	
	function ZsSoapClient($endpointURI=0) { $this->endpoint=$endpointURI; }
	
	function mime_pack($dataA=array(), $MB){		/* creates mimeparts from array of data (e.g. message + attachments) */
		$EOL = EOL;
		$ret='';$i=0;
		foreach($dataA as $data){
			$i++;
			$O = (object)$data;
			$ret.="--{$MB}{$EOL}";
			switch($O->type){
				case "text/xml":
					$ret.="Content-Type: text/xml; charset=UTF-8".EOL;
				case "text/plain":
					$ret.="Content-Transfer-Encoding: 8bit".EOL;
					break;
				default:
					$ret.="Content-Type: {$O->type}{$EOL}";
					$ret.="Content-Transfer-Encoding: base64{$EOL}";
					$O->data = chunk_split(base64_encode($O->data));
			}
			if($O->ID)$ret.="Content-ID: <{$O->ID}>".EOL;else $ret.="Content-ID: <c$MB#$i>".EOL;
			$ret.=$EOL;
			$ret.=$O->data;
			$ret.=$EOL;
		}
		$ret.="--{$MB}--{$EOL}{$EOL}";
		return $ret;
	}
	
	function mime_unpack($data){
		if("--"!=substr($data,0,2))return array(array("data"=>$data));
		$linea=explode("\r\n",$data);
		if(sizeof($linea)<3)$linea=explode("\n",$data);
		$mb=$linea[0];
		
		$reta=array();$i=-1;$state=$sor=0;
		
		foreach($linea as $line){
			$sor++;
			if($line==$mb){
				$i++;
				$state=0;	/* uj part kovetkezik */
			}
			if($state==1)if($mb!=$ss=substr($line,0,strlen($mb))){	/* adat */
				$reta[$i]["data"].=$line;
			}
			if($state==0){	/* fejlecek kovetkeznek */
				if(!strlen($line))$state=1;{ /* fejlecek vege */
					if(preg_match("/^([a-zA-Z0-9\-]+)\: (.+)/",$line,$macsa)){
						$reta[$i][$macsa[1]]=$macsa[2];
					}
				}
			}
		}
		return $reta;
	}
	
	function log($msg){$t=date("Y-m-d H:i:s")."BedszSoapClient: $msg\n";if($this->DEBUG)file_put_contents($this->logfile,$t,FILE_APPEND);
		echo $t;
	}
	function fatal($msg){$this->log("Fatal: ".$msg);die("\texiting\n");}
	
	function __doPost($dataA){	/* array of mimeparts	*/
		if(!$this->endpoint)$this->fatal("no endpoint");
		$MB = 'MIMEboundary'.microtime(1).$this->mbpfix;
		$pu=parse_url($this->endpoint);
		if(!$dataA[0])$dataA=array($dataA);
		$cstart=$dataA[0]["ID"];
		if(!$cstart)$cstart="c$MB#1";
		$content=$this->mime_pack($dataA,$MB);
		$this->__last_request=
		$sparm = array("http"=>array("method"	=>"POST"
			,"header"	=>""
					."Host: ".$pu['host'].EOL
					."User-Agent: ".$this->UserAgent.EOL 
					."MIME-Version: 1.0".EOL
					."Content-Type: multipart/related; charset=UTF-8; boundary=\"$MB\"; type=\"text/xml\"; start=\"<$cstart>\"".EOL
					."Content-Length: ".strlen($content).EOL
					."Connection: close".EOL
					."SOAPAction: ".$this->SoapAction
			,"content"	=>$content
			));
		if($this->DEBUG)$this->log($sparm["http"]["content"]);
		$ctx = stream_context_create($sparm);
		$this->__last_reponse=
		$response = file_get_contents($this->endpoint, FILE_TEXT, $ctx);
		if($this->DEBUG)$this->log($response);
		return $response;
	}
	
	function __doRequest($msg){
		$p = $this->__doPost(array(
			"type"	=>"text/xml",
			"data"	=>$msg,
			));
		return $p;
	}
	
}

?>
