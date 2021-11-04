<?php

namespace core;

class PeersSettings extends MenuModule implements \JSONInterface {

  public static function getLocation() {
    return 'settings/peers';
  }

  public static function getMenu() {
    return (object) array('name' => 'Абоненты', 'prio' => 3, 'icon' => 'oi oi-person');
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

  public function scripts() {
    ?>
      <script>
      var context_data = [];

      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();

        sendRequest('get-contexts').success(function(contexts) {
          context_data.splice(0);
          context_data.push.apply(context_data, contexts);
        });
      });
      </script>
    <?php
  }

}

?>