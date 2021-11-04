<?php

namespace core;

class ActionMessagePort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/message';
  }

  public static function info() {
    return 'Отправить сообщение';
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