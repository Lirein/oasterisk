<?php

namespace core;

class ActionEnableFeatureViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/enablefeature';
  }

  public static function info() {
    return 'Включить функцию';
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