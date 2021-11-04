<?php

namespace core;

use \module\IJSON;

class DriversSettings extends \view\Menu implements \module\IJSON {

  private static $drivermodules = null;

  public static function getLocation() {
    return 'settings/drivers';
  }

  public static function getMenu() {
    return (object) array('name' => 'Канальные драйверы', 'prio' => 1, 'icon' => 'SettingsInputComponentSharpIcon', 'mode' => 'expert');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "get-contexts": {
        $dialplan=new \core\Dialplan();
        $result = self::returnResult($dialplan->getContexts());
      } break;
    }
    return $result;
  }

  public function implementation() {
    ?>
      <script>
      var context_data = [];

      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();

        this.sendRequest('get-contexts').success(function(contexts) {
          context_data.splice(0);
          context_data.push.apply(context_data, contexts);
        });
      });
      </script>
    <?php
  }

}

?>