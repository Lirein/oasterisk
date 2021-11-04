<?php

namespace core;

class ActionLogPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/log';
  }

  public static function info() {
    return 'Записать в журнал';
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