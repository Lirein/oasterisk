<?php

namespace core;

class IntegrationsSettings extends MenuModule {

  public static function getLocation() {
    return 'settings/integration';
  }

  public static function getMenu() {
    return (object) array('name' => 'Интеграция', 'prio' => 9, 'icon' => 'oi oi-cloud');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>