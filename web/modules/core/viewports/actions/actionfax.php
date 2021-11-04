<?php

namespace core;

class ActionFaxViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/fax';
  }

  public static function info() {
    return 'Факс';
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