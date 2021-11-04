<?php

namespace core;

class GeneralSettings extends \view\Menu {

  public static function getLocation() {
    return 'settings/general';
  }

  public static function getMenu() {
    return (object) array('name' => 'Общие настройки', 'prio' => 8, 'icon' => 'TuneSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>
