<?php

namespace confbridge;

class RoomProfilesSettings extends \view\Menu implements \module\IJSON {

  public static function getLocation() {
    return 'settings/roomprofiles';
  }

  public static function getMenu() {
    return (object) array('name' => 'Профили конф.-комнат', 'prio' => 6, 'icon' => 'oi oi-layers');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "get-sounds": {
        $sounds=new \core\Sounds();
        $sounddata=array();
        foreach($sounds->get() as $v => $dummy) {
          $sounddata[] = (object) array('id' => $v, 'text' => $v);
        }
        $result = self::returnResult($sounddata);
      } break;
      case "get-contexts": {
        $dialplan=new \core\Dialplan();
        $contexts=array();
        foreach($dialplan->getContexts() as $v) {
          $contexts[] = (object) array('id' => $v->id, 'text' => $v->title);
        }
        $result = self::returnResult($contexts);
      } break;
    }
    return $result;
  }

  public function implementation() {
    ?>
    <script>
      var sound_data = [];
      var context_data = [];

      $(function () {
        this.sendRequest('get-sounds').success(function(sounds) {
          sound_data.splice(0);
          sound_data.push.apply(sound_data,{id: '', text: 'Не указано'});
          sound_data.push.apply(sound_data,sounds);
          return false;
        });
        this.sendRequest('get-contexts').success(function(contexts) {
          context_data.splice(0);
          context_data.push.apply(context_data,contexts);
          return false;
        });
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
      });
     </script>
    <?php
  }

}

?>
