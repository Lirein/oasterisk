<?php

namespace core;

class pngCodec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'png', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'Portable Network Graphics (PNG)', "name" => 'png');
    return $result;
  }

}

?>