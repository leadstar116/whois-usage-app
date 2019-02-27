<?php /* 

    converts an object or array to SimpleXMLelement

*/


namespace ZsBT\misc;

class ob2xml {
    var $resX;
    function asxml($ob) {$this->iteratechildren($ob,$this->resX);return $this->resX->asXML();}
    function __construct($root){$this->resX = new SimpleXMLElement("<$root/>");}
    private function iteratechildren($ob,$xml){
        foreach ($ob as $name=>$val)
        if(is_string($val) || is_numeric($val))$xml->$name=$val;
        else if(is_numeric($name)){
#            $xml->$name=null;
            $this->iteratechildren($val,$xml[$name]);
        }
        else {
            $xml->$name=null;
            $this->iteratechildren($val,$xml->$name);
        }
    }
}

?>