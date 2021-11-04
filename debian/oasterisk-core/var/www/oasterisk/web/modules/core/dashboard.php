<?php

namespace core;

abstract class DashboardWidget extends ViewModule {

  abstract public static function info();

  public static function getMenu() {
    return null;
  }

}

?>