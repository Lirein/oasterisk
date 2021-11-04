<?php

namespace core;

class ActionQueuePort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/queue';
  }

  public static function info() {
    return 'Перейти в очередь';
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