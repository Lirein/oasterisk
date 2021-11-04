<?php

namespace core;

class DialplanSettings extends MenuModule {

  public static function getLocation() {
    return 'settings/dialplan';
  }

  public static function getMenu() {
    return (object) array('name' => 'Диалплан', 'prio' => 8, 'icon' => 'oi oi-script');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>
