<?php

namespace dahdi;

class DahdiLicense extends \core\LicenseModule {

  public function info() {
    $agreement = file_get_contents(dirname(__FILE__).'/agreement.html');
    $result = new \stdClass();
    $result->name = 'Модуль каналов E1/T1';
    $result->codename = 'oasterisk-e1';
    $result->valid = self::checkLicense('oasterisk-e1');
    $result->license = self::getLicenseInfo('oasterisk-e1');
    $result->agreement = $agreement;
    return $result;
  }

}
