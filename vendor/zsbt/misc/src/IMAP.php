<?php	/*

    Zsombor pop3/imap class
    
*/


namespace ZsBT\misc;

class IMAP {
  public function __construct($creda){		// needs an associated array with 'url', 'user', 'pass'
    $creds=(object)$creda;
    if(!$this->CONN=imap_open($creds->url,$creds->user,$creds->pass)){echo "error opening folder $url ";return FALSE;}
  }
  
  
  // functions you will use
  
  
  // mailbox statistics
  public function stat(){ 
      $check = imap_mailboxmsginfo($this->CONN); 
      return ((array)$check); 
  } 
  
  // messages in folder
  public function listfolder($message="") { 
      if ($message) { $range=$message; } else { $MC = imap_check($this->CONN); $range = "1:".$MC->Nmsgs; } 
      $response = imap_fetch_overview($this->CONN,$range); 
      foreach ($response as $msg) $result[$msg->msgno]=(array)$msg; return $result; 
  } 
  
  
  // read mail header
  public function retr($message) { return(imap_fetchheader($this->CONN,$message,FT_PREFETCHTEXT)); } 
  
  
  // read mail body
  public function body($message) { return(imap_body($this->CONN,$message)); } 
  
  
  // delete message
  public function dele($message) { return(imap_delete($this->CONN,$message)); } 
  
  
  // expunge deleted ones
  public function expunge() { return imap_expunge($this->CONN); }
  
  
  // read boundaries
  public function mime_to_array($mid,$parse_headers=false) { 
      $mail = imap_fetchstructure($this->CONN,$mid); 
      $mail = $this->get_parts($mid,$mail,0); 
      if ($parse_headers) $mail[0]["parsed"]=$this->parse_headers($mail[0]["data"]); 
      return($mail); 
  }
  
  
  
  
  
  // helper functions

  private function parse_headers($headers) { 
      $headers=preg_replace('/\r\n\s+/m', '',$headers); 
      preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches); 
      foreach ($matches[1] as $key =>$value) $result[$value]=$matches[2][$key]; 
      return($result); 
  } 

  private function get_parts($mid,$part,$prefix) {    
      $attachments=array(); 
      $attachments[$prefix]=$this->decode_part($mid,$part,$prefix); 
      if (isset($part->parts)) {
          $prefix = ($prefix == "0")? "":"$prefix."; 
          foreach ($part->parts as $number=>$subpart) 
              $attachments=array_merge($attachments, $this->get_parts($mid,$subpart,$prefix.($number+1))); 
      } 
      return $attachments; 
  } 

  private function decode_part($message_number,$part,$prefix) { 
      $attachment = array(); 
      if($part->ifdparameters) { 
          foreach($part->dparameters as $object) { 
              $attachment[strtolower($object->attribute)]=$object->value; 
              if(strtolower($object->attribute) == 'filename') { 
                  $attachment['is_attachment'] = true; 
                  $attachment['filename'] = $object->value; 
              } 
          } 
      } 

      if($part->ifparameters) { 
          foreach($part->parameters as $object) { 
              $attachment[strtolower($object->attribute)]=$object->value; 
              if(strtolower($object->attribute) == 'name') { 
                  $attachment['is_attachment'] = true; 
                  $attachment['name'] = $object->value; 
              } 
          } 
      } 

      $attachment['data'] = imap_fetchbody($this->CONN, $message_number, $prefix); 
      if($part->encoding == 3) { $attachment['data'] = base64_decode($attachment['data']); } 
      elseif($part->encoding == 4) { $attachment['data'] = quoted_printable_decode($attachment['data']); } 
      return($attachment); 
  } 
  
}


?>
