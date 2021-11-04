<?php

namespace core;

class SystemSettings extends \view\Menu {

  public static function getLocation() {
    return 'settings/system';
  }

  public static function getMenu() {
    return (object) array('name' => 'Система', 'prio' => 8, 'icon' => 'SettingsApplicationsSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>
