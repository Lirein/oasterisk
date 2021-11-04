<?php

namespace dahdi;

class DahdiPeerSettings extends \view\Menu {

  public static function getLocation() {
    return 'settings/trunks/e1';
  }

  public static function getMenu() {
    return (object) array('name' => 'E1', 'prio' => 1);
  }

  public static function check() {
    return self::checkLicense('oasterisk-e1');
  }

}

?>
