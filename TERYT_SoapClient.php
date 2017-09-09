<?php

class TERYT_SoapClient extends SoapClient {
	protected $user;
  protected $pass;

  public function __construct ($wsdl, $options) {
    $this->user = $options['ws-security-login'];
    $this->pass = $options['ws-security-password'];
    parent::__construct($wsdl, $options);
  }

  public function __doRequest ($request , $location , $action , $version, $one_way = 0)
  {
    $nonce_encoded = base64_encode( bin2hex( openssl_random_pseudo_bytes( 16 ) ) );
    $header = '
    <SOAP-ENV:Header xmlns:wsa="http://www.w3.org/2005/08/addressing">
  		<wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
  			<wsse:UsernameToken wsu:Id="UsernameToken-'. date('c') .'">
  				<wsse:Username>'. $this->user .'</wsse:Username>
  				<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'. $this->pass .'</wsse:Password>
  				<wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'. $nonce_encoded .'</wsse:Nonce>
  				<wsu:Created>'. date('c') .'</wsu:Created>
  			</wsse:UsernameToken>
  		</wsse:Security>
  		<wsa:Action>'. $action .'</wsa:Action>
  	</SOAP-ENV:Header>
    ';

    $xml = explode('<SOAP-ENV:Body>', $request);
    $request = str_replace('SOAP-ENV', 'soapenv', $xml[0] . $header . '<SOAP-ENV:Body>' . $xml[1]);

    return parent::__doRequest($request , $location, $action, $version, $one_way);
  }
}
