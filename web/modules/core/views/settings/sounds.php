<?php

namespace core;

class SoundsSettings extends \view\Menu {

  public static function getLocation() {
    return 'settings/sound';
  }

  public static function getMenu() {
    return (object) array('name' => 'Аудиозаписи', 'prio' => 8, 'icon' => 'MusicNoteSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

}

?>
