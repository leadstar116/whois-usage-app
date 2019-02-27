<?php	/*

  show details for given domain
  
  */

require_once("../setup.php");


// show whois raw data only?
if(
  ($domain = $_GET["rawdata"])
  &&
  ($ts = $_GET["ts"])
  ){
  
  $domainID = $whois->domain_id($domain);
  if(!$dataStr = $whois->DB->oneValue("select fulldata_json from tblDomainInformation where domainID=$domainID and updated_epoch=$ts"))
  die("No data available");else {
    $data = json_decode($dataStr);
    header("Content-type: text/plain; charset=utf-8");
    die( json_encode($data, JSON_PRETTY_PRINT) );
  }
}




//	list history of given domain

$domain = $_GET["domain"];
$domainID = $whois->domain_id($domain);

$chgStr = $whois->DB->oneValue("select changes_json from tblDomainInformation where domainID=$domainID and not changes_json is null order by updated_epoch desc");
$dtaStr = $whois->DB->oneValue("select fulldata_json from tblDomainInformation where domainID=$domainID and not fulldata_json is null order by updated_epoch desc");
if($dtaStr){
  echo @str_replace("\n", "<br>", @json_encode(@json_decode($dtaStr),JSON_PRETTY_PRINT+JSON_NUMERIC_CHECK) );
}else{
  echo "No data available yet.";
}
exit;


foreach($whois->DB->allRow("select * from tblDomainInformation where domainID=$domainID order by updated_epoch desc")as $row){

  $changes = $row->changes_json ?	// any change?
    format_changes($row->changes_json) : "-";
  
  $changes.=" <a title='Full WHOIS record' href='details.php?rawdata=$domain&ts={$row->updated_epoch}'>&raquo;</a>";
  
  $detlist .= sprintf("<tr><td>%s<td>%s"
    ."<td>%s<td>{$changes}"
    ,date("m/d/Y", $row->updated_epoch)
    ,$row->registered ? "YES":"NO"
    ,$row->expiry_date ? $row->expiry_date : "?"
  );
}


echo <<<PAGE
<h1>Domain details: $domain</h1>
  
<table class=border id=ddetails>
  <thead><tr><th>Checked<th>Registered<th>Expiry<th>Changes
  <tbody>$detlist</tbody>
</table>

PAGE;
