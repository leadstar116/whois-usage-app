<?php	/*

  common functions
  
  */


function parse_changes($charr){	// return the changed values' keys only
  $ret = array();
  foreach($charr as $key=>$val)
    if(is_array($val))$ret = array_merge($ret, parse_changes($val));else
      $ret[] = $key;
  return $ret;
}

function format_changes($changes_json){	// changes in human-readable format
  $arr = parse_changes( json_decode($changes_json,1) );
  return $arr ? implode(", ", $arr) : "-";
}
