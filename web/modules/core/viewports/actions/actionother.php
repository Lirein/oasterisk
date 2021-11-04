<?php

namespace core;

class ActionOtherPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/other';
  }

  public static function info() {
    return 'Прочее';
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