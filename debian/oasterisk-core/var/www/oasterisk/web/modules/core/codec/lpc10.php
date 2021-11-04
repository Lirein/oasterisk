<?php

namespace core;

class lpc10Codec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'lpc10', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'LPC10', "name" => 'lpc10');
    return $result;
  }

}

?>