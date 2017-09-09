<?php

class TERYT_SoapClient extends SoapClient {
  public function __doRequest ($request , $location , $action , $version, $one_way = 0)
  {
    $created = date('c');
    $nonce = openssl_random_pseudo_bytes( 16 );
    $nonce_encoded = base64_encode( bin2hex( $nonce ) );
    $header = '
    <SOAP-ENV:Header xmlns:wsa="http://www.w3.org/2005/08/addressing">
  		<wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
  			<wsse:UsernameToken wsu:Id="UsernameToken-'. $created .'">
  				<wsse:Username>TestPubliczny</wsse:Username>
  				<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">1234abcd</wsse:Password>
  				<wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'. $nonce_encoded .'</wsse:Nonce>
  				<wsu:Created>'. $created .'</wsu:Created>
  			</wsse:UsernameToken>
  		</wsse:Security>
  		<wsa:Action>'. $action .'</wsa:Action>
  	</SOAP-ENV:Header>
    ';

    $xml = explode('<SOAP-ENV:Body>', $request);
    $request = str_replace('SOAP-ENV', 'soapenv', $xml[0] . $header . '<SOAP-ENV:Body>' . $xml[1]);

    $request_old = '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">
  	<soapenv:Header xmlns:wsa="http://www.w3.org/2005/08/addressing">
  		<wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
  			<wsse:UsernameToken wsu:Id="UsernameToken-'. $created .'">
  				<wsse:Username>TestPubliczny</wsse:Username>
  				<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">1234abcd</wsse:Password>
  				<wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'. $nonce_encoded .'</wsse:Nonce>
  				<wsu:Created>'. $created .'</wsu:Created>
  			</wsse:UsernameToken>
  		</wsse:Security>
  		<wsa:Action>'. $action .'</wsa:Action>
  	</soapenv:Header>
  	<soapenv:Body>
  		<tem:CzyZalogowany/>
  	</soapenv:Body>
    </soapenv:Envelope>
    ';
    print_r($request);
    print_r($request_old);
    return parent::__doRequest($request , $location, $action, $version, $one_way);
  }
}

class TERYT_Webservices
{
	/**
	 * User login string
	 * @var string
	 */
	protected $user;

  /**
   * User password string
   * @var string
   */
  protected $pass;

	/**
	 * Instance name, see {@see $instances}
	 * @var string
	 */
  protected $instance;

	/**
	 * Array of options for SoapClient
	 * @var array
	 */
  protected $soap_options;

	/**
	 * Last XML response (only if trace parameter of {@see __construct()} is set; otherwise NULL)
	 * @var string
	 */
	public $xml_response;

	/**
	 * Last XML request (only if trace parameter of {@see __construct()} is set; otherwise NULL)
	 * @var string
	 */
	public $xml_request;

	/**
	 * Array of available instances
	 * @var array
	 */
  public $instances = array(
    'production'  => 'https://uslugaterytws1.stat.gov.pl',      //< without trailing slash
    'test'        => 'https://uslugaterytws1test.stat.gov.pl',  //< without trailing slash
  );

  public function __construct($user, $pass, $instance = 'production', $trace = false)
  {
		$this->user = $user;
    $this->pass = $pass;
    $this->instance = $instance;
		$this->xml_response = null;
		$this->xml_request = null;

    $this->soap_options = array(
      'soap_version'   => SOAP_1_1,
			'cache_wsdl'     => WSDL_CACHE_MEMORY,
			'encoding'       => 'utf8',
      'keep_alive'     => false,
			'trace'					 => $trace,
			'compression'		 => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
    );
	}

	private function store_trace_data($soap_client_object) {
		$this->xml_response = $soap_client_object->__getLastResponse();
		$this->xml_request = $soap_client_object->__getLastRequest();
	}

  public function get_webservice()
  {
    $url = $this->instances[$this->instance];
    $webservice = new TERYT_SoapClient("$url/wsdl/terytws1.wsdl", $this->soap_options);
    //$webservice->__setLocation("$url/terytws1.svc");
    return $webservice;
  }

  public function is_logged_in() {
    $ws	= $this->get_webservice();
		$result = $ws->CzyZalogowany();
		$this->store_trace_data($ws);
		return $result;
  }
}
