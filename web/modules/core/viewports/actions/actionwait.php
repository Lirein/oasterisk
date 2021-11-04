<?php

namespace core;

class ActionWaitPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/wait';
  }

  public static function info() {
    return 'Ожидать';
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