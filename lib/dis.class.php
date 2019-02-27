<?php	/*

  domain information system class for Scott
  
  - follow-up changes, store them in DB 


  */


use \ZsBT\misc\Cache;


class dis {
  
  const TimestampFormat = "Y-m-d H:i:s";	// do not change this

  function __construct($configFile="config.js"){	// connect to DB
    // parse config file
    $this->config = json_decode(@file_get_contents($configFile));
    
    // database
    if(!$DB = new \ZsBT\misc\PDO($this->config->pdo_db))
      throw new Exception("Cannot create DB connection {$this->config->pdo_db}");
    $this->DB = &$DB;
    
    // setup cache
    Cache::$TIMEOUT = $this->config->cache->timeout;
    if(!file_exists($dir = $this->config->cache->dir)){
      if(!mkdir($dir,0777,true))throw new Exception("Cannot create cache dir '$dir'");
    }
    Cache::$PREFIX = "$dir/.zsc-";
    umask(0000);
   
    $this->whois = new Novutec\WhoisParser\Parser();
    $this->novuConfig = new Novutec\WhoisParser\Config\Config;
    
    $this->log = new \ZsBT\misc\Logger;
    foreach(['debug','info','warn','error']as $loglevel){
      $attr = strtoupper("outfile_$loglevel");
      $this->log->{$attr} = $this->config->logfile->{$loglevel};
    }
  }

  
  // rate limit functions
  function tld($domain){
    if(preg_match('/([a-z]+)$/', $domain, $ma)) return $ma[1];
    $this->log->error("cannot find tld of domain '$domain'");
    return false;
  }
  
  function whoisServerID($domain){
    $host = $this->novuConfig->get($tld=self::tld($domain))['server'];
    if(!$host)return false;
    if(!$id=$this->DB->oneValue("select id from tblWhoisServer where Hostname='$host'"))
      $id = $this->DB->insert("tblWhoisServer", ['Hostname'=>$host,'tld'=>$tld]);
    $this->log->debug("whoisServerID($domain)=$id");
    return $id;
  }
  
  function whoisServer($domain){
    $ret = $this->novuConfig->get($tld=self::tld($domain))['server'];
    if(!$ret)
      $this->log->error("no whois server for tld '$tld' !");
    else $this->whoisServerID($domain);
    return $ret;
  }
  
  function dailyUsage($domain, $assistantID=1){	// per whois server
    if(!$whoisServer=$this->whoisServerID($domain))return false;
    $yesterday = self::now(strtotime("now - 1 day"));
    $sql = "select count(1) from tblWhoisServerQuery where whoisServer=$whoisServer and assistant=$assistantID and queryTimestamp>='$yesterday'";
    $ret = $this->DB->oneValue($sql);
    $this->log->debug("dailyUsage($domain, $assistantID)=$ret");
    return $ret;
  }

  function dailyLimitLeft($domain, $assistantID=1){
    if(!$tld = $this->tld($domain))return false;
    $limit = $this->DB->oneValue("select dailyLimit from tblWhoisServer where tld='$tld'");
    if($limit==NULL){
      $this->DB->insert("tblWhoisServer", ['Hostname'=>$this->whoisServer($domain), 'tld'=>$tld]);
      $limit = $this->DB->oneValue("select dailyLimit from tblWhoisServer where tld='$tld'");
    }
    $limitLeft = $this->DB->oneValue("select dailyLimit from tblWhoisServer where tld='$tld'") - $this->dailyUsage($domain, $assistantID);
    $this->log->debug("dailyLimitLeft($domain, $assistantID)=$limitLeft");
    return max(0,$limitLeft);
  }
  
  
  private function sendmail($subject, $body){	// send out an email notification
    return mail($this->config->email->to, "domainUpdate: $subject", $body
      ,"From: monitor@".trim(file_get_contents("/etc/mailname"))
    );
  }
  
  
  // store functions
  public function companyID($name){
    $Name = str_replace("'","''",$name);
    if(!$id=$this->DB->oneValue("Select id from tblTargetCompany where Name='$name'"))
      $id = $this->DB->insert("tblTargetCompany", ['Name'=>$Name]);
    return $id;
  }
  
  
  public function nameserverID($name){
    if(!is_string($name))$name = json_encode($name);
    $Name = str_replace("'","''",$name);
    if(!$id=$this->DB->oneValue("Select id from tblNameservers where Nameservers='$name'"))
      $id = $this->DB->insert("tblNameservers", ['Nameservers'=>$Name]);
    return $id;
  }
  

  public function mxrecordID($name){
    if(!is_string($name))$name = json_encode($name);
    $Name = str_replace("'","''",$name);
    if(!$id=$this->DB->oneValue("Select id from tblMXRecords where MXRecord='$name'"))
      $id = $this->DB->insert("tblMXRecords", ['MXRecord'=>$Name]);
    return $id;
  }
  
  
  public function domainID($domain){
    if(!$id=$this->DB->oneValue("Select id from tblUnprocessedDomains where Domain='$domain'")){
      if(!$wi = $this->whois2table($domain) )return false;
      $id = $this->DB->insert("tblUnprocessedDomains",$wi);
      return $id;
    }
    return $id;
  }
  
  
  
  
  //	whois query section
  
  public function candidateAssistant($domain){
    // pick the most convenient node for the whois query
    $whoisserver = $this->whoisServerID($domain);
    $yesterday = self::now(strtotime("now - 1 day"));
    
    $this->log->error("could not find whoisserver for domain $domain!");
    
    $candidates = $this->DB->allRow($sql="select a.*
      ,(select count(1) from tblwhoisserverquery q where q.assistant=a.id and whoisserver=$whoisserver and queryTimestamp>'$yesterday') as hit
      ,(select dailylimit from tblwhoisserver s where id=$whoisserver) as dailyLimit 
      from tblassistant a
      where hit < dailyLimit order by hit
    ");
    
    $this->log->debug("candidateAssistant($domain)=".json_encode($candidates));
    return $candidates;
  }


  public function whois($domain){    
    if($ret = Cache::get($key="whois$domain")){
      $this->log->debug("whois $domain from cache");      
      return $ret;
    }
    
    $ret = false;
    
    $candidates = $this->candidateAssistant($domain);    

    if(!count($candidates)){
      $this->log->warn("no assistants left for $domain");
    } else
    foreach( $candidates as $candidate ){
      // assistant exhausted?
      
      $this->log->debug("{$candidate->hit} >=? {$candidate->dailyLimit}");
      
      if($candidate->dailyLimit && ($candidate->hit >= $candidate->dailyLimit) ){
        $this->log->warn("assistant #{$candidate->Assistant} exchausted @ $domain");
      }else{
        // let's try to query using the chosen assistant
        $this->log->debug("whois query $domain using assistant #{$candidate->ID}"); 
        if($ret = $this->lookup($domain, $candidate->ID)){
          Cache::set($key, $ret);
          return $ret;
        }
      }
    }
    return $ret;
  }
  

  // lookup the domain using a given assistant (remote host)
  function lookup($domain, $AssistantID){
    $assistant = $this->DB->oneRow("select * from tblAssistant where ID=$AssistantID");
    $this->log->info("query $domain using host {$assistant->Hostname}");
    
    // are we too fast?
    $latestQuery = $this->DB->oneValue($sql="select queryTimestamp from tblWhoisServerQuery 
      where assistant=$AssistantID and whoisServer=".$this->whoisServerID($domain)
      ." order by id desc limit 1 ");
    $unixEpoch = strtotime($this->DB->oneValue("Select CURRENT_TIMESTAMP"));
    
    if($latestQuery){
      $elapsedSeconds = ($unixEpoch - strtotime((string)$latestQuery));
      
#      $this->log->debug("elapsed $elapsedSeconds secs since $latestQuery, sql=$sql");
      if($elapsedSeconds < $this->config->whois->delaySeconds){
        $wait = $this->config->whois->delaySeconds - $elapsedSeconds;
        $this->log->info("we are too fast, waiting for $wait seconds");
        sleep($wait);
      }
    }
    
    // shall it be localhost, we don't use SSH
    if($assistant->Hostname=='localhost') {      
      $ret = $this->whois->lookup($domain);
      file_put_contents("/tmp/$domain.whois", json_encode($ret,JSON_PRETTY_PRINT) );
    } else {
      // let's SSH to the remote host.
      $remotecmd = sprintf($this->config->whois->remoteExec, $domain);
      $localcmd = sprintf('%s %s@%s "%s"', $this->config->whois->sshFullPath, $assistant->UserName, $assistant->Hostname, $remotecmd);
      exec($localcmd, $outputArray, $retval);
      if($retval){
        $this->log->error("could not execute: $localcmd");
        return false;
      }
      if(!preg_match("/#<#(.+)#>#/", $output=implode("",$outputArray), $ma)){
        $this->log->error("could not parse response: '$output'");
        return false;
      }
      $ret = unserialize( base64_decode($ma[1]) );
      if(!$ret){
        $this->log->debug("output: $output");
        $this->log->debug($ret);
      }
    }

    
    if(!$this->DB->insert($T='tblWhoisServerQuery', $IA=['whoisServer'=>$this->whoisServerID($domain), 'Assistant'=>$AssistantID]))
      $this->log->error("cannot insert into $T : ".json_encode($IA) );
    
    return $ret;
  }
  
  
  private function now($unixEpoch=null){	// current datetime
    if(!$unixEpoch)$unixEpoch = time();
    return date(self::TimestampFormat, $unixEpoch);
  }

  
  function whois2table($domain){	// convert domain whois result to tblUnprocessedDomains structure
    $wi = $this->whois($domain);	// structured whois data
    
    if(!$wi){
      $this->log->debug("got no whois data for $domain");
      return false;
    }
    $rawdata = &$wi->rawdata[0];
    
    $owner = &$wi->contacts->owner[0];
    $tech = &$wi->contacts->tech[0];
    
    $svrid = $this->whoisServerID($domain);
    
    $ret = false;
    
    if(!$owner->name) {      
      $startPos = strpos($rawdata, "Registrant Contact Name:");
      if($startPos !== FALSE) {
        $startPos += strlen("Registrant Contact Name:") + 1;
        $subStr = substr($rawdata, $startPos, 20);
        $owner->name = strtok($subStr, "\n");        
      }      
    }

    if(!$owner->organization) {
      $startPos = strpos($rawdata, "Registrant:");
      if($startPos !== FALSE) {
        $startPos += strlen("Registrant:") + 1;
        $subStr = substr($rawdata, $startPos, 20);
        $owner->organization = strtok($subStr, "\n");
      }
    }

    if(
      preg_match("/^(no match for domain)/i", $rawdata,$ma)
      ||
      preg_match("/^(no data found)/i", $rawdata,$ma)
    ){
      $ret = [
        'Registrant'=>$msg="$ma[1] @{$wi->whoisserver}",
        'MXID'	=>$this->mxrecordID( mxcheck::mxrecord($domain) ),
        'ProcessedDateTime' =>self::now(),
        'NotExists' =>1,
      ];
      $this->log->warn($msg);
    }
    elseif(!$tech){
      $today = date("Y-m-d");
      $usage = $this->DB->oneValue("select count(1) from tblWhoisServerQuery where whoisServer=$svrid and queryTimestamp between '$today 00:00:00' and '$today 23:59:59'");
      $this->log->warn("no values from {$wi->whoisserver} (#$svrid). ".trim($rawdata) );
      unset($wi->rawdata);
      $this->log->debug("wi: ".json_encode($wi));
      $ret = false;
    }
    else $ret= [
      'Registrant'	=>$owner->organization,
      'EligibilityID' =>$owner->eligibility_id ? $owner->eligibility_id : $owner->handle,
      'RegistrantContactName' =>$owner->name,
      'RegistrantContactEmail' =>(filter_var($owner->email, FILTER_VALIDATE_EMAIL))? $owner->email:'',
      'Phone' =>$owner->phone,
      'TechName' =>$tech->name,
      'TechEmail' =>(filter_var($tech->email, FILTER_VALIDATE_EMAIL))? $tech->email:'',
      'TechPhone' =>$tech->phone,
      'Domain'=>$domain,
      'MXID'	=>$this->mxrecordID( mxcheck::mxrecord($domain) ),
      'NameserverID'	=>$this->nameserverID( json_encode($wi->nameserver) ),
      'TargetCompanyID'	=>$this->companyID($owner->organization),
      'ProcessedDateTime' =>self::now(),
      'Changed'	=>$wi->changed,
      'Expiry'	=>$wi->expires,
      'is_google'	=>!!mxcheck::is_google($domain),
      'is_ms'	=>!!mxcheck::is_ms($domain),
    ];
    
    $this->log->debug("whois2table($domain)=".json_encode($ret));
    return $ret;
  }
  

  
  public function process_all(){	// poll whois updates (if allowed)
    $limit = 20;
    $this->DB->iterate("select id, Domain from tblUnprocessedDomains where ProcessedDateTime='' limit $limit ", function($rec)  {

      $this->log->info("inspecting {$rec->Domain}");      
      if(  (($is_google=!!mxcheck::is_google($rec->Domain)) || ($is_ms=!!mxcheck::is_ms($rec->Domain))) ){
        $r = 
        $this->DB->insert("tblManagedMailDomains", [
          'Domain'	=>$rec->Domain,
          'is_google'	=>$is_google,
          'is_ms'	=>$is_ms,
        ]) &&
        $this->DB->exec("Delete from tblUnprocessedDomains where ID={$rec->ID}");
        
        if($is_google)$this->log->info("{$rec->Domain} mail is managed by Google");
        if($is_ms)$this->log->info("{$rec->Domain} mail is managed by Office365");
        
        if(!$r)$this->log->error("could not move {$rec->Domain} from tblUnprocessedDomains to tblManagedMailDomains!");
      }else{
        // not Google, nor Microsoft
        $UA = $this->whois2table($rec->Domain);
        if($UA){
          if(!$this->DB->update('tblUnprocessedDomains', $UA, "id={$rec->ID}"))
            $this->log->error("table tblUnprocessedDomains could not be updated for id#{$rec->ID}");
          else
            $this->log->info("{$rec->Domain} is processed");
        } else {
          $this->log->error("{$rec->Domain} could not be processed" );
          $this->log->debug($UA);
        }
      }
      
    });
  }
  
  // start an email campaign, using the specified template
  function send_campaign($templateID, $domainIDs){
    $ret = new stdClass;
    $tplid = 0+$templateID;
    $ret->sent = $ret->failed = 0;

    if(!$tpl=$this->DB->oneRow("select * from tblTemplate where ID=$tplid")){
      $ret->error = "Template #$tplid does not exist";
    }elseif(!count($domainIDs)){
      $ret->error = "No emails given";
    }else{
      $dids = implode(",",$domainIDs);
      $LOG = &$this->log;
      $DB = &$this->DB;
      $LOG->info("initiate campaign for template #$tplid");
      
      /* extract attachments if any */
      $attachmentfilenames = [];
      foreach($this->DB->allRow("select * from tblTemplateAttachment where template=$tplid")as $rec){
        $details = json_decode($rec->detailsjson);
        $wb = file_put_contents($fn="{$this->config->cache->dir}/{$details->name}", @base64_decode($rec->bindata) );
        if(!$wb)throw new \Exception("cannot write file $fn or attachment for template #$tplid is empty");
        $attachmentfilenames[] = $fn;
      }
      
      $this->DB->iterate("select ud.ID,RespectiveURL as url,RegistrantContactEmail as email, co.name as host
        ,RegistrantContactName name
         from tblUnprocessedDomains ud
         left join tblTargetCompany co on TargetCompanyID=co.ID
         where ud.ID in ($dids)
         and discarded is null and unsubscribed is null
         ",function($rec) use ($DB,$LOG,$tpl,$tplid,&$ret,$attachmentfilenames){
        if(!$DB->insert('tblTemplateSent',['templateID'=>$tplid,'domainID'=>$rec->ID]) ){
          $LOG->warn("will not send to {$rec->email} . (already sent?)");
        }else{
          $LOG->info("sending mail to domain #{$rec->ID}");
          $body = @str_replace('{URL}', $rec->url, $tpl->body);
          $body = @str_replace('{TARGET}', $rec->host, $body);
          $body = @str_replace('{FIRSTNAME}', explode(" ",$rec->name)[0], $body);
          $body = @str_replace('{UNSUBSCRIBE}', sprintf($this->config->mail->unsubscribe, md5($rec->email).$rec->ID ), $body );
          if($this->send_mail($rec->email, $tpl->subject, $body, $attachmentfilenames))
            $ret->sent++; 
          else
            $ret->failed++;
        }
      });
    }
    return $ret;
  }
  
  
  // send an email to sy
  function send_mail_obsolete($email, $subject, $body){
    $sent = mail($email, html_entity_decode($subject), $body
      ,"From: {$this->config->mail->from}\r\n"
      ."Content-Type: text/html\r\n"
      ,"-r {$this->config->mail->returnPath}"
    );
    return $sent;
  }
  
  function send_mail($to, $subject, $body, $attachments=[] ){
    advanced_email::$FELADO = $this->config->mail->from;
    advanced_email::$XMailer = 'RGB mailer';
    $em = new advanced_email;
    $em->EMAILCIM = $to;
    $em->TARGY = $subject;
    $em->TORZS_HTML = $body;
    $em->MELLEKLETEK=$attachments;
    $r = $em->kuld();
    $this->log->info("email to {$to} with ".count($attachments)." attachments ".($r?'success':'FAIL'));
    return $r;
  }
  
}




class advanced_email {

    public $EMAILCIM, $TARGY, $TORZS_TXT, $TORZS_HTML, $MELLEKLETEK=[], $FILENEVEK=[];
    
    public static $FELADO = "undefined-felado <undefined@send.er>";
    public static $XMailer;
    
    private $boundary;
    
    function __construct(){
        $this->boundary = sprintf("----=_%s_boundary", uniqid());
    }
    
    
    function kuld(){
        if(!self::$XMailer)self::$XMailer = sprintf("%s@%s", __CLASS__, "BMH" );
    
        if(!strlen(self::$FELADO)*strlen($this->EMAILCIM)*strlen($this->TARGY))
            throw new \Exception("FELADO, EMAILCIM vagy TARGY hianyzik");
            
        $HA = [
            "From"=>	self::$FELADO,
            "Content-Type"=>	sprintf('multipart/mixed; boundary="%s"', $this->boundary),
            "X-Mailer"=>	self::$XMailer,
#            "To"=>	$this->EMAILCIM,
            "MIME-Version"=>	"1.0",
            "Date"=>	date("r"),
        ];
        
        $HS = '';
        foreach($HA as $k=>$v)
            $HS.="$k: $v\r\n";
        
        $B = &$this->boundary;
        
        $TORZS = '';
        if($this->TORZS_TXT)
            $TORZS.= "--$B\nContent-Type: text/plain; charset=\"utf-8\"\nContent-Transfer-Encoding: binary\n\n{$this->TORZS_TXT}\n\n";
        
        if($this->TORZS_HTML)
            $TORZS.= "--$B\nContent-Type: text/html; charset=\"utf-8\"\nContent-Transfer-Encoding: binary\n\n{$this->TORZS_HTML}\n\n";
        
        foreach($this->MELLEKLETEK as $mi=>$mellfn)if(file_exists($mellfn)){
            $bfn = isset($this->FAJLNEVEK[$mi]) ? $this->FAJLNEVEK[$mi] : basename($mellfn);
            $ct = mime_content_type($mellfn);
            $TORZS.=sprintf("--%s\nContent-Type: %s; name=\"%s\""
                ."\nContent-Transfer-Encoding: base64"
                ."\nContent-Disposition: attachment; filename=\"%s\""
                ."\n\n"
                , $B, $ct, $bfn, $bfn
            );
            $TORZS.= @chunk_split(@base64_encode(@file_get_contents($mellfn)),76,"\n");
            $TORZS.="\n\n";
        }
        
        $TORZS.="--$B--\n";
        
        $addparms = '';
        if(preg_match('/([^<]+@[^>]+)/i',self::$FELADO,$ma))
            $addparms .= " -f ".$ma[1];
        
        $r = mail($this->EMAILCIM
            , sprintf("=?UTF-8?B?%s?=", base64_encode($this->TARGY))
            , $TORZS, $HS, $addparms
        );
        syslog(LOG_NOTICE,"email to {$this->EMAILCIM} ".($r ? "sent":"FAILED") );
        return $r;
    }
    
    
}

