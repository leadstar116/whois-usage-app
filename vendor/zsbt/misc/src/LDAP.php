<?php /*
        
    (c) 2013 Zsombor's ldap class v.01
    
    requires php5-ldap
*/


namespace ZsBT\misc;

class LDAP {
  var $queryName, $queryPass;	// the user to bind with - optional if you use login (will set)
  
  function __construct($domain,$svr=false,$DN=false){	/* if server and DN are false, will be set based on domain (FDQN) */
    if(false===$svr)$svr=$domain;
    if(false===$DN)$DN="DC=".str_replace(".",",DC=",$domain);
    $this->svr=$svr;$this->domain=$domain;$this->DN=$DN;
    $this->conn = ldap_connect($svr) or $this->die("cannot connect to $svr");
    $this->lbind=0;
    ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION,3);
    ldap_set_option($this->conn, LDAP_OPT_REFERRALS,0);
  }
  function msg($s){echo "bahLDAP: $s\n";}
  function fatal($s,$error=1){$this->msg($s);exit($error);}
  function bind(){
    return $this->lbind=ldap_bind($this->conn,"{$this->queryName}@{$this->domain}",$this->queryPass);
  }
  function login($name,$pass){
    if(!($this->bind($this->queryName=$name,$this->queryPass=$pass)))return FALSE;
    return ($user=$this->getobject("samaccountname=$name"));
  }
  function search($filter,$DN=0,$type=array("*")){
    if(!$this->lbind)$this->bind();if(!$DN)$DN=$this->DN;
    if(!$sr=ldap_search($this->conn,$DN,"($filter)",$type))return FALSE;//$this->fatal("search error");
    return ldap_get_entries($this->conn,$sr);
  }
  function getobjects($filter,$DN=0,$type=array("*")){	// returns list of objects
    $ia=$this->search($filter,$DN,$type);if(!$ia || !$ia["count"]) return NULL;
    $reta=array();
    foreach($ia as $i){
      $ret = new \stdClass;
      if(is_array($i)){
        foreach($i as $k=>$v)
          if(!is_int($k)){
            if( $v && isset($v['count']) && ($v['count']==1))$val=$v[0];
            else {
              if(is_array($v))
                if($v['count'])unset($v['count']);
                $val=$v;
              }
            $ret->{$k}=$val;
          }
          $reta[]=(object)$ret;
        }
      }
    return $reta;
  }
  function getobject($filter,$DN=0,$type=array("*")){	// returns one object
    $oa = $this->getobjects($filter,$DN,$type);
    return sizeof($oa)? $oa[0]:NULL;
  }
  function ingroup($who,$group){	// object or filter string ( 'cn=Guest' , 'samaccountname=bh01934tt')
    if(!is_object($who))$who=$this->getobject("$who"); if(!$who)return NULL;
    if(!is_object($group))$group=$this->getobject("$group");	if(!$group)return NULL;
    return in_array($who->dn,$group->member);
  }
  function is_admin($who){return $this->ingroup($who,"cn=Enterprise Admins");}
}
?>
