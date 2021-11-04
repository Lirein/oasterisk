<?php

namespace core;

class GatewaySettingsView extends \view\Collection {

  public static function getLocation() {
    return 'settings/gateway';
  }

  public static function getAPI() {
    return 'gateway';
  }

  public static function getViewLocation() {
    return 'gateway';
  }

  public static function getMenu() {
    return (object) array('name' => 'Шлюзы', 'prio' => 2, 'icon' => 'ShuffleSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  // public function json(string $request, \stdClass $request_data) {
  //   $result = new \stdClass();
  //   switch($request) {
  //     case "get-contexts": {
  //       $dialplan=getModuleByClass('core\Dialplan');
  //       $result = self::returnResult($dialplan->getContexts());
  //     } break;
  //   }
  //   return $result;
  // }

  public function implementation() {
    ?>
      <script>

      async function init(parent, data) {
        this.id = null;

        [this.types, this.directions] = await Promise.all([this.asyncRequest('types'),  this.asyncRequest('directions')]);

        this.name = new widgets.input(parent, {id: 'name'}, _('Название шлюза'));
        this.type = new widgets.select(parent, {id: 'type', options: this.types, search: false}, _('Тип шлюза'));
        this.direction = new widgets.select(parent, {id: 'direction', options: this.directions, search: false}, _('Направление'));
        //this.setValue(data);

        this.hasSave = true;
      }

      // $(function () {
      //    $('[data-toggle="tooltip"]').tooltip();
      //    $('[data-toggle="popover"]').popover();
 
      //    this.sendRequest('get-contexts').success(function(contexts) {
      //     context_data.splice(0);
      //     context_data.push.apply(context_data, contexts);
      //   });
      // });
      </script>;
    <?php
  }

}

?>