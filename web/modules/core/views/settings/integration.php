<?php

namespace core;

class IntegrationsSettings extends \view\Menu {

  public static function getLocation() {
    return 'settings/integration';
  }

  public static function getMenu() {
    return (object) array('name' => 'Интеграция', 'prio' => 9, 'icon' => 'CloudCircleSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>