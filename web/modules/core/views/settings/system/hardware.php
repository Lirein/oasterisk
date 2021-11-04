<?php

namespace core;

class HardwareSettings extends \view\Menu {

  public static function getLocation() {
    return 'settings/system/hardware';
  }

  public static function getMenu() {
    return (object) array('name' => 'Оборудование', 'prio' => 8, 'icon' => 'DeviceHubSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>
