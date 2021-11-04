<?php

namespace core;

abstract class DashboardWidget extends \view\ViewPort {

  abstract public static function info();

  public static function getViewLocation() {
    return null;
  }

}

?>