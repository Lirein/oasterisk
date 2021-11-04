<?php

namespace core;

class ActionAnswerViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/answer';
  }

  public static function info() {
    return 'Ответить';
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