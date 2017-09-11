<?php

require 'TERYT_SoapClient.php';

class TERYT_Webservices
{
  public $soap_client;

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
    $result = $this->soap_client->CzyZalogowany();
    if (isset($result->CzyZalogowanyResult)) {
      return $result->CzyZalogowanyResult;
    } else {
      throw new Exception("Response object doesn't contain expected members");
    }
  }

  /**
   * Returns last modification time of the registry.
   *
   * @param  string $register Name of the registry, e.g. TERC, SIMC, ULIC, NTS
   * @return string           Last modification time, e.g. 2017-01-01T00:00:00
   */
  public function last_modified($register) {
    switch ($register) {
      case 'TERC':  $method = 'PobierzDateAktualnegoKatTerc'; break;
      case 'SIMC':  $method = 'PobierzDateAktualnegoKatSimc'; break;
      case 'ULIC':  $method = 'PobierzDateAktualnegoKatUlic'; break;
      default:      $method = "PobierzDateAktualnegoKat$register";
    }
    $result = $this->soap_client->$method();

    $property = $method . 'Result';
    if (property_exists($result, $property)) {
      return $result->$property;
    } else {
      throw new Exception("Response object doesn't contain expected members");
    }
  }


}
