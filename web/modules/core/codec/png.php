<?php

namespace codec;

class png extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'png', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'Portable Network Graphics (PNG)', "name" => 'png');
    return $result;
  }

}

?>