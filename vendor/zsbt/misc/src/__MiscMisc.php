<?php	/*

  you may want to copy out some of these functions.
  
  */

function detectUTF8($string) {
    return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
        )+%xs', 
    $string);
}





/* converts a big integer to a short string (like Google URL shortener) */
function numtoshortstring($N,$set="23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ"){
  $n=strlen($set);$s='';
  while($N>0){$d=($N % $n);$s=''.$set[$d].$s;$N=round(($N-$d)/$n);}
  return $s;
}
#echo numtoshortstring( microtime(1)*10000 );





function uuid($prefix = '') {
  $chars = md5(uniqid(mt_rand(), true));
  $uuid  = substr($chars,0,8) . '-';  
  $uuid .= substr($chars,8,4) . '-';
  $uuid .= substr($chars,12,4) . '-';
  $uuid .= substr($chars,16,4) . '-';
  $uuid .= substr($chars,20,12);
  return $prefix . strtoupper($uuid);
}




function humanTimeElapsed($seconds, $words=array('seconds','minutes','hours','days','weeks') ){
  if($seconds<0)$seconds=0-$seconds;
  if($seconds>59){$minutes=floor($seconds/60);$seconds=$seconds % 60;}
  if($minutes>59){$hours=floor($minutes/60);$minutes=$minutes % 60;}
  if($hours>23){$days=floor($hours/24);$hours=$hours % 24;}
  if($days>6){$weeks=floor($days/7);$days=$days % 7;}
  
  $ret='';
  if($weeks)$ret.="$weeks {$words[4]} ";
  if($days)$ret.="$days {$words[3]} ";
  if($hours)$ret.="$hours {$words[2]} ";
  if($minutes)$ret.="$minutes {$words[1]} ";
  if($seconds)$ret.="$seconds {$words[0]} ";
  return trim($ret);
}



#
#	for VT100 compatible terminal!
#
# colors and control characters for your php cli script.
#

function AnsiSTR( $str, $opts='' ) {
  $E = "\x1b";
  $ca=array();
  foreach (explode(",",$opts) as $opt) switch($opt) {
    case "RSET": $ca[]="[0m";	break;	//	reset; clears all colors and styles (to white on black)
    case "b":	$ca[]="[1m";	break;	//	bold on (see below)
    case "i":	$ca[]="[3m";	break;	//	italics on
    case "u":	$ca[]="[4m";	break;	//	underline on
    case "I":	$ca[]="[7m";	break;	//	inverse on; reverses foreground & background colors
    case "S":	$ca[]="[9m";	break;	//	strikethrough on
    
    case "black": $ca[]="[30m";	break;	//	set foreground color to black
    case "red":	$ca[]="[31m";	break;	//	set foreground color to red
    case "green": $ca[]="[32m";	break;	//	set foreground color to green
    case "yellow":$ca[]="[33m";	break;	//	set foreground color to yellow
    case "blue": $ca[]="[34m";	break;	//	set foreground color to blue
    case "magenta":$ca[]="[35m";break;	//	set foreground color to magenta (purple)
    case "cyan": $ca[]="[36m";	break;	//	set foreground color to cyan
    case "white":$ca[]="[37m";	break;	//	set foreground color to white
    
    case "bgblack":	$ca[]="[40m";	break;	//	set background color to black
    case "bgred":	$ca[]="[41m";	break;	//	set background color to red
    case "bggreen":	$ca[]="[42m";	break;	//	set background color to green
    case "bgyellow":	$ca[]="[43m";	break;	//	set background color to yellow
    case "bgblue":	$ca[]="[44m";	break;	//	set background color to blue
    case "bgmagenta":	$ca[]="[45m";	break;	//	set background color to magenta (purple)
    case "bgcyan":	$ca[]="[46m";	break;	//	set background color to cyan
    case "bgwhite":	$ca[]="[47m";	break;	//	set background color to white
    
    case "EL2":     $ca[]="[2K"; break;         // Clear entire line
    case "CUB":     $ca[]="[1D"; break;		// cursor back
    case "CUB10":   $ca[]="[10D"; break;	// cb 10 chars
    case "CUB20":   $ca[]="[20D"; break;	// cb 20 chars
    case "CUB50":   $ca[]="[50D"; break;	// cb 50 chars
    case "CUB100":  $ca[]="[100D"; break;	// cb 100 chars

  }
  
  $prefix="";
  foreach ($ca as $c) $prefix.= ($E.$c);
  return $prefix.$str. $E."[0m";
}






 /* 

  Simple crypt/decrypt function with salt.
  Cracking is too easy to use in production environment!
  With production data, you should use mcrypt.
  
  */

function encrypt($str, $salt="WriteSomeThingUniqueHere") {
	$salt = md5($salt);
	$out = '';
	$str = gzdeflate($str,9);
	for ($i = 0; $i<strlen($str); $i++) {
		$kc = substr($salt, ($i%strlen($salt)) - 1, 1);
		$out .= chr(ord($str{$i})+ord($kc));
	}
	$out = base64_encode($out);
	$out = str_replace(array('=', '/'), array('', '-'), $out);
	return $out;
}


function decrypt($str, $salt="WriteSomeThingUniqueHere") {
	$salt = md5($salt);
	$out = '';
	$str = str_replace('-', '/', $str);
	$str = base64_decode($str);
	for ($i = 0; $i<strlen($str); $i++) {
		$kc = substr($salt, ($i%strlen($salt)) - 1, 1);
		$out .= chr(ord($str{$i})-ord($kc));
	}
	return gzinflate($out);
}





?>
