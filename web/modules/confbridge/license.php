<?php

namespace confbridge;

class ConfbridgeLicense extends \module\License {

  public function info() {
    $agreement = file_get_contents(dirname(__FILE__).'/agreement.html');
    $result = new \stdClass();
    $result->name = 'Модуль конференц-связи';
    $result->codename = 'oasterisk-confbridge';
    $result->valid = self::checkLicense('oasterisk-confbridge');
    $result->license = self::getLicenseInfo('oasterisk-confbridge');
    $result->agreement = $agreement;
    return $result;
  }

}