<?php

namespace core;

class SoundsStaffSettingsView extends \view\Collection {

  public static function getLocation() {
    return 'settings/sound/staff';
  }

  public static function getAPI() {
    return 'sound/staff';
  }

  public static function getViewLocation() {
    return 'sound/staff';
  }

  public static function getMenu() {
    return (object) array('name' => 'По каждому сотруднику', 'prio' => 3, 'icon' => 'MicNoneSharpIcon');
  }

  public static function check() {
    $result = true;
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