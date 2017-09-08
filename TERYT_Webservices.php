<?php
class WsseAuthHeader extends SoapHeader {
    //private $wss_ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private $wss_ns = 'https://docs.oasis-open.org/wss/oasis-wss-wssecurity-secext-1.1.xsd';

    function __construct($user, $pass, $ns = null) {
        if ($ns) {
            $this->wss_ns = $ns;
        }

        $auth = new stdClass();
        $auth->Username = new SoapVar($user, XSD_STRING, NULL, $this->wss_ns, NULL, $this->wss_ns);
        $auth->Password = new SoapVar($pass, XSD_STRING, NULL, $this->wss_ns, NULL, $this->wss_ns);

        $username_token = new stdClass();
        $username_token->UsernameToken = new SoapVar($auth, SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'UsernameToken', $this->wss_ns);

        $security_sv = new SoapVar(
                new SoapVar($username_token, SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'UsernameToken', $this->wss_ns), SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'Security', $this->wss_ns);
        parent::__construct($this->wss_ns, 'Security', $security_sv, true);
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

		$stream_options = array(
			'http' => array(
				//'timeout' => 900.0,		//< overrides default_socket_timeout
				//'header' => 'Content-Type: text/xml; charset=utf-8',
			),
      /*
			'ssl' => array(
				'allow_self_signed'	=> true,
			),
      */
		);

		$stream_context = stream_context_create($stream_options);

    $this->soap_options = array(
      'soap_version'   => SOAP_1_2,
			'cache_wsdl'     => WSDL_CACHE_MEMORY,
			'encoding'       => 'utf8',
      'keep_alive'     => false,
      //'login'          => $user,
			//'password'       => $pass,
			'stream_context' => $stream_context,
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
    $webservice = new SoapClient("$url/wsdl/terytws1.wsdl", $this->soap_options);
    $webservice->__setLocation("$url/terytws1.svc");
    //$webservice->__setSoapHeaders(array(new WsseAuthHeader($this->user, $this->pass)));
    return $webservice;
  }

  public function is_logged_in() {
    $ws	= $this->get_webservice();
		$result = $ws->CzyZalogowany();
		$this->store_trace_data($ws);
		return $result;
  }
}
