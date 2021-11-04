<?php

namespace core;

class ActionEchoViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/echo';
  }

  public static function info() {
    return 'Эхо-тест';
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