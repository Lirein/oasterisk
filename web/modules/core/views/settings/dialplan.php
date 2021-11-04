<?php

namespace core;

class DialplanSettings extends \view\Menu {

  public static function getLocation() {
    return 'settings/dialplan';
  }

  public static function getMenu() {
    return (object) array('name' => 'Номерной план', 'prio' => 8, 'icon' => 'SwapCallsSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>
