<?php

namespace core;

class ActionRecordingPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/recording';
  }

  public static function info() {
    return 'Запись';
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