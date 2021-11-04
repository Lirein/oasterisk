<?php

namespace core;

class ActionGotoViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/goto';
  }

  public static function info() {
    return 'Шаг сценария';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {

        this.direction = new widgets.select(parent, {id: 'direction', expand: false, search: false, options: [{id: 'step', title: _('Шаг сценария')}, {id: 'ivr', title: _('Сценарий')}], clean: true}, _('Куда'));
      }


    <?php
  }
}

?>