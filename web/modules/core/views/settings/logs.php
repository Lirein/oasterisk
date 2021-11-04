<?php

namespace core;

class LogsSettings extends \view\Menu {

  public static function getLocation() {
    return 'settings/logs';
  }

  public static function getMenu() {
    return (object) array('name' => 'Журналы', 'prio' => 12, 'icon' => 'EventNoteSharpIcon', 'mode' => 'advanced');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>
