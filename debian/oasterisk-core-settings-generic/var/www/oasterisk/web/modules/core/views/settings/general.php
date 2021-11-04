<?php

namespace core;

class GeneralSettings extends MenuModule {

  public static function getLocation() {
    return 'settings/general';
  }

  public static function getMenu() {
    return (object) array('name' => 'Общие настройки', 'prio' => 8, 'icon' => 'oi oi-cog');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>
