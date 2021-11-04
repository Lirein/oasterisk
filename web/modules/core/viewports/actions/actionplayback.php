<?php

namespace core;

class ActionPlaybackPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/playback';
  }

  public static function info() {
    return 'Воспроизвести';
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