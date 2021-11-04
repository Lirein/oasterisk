<?php

namespace core;

class ActionHangupPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/hangup';
  }

  public static function info() {
    return 'Отбить';
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