<?php

namespace core;

class DialCdrFilter extends CdrFilter {

  public static function check() {
    return self::checkLicense('oasterisk-core');
  }

  public function apps() {
    return array();
  }

  public function filter($data) {
    return false;
  }

}

?>
