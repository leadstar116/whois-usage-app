#!/usr/bin/php5
<?

#       creates a WSDL file as defined below to standard output.

define("DEFNS", "bahwsdl");		// default namespace
define("SCHNS", "BW");			// schema namespace


$services=array(	/* name - endpoint pairs */
    'bedszProxy'	=>'http://zsomborpc/hkp/proxy.php'
);



$WSDLHEAD='<?xml version="1.0" encoding="UTF-8"?>
<definitions name="'.DEFNS.'"
  targetNamespace="urn:'.DEFNS.'"
  xmlns:tns="urn:'.DEFNS.'"
  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
  xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
  xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"
  xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
  xmlns="http://schemas.xmlsoap.org/wsdl/">
    <types>
        <xsd:schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="urn:'.SCHNS.'">
            <xsd:element name="reqType" type="xsd:string" />
            <xsd:element name="respType" type="xsd:string" />
        </xsd:schema>
    </types>

    <message name="requestMSG">
        <part name="reqXML" type="tns:reqType" />
    </message>
    <message name="responseMSG">
        <part name="return" type="tns:respType" />
    </message>
';
$WSDLITER='
    <portType name="%sPort">
        <operation name="%s'.SCHNS.'op">
            <input message="tns:requestMSG" />
            <output message="tns:responseMSG" />
        </operation>
    </portType>
    <binding name="%sBinding" type="tns:%sPort">
        <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http" />
        <operation name="%s'.SCHNS.'op">
            <soap:operation soapAction="urn:reqAction" />
            <input>
                <soap:body use="encoded" namespace="urn:'.SCHNS.'" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" />
            </input>
            <output>
                <soap:body use="encoded" namespace="urn:'.SCHNS.'" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" />
            </output>
        </operation>
    </binding>
    <service name="%s'.SCHNS.'service">
        <port name="%sPort" binding="tns:%sBinding">
            <soap:address location="%s" />
        </port>
    </service>
';
$WSDLFOOT='</definitions>
';

print $WSDLHEAD;
foreach ($services as $n=>$url) printf($WSDLITER,$n,$n,$n,$n,$n,$n,$n,$n,$url);
print $WSDLFOOT;

?>
