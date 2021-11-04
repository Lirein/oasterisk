<?php

namespace core;

class DialplanDirectionSettings extends \view\Collection {

  public static function getLocation() {
    return 'settings/dialplan/direction';
  }

  public static function getAPI() {
    return 'dialplan/direction';
  }

  public static function getViewLocation() {
    return 'dialplan/direction';
  }

  public static function getMenu() {
    return (object) array('name' => 'Направление', 'prio' => 3, 'icon' => 'CallSplitSharpIcon');
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

        this.name = new widgets.input(rootcontent, {id: 'name'}, _("Название"));
        
        //this.collection = new widgets.collection(this.applicationmap, {id: 'applicationmap', value: [], expand: true, entry: 'features/custom/entry', select: 'features/custom/select'}, null);

      }

      </script>;
    <?php
  }
}

?>