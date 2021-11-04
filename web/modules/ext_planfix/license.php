<?php

namespace planfix;

class PlanfixLicense extends \module\License {

  public function info() {
    $agreement = file_get_contents(dirname(__FILE__).'/agreement.html');
    $result = new \stdClass();
    $result->name = 'Модуль интеграции с ПланФикс™';
    $result->codename = 'oasterisk-planfix';
    $result->valid = self::checkLicense('oasterisk-planfix');
    $result->license = self::getLicenseInfo('oasterisk-planfix');
    $result->agreement = $agreement;
    return $result;
  }

}