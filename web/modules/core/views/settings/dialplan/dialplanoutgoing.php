<?php

namespace core;

class DialplanOutgoingSettings extends \view\Collection {

  public static function getLocation() {
    return 'settings/dialplan/outgoing';
  }

  public static function getAPI() {
    return 'dialplan/outgoing';
  }

  public static function getViewLocation() {
    return 'dialplan/outgoing';
  }

  public static function getMenu() {
    return (object) array('name' => 'Исходящие звонки', 'prio' => 1, 'icon' => 'CallMadeIcon');
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