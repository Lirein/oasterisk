<?php

namespace dialplan;

class DialplanLicense extends \module\License {

  public function info() {
    $agreement = file_get_contents(dirname(__FILE__).'/agreement.html');
    $result = new \stdClass();
    $result->name = 'Модуль управления логикой работы';
    $result->codename = 'oasterisk-dialplan';
    $result->valid = self::checkLicense('oasterisk-dialplan');
    $result->license = self::getLicenseInfo('oasterisk-dialplan');
    $result->agreement = $agreement;
    return $result;
  }

}