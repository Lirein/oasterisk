<?php

namespace core;

class DialplanIncomingSettings extends \view\Collection {

  public static function getLocation() {
    return 'settings/dialplan/incoming';
  }

  public static function getAPI() {
    return 'dialplan/incoming';
  }

  public static function getViewLocation() {
    return 'dialplan/incoming';
  }

  public static function getMenu() {
    return (object) array('name' => 'Входящие звонки', 'prio' => 2, 'icon' => 'CallReceivedSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('dialplan_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function implementation() {
    ?>
      <script>

      async function init(parent, data) {


      }

      </script>;
    <?php
  }
}

?>