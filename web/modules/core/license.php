<?php

namespace core;

class CoreLicense extends \module\License {

  public function info() {
    $agreement = file_get_contents(dirname(__FILE__).'/agreement.html');
    $result = new \stdClass();
    $result->name = 'Базовое программное обеспечение';
    $result->codename = 'oasterisk-core';
    $result->valid = self::checkLicense('oasterisk-core');
    $result->license = self::getLicenseInfo('oasterisk-core');
    $result->agreement = $agreement;
    return $result;
  }

}