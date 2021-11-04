<?php

namespace codec;

class lpc10 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'lpc10', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'LPC10', "name" => 'lpc10');
    return $result;
  }

}

?>