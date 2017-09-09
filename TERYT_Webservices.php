<?php

require 'TERYT_SoapClient.php';

class TERYT_Webservices
{
  protected $soap_client;

  public function __construct($user, $pass, $instance = 'production', $trace = false)
  {
    if ($instance == 'production')  $wsdl = 'https://uslugaterytws1.stat.gov.pl/wsdl/terytws1.wsdl';
    if ($instance == 'test')        $wsdl = 'https://uslugaterytws1test.stat.gov.pl/wsdl/terytws1.wsdl';

    $soap_options = array(
      'ws-security-login'    => $user,
      'ws-security-password' => $pass,
      'soap_version'   => SOAP_1_1,
			'cache_wsdl'     => WSDL_CACHE_MEMORY,
			'encoding'       => 'utf8',
      'keep_alive'     => false,
			'trace'					 => $trace,
			'compression'		 => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
    );

    $this->soap_client = new TERYT_SoapClient($wsdl, $soap_options);
	}

  public function is_logged_in() {
		return $this->soap_client->CzyZalogowany();
  }
}
