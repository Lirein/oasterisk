<?php

namespace addressbook;

class AddressBookLicense extends \core\LicenseModule {

  public function info() {
    $agreement = file_get_contents(dirname(__FILE__).'/agreement.html');
    $result = new \stdClass();
    $result->name = 'Модуль адресной книги';
    $result->codename = 'oasterisk-addressbook';
    $result->valid = self::checkLicense('oasterisk-addressbook');
    $result->license = self::getLicenseInfo('oasterisk-addressbook');
    $result->agreement = $agreement;
    return $result;
  }

}
