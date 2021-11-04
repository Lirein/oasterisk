<?php

namespace core;

class DialplanActions extends \view\ViewPort {

  public static function getViewLocation() {
    return 'ivr/actions';
  }

  public static function getAPI() {
    return '';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {
        this.collection = new widgets.collection(parent, {entry: 'ivr/action', data: data});
      }

      function setValue(data) {
        if((typeof data == 'object')&&(data instanceof Array)) {
          this.collection.setValue(data);
        }
      }

      function getValue() {
        return this.collection.getValue();
      }

      function clear() {
        this.collection.setValue({
          value: [],
        });
      }

    <?php
  }
}

?>