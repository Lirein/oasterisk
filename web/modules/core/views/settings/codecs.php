<?php

namespace core;

class CodecsSettings extends \view\Collection {

  public static function getLocation() {
    return 'settings/general/codecs';
  }

  public static function getAPI() {
    return 'general/codecs';
  }

  public static function getViewLocation() {
    return 'general/codecs';
  }

  public static function getMenu() {
    return (object) array('name' => 'Параметры кодеков', 'prio' => 7, 'icon' => 'GraphicEqSharpIcon', 'mode' => 'expert');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function implementation() {
    ?>
      <script>

      async function init(parent, data) {
        this.title = new widgets.input(parent, {id: 'title'}, "Наименование кодека");
        this.props = new widgets.section(parent, null);

        this.hasSave = true;
      }

      async function setValue(data) {
        this.props.clear();
        this.id = data.id;
        super.setValue(data);
        let type = null;
        switch(this.id) {
          case 'silk':
          case 'g729':
          case 'opus': 
            type = this.id;
            break;
          default:
            if (isSet(data.genericplc)) type = 'pcm';
            break;
        } 
        if (type !== null) await require('codecs/'+type, this.props, data);
      }

      function getValue() {
        let result = {};
        result = rootcontent.getValue();
        result.id = this.id
        return result;
      }

    </script>
    <?php
  }

}

?>