<?php

namespace core;

class ActionConferenceViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/conference';
  }

  public static function info() {
    return 'Конференц-комната';
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