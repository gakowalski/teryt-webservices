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

  /**
   * Always returns true; used to test proper connection with TERYT webservices.
   * @return boolean  Always true.
   */
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

  /**
   * Returns list of divisions: IDs and names.
   *
   * @return array An array of division names. Array keys are division IDs.
   */
  public function division_types() {
    $response_array = $this->soap_client->PobierzSlownikRodzajowJednostek()->PobierzSlownikRodzajowJednostekResult->string;
    $result_array = array();

    foreach ($response_array as $division_type) {
      $exploded = explode(',', $division_type, 2);
      $result_array[$exploded[0]] = ltrim($exploded[1]);
    }
    return $result_array;
  }

  /**
   * Returns multidimensional array containing town type names, descriptions and symbols (IDs).
   *
   * @param  string $when Date of the registry contents to be queries; in YYYY-MM-DD format.
   * @return array
   */
  public function town_types($when = null) {
    if ($when === null) $when = date('Y-m-d');

    $result = $this->soap_client->PobierzSlownikRodzajowSIMC(array(
      'DataStanu' => $when,
    ));

    return $result->PobierzSlownikRodzajowSIMCResult->RodzajMiejscowosci;
  }

  public function street_prefixes() {
    return $this->soap_client->PobierzSlownikCechULIC()->PobierzSlownikCechULICResult->string;
  }

  public function divisions($division_name, $identifiers = null, $category = null, $when = null) {
    if ($identifiers === null && $category === null && $when === null) {
      $result = $this->soap_client->WyszukajJPT(array(
        'Nazwa' => $division_name,
      ));
    } else {
      if ($when === null) $when = date('Y-m-d');
      $result = $this->soap_client->WyszukajJednostkeWRejestrze(array(
        'Nazwa' => $division_name,
        'identyfiks' => $identifiers,
        'kategoria' => $category,
        'DataStanu' => $when,
      ));
    }

    return $result;
  }

  public function provinces($region_id = null, $when = null) {
    if ($when === null) $when = date('Y-m-d');

    if ($region_id === null) {
      $result = $this->soap_client->PobierzListeWojewodztw(array(
        'DataStanu' => $when,
      ));
      $result = $result->PobierzListeWojewodztwResult->JednostkaTerytorialna;
    } else {
      $result = $this->soap_client->PobierzListeWojewodztwWRegionie(array(
        'Reg' => $region_id,
        'DataStanu' => $when,
      ));
      $result = $result->PobierzListeWojewodztwWRegionieResult->JednostkaNomenklaturyNTS;
    }

    return $result;
  }

  public function districts($province_or_subregion_id, $province = true, $when = null) {
    if ($when === null) $when = date('Y-m-d');

    if ($province === true) {
      $result = $this->soap_client->PobierzListePowiatow(array(
        'Woj' => str_pad($province_or_subregion_id, 2, '0', STR_PAD_LEFT),
        'DataStanu' => $when,
      ));
    } else {
      $result = $this->soap_client->PobierzListePowiatowWPodregionie(array(
        'Podreg' => str_pad($province_or_subregion_id, 2, '0', STR_PAD_LEFT),
        'DataStanu' => $when,
      ));
    }

    return $result;
  }

  public function communes($province_or_subregion_id, $district_id, $province = true, $when = null) {
    if ($when === null) $when = date('Y-m-d');

    if ($province === true) {
      $result = $this->soap_client->PobierzListeGmin(array(
        'Woj' => str_pad($province_or_subregion_id, 2, '0', STR_PAD_LEFT),
        'Pow' => str_pad($district_id, 2, '0', STR_PAD_LEFT),
        'DataStanu' => $when,
      ));
    } else {
      $result = $this->soap_client->PobierzListeGminPowiecie(array(
        'Pow' => str_pad($district_id, 2, '0', STR_PAD_LEFT),
        'Podreg' => str_pad($province_or_subregion_id, 2, '0', STR_PAD_LEFT),
        'DataStanu' => $when,
      ));
    }

    return $result;
  }

  public function districts_and_communes($province_id, $when = null) {
    if ($when === null) $when = date('Y-m-d');

    $result = $this->soap_client->PobierzGminyiPowDlaWoj(array(
      'Woj' => str_pad($province_or_subregion_id, 2, '0', STR_PAD_LEFT),
      'DataStanu' => $when,
    ));

    return $result;
  }

  /**
   * Return single town object.
   *
   * @param  string $town_id
   * @return object
   */
  public function town($town_id) {
    $result = $this->soap_client->WyszukajMiejscowosc(array(
      'identyfikatorMiejscowosci' => $town_id,
    ));
    return $result->WyszukajMiejscowoscResult->Miejscowosc;
  }

  /**
   * Returns list of towns with names beginning on given string and - optionally - located in provinces, districts or communes whose names start with given strnigs.
   *
   * @param  string $town_name    [description]
   * @param  array  $params_array (optional) Array of string. Possible keys: 'province', 'district', 'commune'.
   * @return [type]               [description]
   */
  public function town_search($town_name, $params_array = array()) {
    if (empty($params_array)) {
      $result = $this->soap_client->WyszukajMiejscowosc(array(
        'nazwaMiejscowosci' => $town_name,
      ));
      $result = $result->WyszukajMiejscowoscResult;
    } else {
      $true_params = array();
      $true_params['nazwaMiejscowosci'] = $town_name;
      $true_params['nazwaWoj']          = isset($params_array['province'])  ? $params_array['province'] : '';
      $true_params['nazwaPow']          = isset($params_array['district'])  ? $params_array['district'] : '';
      $true_params['nazwaGmi']          = isset($params_array['commune'])   ? $params_array['commune'] : '';

      $result = $this->soap_client->WyszukajMiejscowoscWJPT($true_params);
      $result = $result->WyszukajMiejscowoscWJPTResult;
    }

    if (property_exists($result, 'Miejscowosc')) {
      if (is_array($result->Miejscowosc)) {
        return $result->Miejscowosc;
      } else {
        return array($result->Miejscowosc);
      }
    } else {
      return array();
    }
  }

  public function towns($province, $district, $commune, $type_id = null, $when = null) {
    if ($when === null) $when = date('Y-m-d');

    if ($type_id === null) {
      $result = $this->soap_client->PobierzListeMiejscowosciWGminie(array(
        'wojewodztwo' => $province,
        'Powiat' => $district,
        'Gmina' => $commune,
        'DataStanu' => $when,
      ));
    } else {
      $result = $this->soap_client->PobierzListeMiejscowosciWRodzajuGminy(array(
        'symbolWoj' => str_pad($province, 2, '0', STR_PAD_LEFT),
        'symbolPow' => str_pad($district, 2, '0', STR_PAD_LEFT),
        'symbolGmi' => str_pad($commune, 2, '0', STR_PAD_LEFT),
        'symbolRodz' => $type_id,
        'DataStanu' => $when,
      ));
    }

    return $result;
  }

  public function towns_in_division($town_name, $town_type, $town_id, $identifiers, $when = null) {
    if ($when === null) $when = date('Y-m-d');

    $result = $this->soap_client->WyszukajMiejscowoscWRejestrze(array(
      'nazwa' => $town_name,
      'rodzajMiejscowosci' => $town_type,
      'symbol' => $town_id,
      'identyfiks' => $identifiers,
      'DataStanu,' => $when,
    ));

    return $result->WyszukajMiejscowoscWRejestrzeResult;
  }

  public function regions($when = null) {
    if ($when === null) $when = date('Y-m-d');

    $result = $this->soap_client->PobierzListeRegionow(array(
      'DataStanu' => $when,
    ));

    return $result->PobierzListeRegionowResult->JednostkaNomenklaturyNTS;
  }

  public function subregions($region_id, $when = null) {
    if ($when === null) $when = date('Y-m-d');

    $result = $this->soap_client->PobierzListePodregionow(array(
      'Woj' => str_pad($region_id, 2, '0', STR_PAD_LEFT),
      'DataStanu' => $when,
    ));

    return $result->PobierzListePodregionowResult;
  }

  public function street_wide_search($street_name, $prefix, $town_name) {
    $result = $this->soap_client->WyszukajUlice(array(
      'nazwaulicy' => $street_name,
      'cecha' => $prefix,
      'nazwamiejscowosci' => $town_name,
    ));

    return $result->WyszukajUliceResult;
  }

  public function streets($province_id, $district_id, $commune_id, $type_id, $town_id, $official = true, $when = null) {
    if ($when === null) $when = date('Y-m-d');

    $result = $this->soap_client->PobierzListeUlicDlaMiejscowosci(array(
      'Woj' => str_pad($province_id, 2, '0', STR_PAD_LEFT),
      'Pow' => str_pad($district_id, 2, '0', STR_PAD_LEFT),
      'Gmi' => str_pad($commune_id, 2, '0', STR_PAD_LEFT),
      'Rodz' => $type_id,
      'msc' => str_pad($town_id, 7, '0', STR_PAD_LEFT),
      'czyWersjaUrzedowa' => $official,
      'czyWersjaAdresowa' => !$official,
      'DataStanu' => $when,
    ));

    return $result->PobierzListeUlicDlaMiejscowosciResult;
  }

  public function streets_in_division($street_name, $prefix, $street_id, $identifiers, $when = null) {
    if ($when === null) $when = date('Y-m-d');

    $result = $this->soap_client->WyszukajMiejscowoscWRejestrze(array(
      'nazwa' => $street_name,
      'cecha' => $prefix,
      'identyfikator' => $street_id,
      'identyfiks' => $identifiers,
      'DataStanu' => $when,
    ));

    return $result->WyszukajMiejscowoscWRejestrzeResult;
  }

  /**
   * Download zipped XML or CSV file with data from full registry or diff from range of dates.
   *
   * @param  string $register  Name of the registry, e.g. TERC, SIMC, ULIC, NTS, WMRODZ
   * @param  string $type      Type or version of the registry, see official documentation, e.g. Adr, Stat, BezDzielnic, Urzedowy, Statystyczny, Adresowy
   * @param  string $date_from Date from (optional)
   * @param  string $date_to   Date to (optional)
   * @return [type]            [description]
   */
  public function download($register, $type = '', $date_from = null, $date_to = null) {
    if ($date_from === null)  $date_to = date('Y-m-d');
    if ($date_to === null)    $date_to = date('Y-m-d');

    if ($date_from == $date_to) {
      $register = strtoupper($register);
      $method = "PobierzKatalog$register$type";
      $result = $this->soap_client->$method(array(
        'DataStanu' => $date_from,
      ));
    } else {
      if ($register != 'NTS') $register = ucfirst($register);
      $method = "PobierzZmiany$register$type";
      $result = $this->soap_client->$method(array(
        'stanod' => $date_from,
        'stando' => $date_to,
      ));
    }

    return $result;
  }

  public function verify_town($town_id, $official = true) {
    $params = array(
      'symbolMsc' => str_pad($town_id, 7, '0', STR_PAD_LEFT),
    );

    if ($official) {
      $result = $this->soap_client->WeryfikujAdresDlaMiejscowosci($params);
    } else {
      $result = $this->soap_client->WeryfikujAdresDlaMiejscowosciAdresowy($params);
    }

    return $result;
  }

  public function verify_address($province_name, $district_name, $commune_name, $town_name, $type_name = null, $official = true) {
    $params = array(
      'Wojewodztwo' => $province_name,
      'Powiat' => $district_name,
      'Gmina' => $commune_name,
      'Miejscowosc' => $town_name,
    );
    if ($type_name !== null) $params['Rodzaj'] = $type_name;

    if ($official) {
      $result = $this->soap_client->WeryfikujAdresWmiejscowosci($params);
    } else {
      $result = $this->soap_client->WeryfikujAdresWmiejscowosciAdresowy($params);
    }

    return $result;
  }

  public function verify_street_by_id($town_id, $street_id, $official = true) {
    $params = array(
      'symbolMs' => str_pad($town_id, 7, '0', STR_PAD_LEFT),
      'SymUl' => str_pad($town_id, 5, '0', STR_PAD_LEFT),
    );

    if ($official) {
      $result = $this->soap_client->WeryfikujAdresDlaUlic($params);
    } else {
      $result = $this->soap_client->WeryfikujAdresDlaUlicAdresowy($params);
    }

    return $result;
  }

  public function verify_street_by_name($province_name, $district_name, $commune_name, $town_name, $type_name, $street_name, $official = true) {
    $params = array(
      'nazwaWoj' => $province_name,
      'nazwaPow' => $district_name,
      'nazwaGmi' => $commune_name,
      'nazwaMiejscowosc' => $town_name,
      'rodzajMiejsc' => $type_name,
      'nazwaUlicy' => $street_name,
    );

    if ($official) {
      $result = $this->soap_client->WeryfikujAdresDlaUlic($params);
    } else {
      $result = $this->soap_client->WeryfikujAdresDlaUlicAdresowy($params);
    }

    return $result;
  }

}
