<?php

namespace core;

class ActionSetPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/set';
  }

  public static function info() {
    return 'Задать переменную';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {

      }


    <?php
  }
}

?>