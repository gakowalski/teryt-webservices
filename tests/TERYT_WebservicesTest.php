<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers RIT_Webservices
 */
final class TERYT_WebservicesTest extends TestCase
{
  protected static $api;

  public static function setUpBeforeClass() {
    global $phpunit_test_config;

    $user = $phpunit_test_config['user'];
    $pass = $phpunit_test_config['pass'];
    $instance = $phpunit_test_config['instance'];

    self::$api = new TERYT_Webservices($user, $pass, $instance, true);
  }
}
