<?php

// serve the ajax calls


define("TABLE", "tblUnprocessedDomains");

require_once("../setup.php");
$DB = &$whois->DB;	// our PDO database object
$LOG = &$whois->log;  // logger

$R = (object)$_REQUEST;

$ret = false;


// update a field
if($ID=$R->pk){
  $ret = $R;
  $ret = $DB->update(TABLE, [$R->name => $R->value], "ID={$ID}");
}

// load templates
elseif(isset($R->tpls)){
  $ret = [];
  foreach($DB->allRow("select * from tblTemplate")as $tpl){
    foreach($DB->allRow("select ID,detailsjson from tblTemplateAttachment where template={$tpl->ID}")as $rec){
      $tatt = @json_decode($rec->detailsjson);
      $tatt->ID=$rec->ID;
      $tpl->atts[] = $tatt;
    }
    $ret[] = $tpl;
  }
}

//save template
elseif($id=$R->tplsave){
  $IA = [
    'name'=>$R->name,
    'subject'=>$R->subject,
    'body'=>$R->body,
  ];
  if($id>0){
    $LOG->info("update template #$id");
    $ret = ($DB->update('tblTemplate',$IA,"ID=$id") ? $id : false);
  }elseif($id==-1){
    $ret = $DB->insert('tblTemplate',$IA);
    $LOG->info("insert new template $ret");
  }else $ret = false;
  $LOG->debug($ret);
}

// delete template
elseif($id=$R->deltpl){
  if($id==-1)$ret = true;
  elseif($DB->oneValue("select count(1) from tblTemplateSent where domainID=$id")){
    $LOG->warn("will not delete template #$id as it has sent emails");
    $ret = false;
  }
  else {
    $ret= $DB->exec("delete from tblTemplate where ID=$id");
    $LOG->info("delete template #$id");
  }
}

// add attachment to template
elseif($tplid=@$R->addattach){
  $fil = $_FILES['file'];
  $r = $DB->insert("tblTemplateAttachment",[
    "template"	=>$tplid,
    "detailsjson" =>json_encode($fil),
    "bindata"	=>@base64_encode(@file_get_contents($fil['tmp_name'])),
  ]);
  $LOG->info("attachment added to template $tplid. success:".json_encode($r) );
  if(!$r)$LOG->debug("attachment file=".json_encode($_FILES).", request: ".json_encode($_REQUEST) );
  $ret = $r;
}

// delete attachment
elseif($attid=@$R->delattach){
  $LOG->info("delete attachment $attid");
  $ret = true;
}

// start campaign
elseif(isset($R->emailTrigger)){
  $ret = $whois->send_campaign($R->tpl, $R->ids);
}

// unsubscribe
elseif( ($uns=$R->unsubscribe) && ($md5 = substr($uns,0,32)) && ($id = 0+substr($uns,32)) ){
  if($md5==md5($DB->oneValue("select RegistrantContactEmail from tblUnprocessedDomains where ID=$id")))
    $ret = $DB->exec("update tblUnprocessedDomains set unsubscribed=CURRENT_TIMESTAMP where ID=$id");
  $LOG->info("unsubscribing domain#$id success is ".json_encode($ret));
}

// add host company
elseif($co=$R->newCompany){
  $ret = $DB->insert('tblTargetCompany',['Name'=>$co]);
  $LOG->info("company '$co' added");
}

elseif($coid=0+$R->delCompany){
  $ret = $DB->exec("delete from tblTargetCompany where id=$coid");
  $LOG->info("company #$coid deleted");
}

elseif($coid=$R->forceProcessDomains){
  $ret = $LOG->info("force process domains");
  include(__DIR__."/../cronjob/dis-task.php");
}

// add new, unprocessed domains
elseif($list=$R->list){
  $inserted=0;
  foreach( explode("\n",str_replace("\r","\n",$list))as $line)if(strlen($line=trim($line))){
    if(!$DB->oneValue("Select count(1) from ".TABLE." where Domain='$line'"))
    if($DB->insert(TABLE, [
      'Domain'=>$line,
      'TargetCompanyID'=>$R->host,
      'RespectiveURL'=>$R->url,
    ]))$inserted++;
  }
  $ret['inserted']=$inserted;
  $LOG->info("added $inserted unprocessed domain");
}

// retrieve data from tables
elseif($table=($R->table)){
  $ret = [];
  $DB->iterate("select * from $table", function($rec) use (&$ret){
    try {
      if($rec->MXRecord)$rec->MXRecord = implode(", ",json_decode($rec->MXRecord));
      $rec->RegistrantContactEmail = strtolower($rec->RegistrantContactEmail);
      $rec->TechEmail = strtolower($rec->TechEmail);
    } catch (Exception $E) {
    }
    $ret[] = $rec;
  });
  $LOG->debug("table/view $table queried");
}

// update a record 
elseif(isset($R->domainupdate)){
  $UA=[];
  foreach($R->form as $field)
    $UA[$field['name']] = $field['value'];
  $ret = $DB->update(TABLE, $UA, "ID={$R->domainupdate}");
  $LOG->info("domain {$R->domainupdate} updated");
}

// delete record
elseif($did = $R->domaindelete){
  $ret = $DB->exec(sprintf("delete from %s where id in (%s)", TABLE, $did) );
  if($ret)$LOG->info("domain #$did deleted");else
    $LOG->warn("domain #$did could not be deleted");
}

// discard prospective
elseif($disp=$R->discardProspective){
  $ret = $DB->update("tblUnprocessedDomains",[
    'discarded'=>date("Y-m-d H:i:s"),
  ],"ID in ($disp)");
  $LOG->info("domain(s) #$disp discarded");
}



/******** work done, output results ***********/
header("Content-type: text/json");
print(json_encode($ret));


# http://bootstrap-table.wenzhixin.net.cn/documentation/
# http://issues.wenzhixin.net.cn/bootstrap-table/Q