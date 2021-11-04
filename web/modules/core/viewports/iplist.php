<?php

namespace core;

class IPListInput extends \view\ViewPort {

  public static function getViewLocation() {
    return 'iplist';
  }

  public static function getAPI() {
    return '';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {

        this.value = new widgets.input(parent, {id: 'value', pattern: '999.999.999.999/99'}, _('IPv4 адрес'));
      
      }

      function setValue(data) {
        this.value.setValue(data);
      }

      function getValue() {
        return this.value.getValue();
      }

      function clear() {
        this.parent.setValue({
          value: {value: ''},
        });
      }

    <?php
  }
}

?>