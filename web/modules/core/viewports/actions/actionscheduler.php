<?php

namespace core;

class ActionSchedulerPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/scheduler';
  }

  public static function info() {
    return 'Планировщик';
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