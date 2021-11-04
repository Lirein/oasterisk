<?php

namespace core;

class LogsSettings extends MenuModule {

  public static function getLocation() {
    return 'settings/logs';
  }

  public static function getMenu() {
    return (object) array('name' => 'Журналы', 'prio' => 12, 'icon' => 'oi oi-book');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>
