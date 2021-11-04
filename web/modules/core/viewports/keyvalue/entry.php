<?php

namespace core;

class KeyValueEntry extends \view\ViewPort {

  public static function getViewLocation() {
    return 'keyvalue/entry';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {
        this.keytext = _('Ключ');
        this.valuetext = _('Значение');
        if(isSet(parent.parent.data)&&isSet(parent.parent.data.keytext)) {
          this.keytext = parent.parent.data.keytext;
        }
        if(isSet(parent.parent.data)&&isSet(parent.parent.data.valuetext)) {
          this.valuetext = parent.parent.data.valuetext;
        }
        this.key = new widgets.input(parent, {id: 'key', pattern: /[a-z0-9_]+/}, this.keytext);
        this.value = new widgets.input(parent, {id: 'value'}, this.valuetext);
      }

    <?php
  }
}

?>