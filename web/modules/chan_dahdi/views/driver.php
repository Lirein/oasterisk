<?php

namespace dahdi;

class DahdiDriverSettings extends \view\View {

  public static function getLocation() {
    return 'settings/drivers/dahdi';
  }

  public static function getAPI() {
    return 'drivers/dahdi';
  }

  public static function getViewLocation() {
    return 'drivers/dahdi';
  }

  public static function getMenu() {
    return (object) array('name' => 'DAHDI', 'prio' => 1, 'mode' => 'expert', 'icon' => 'DialerPRISharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'dahdi reload'));
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {
      }

      </script>
    <?php
  }

}

?>