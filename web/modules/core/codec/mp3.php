<?php

namespace codec;

class mp3 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'mp3', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'MPEG I Layer 3', "name" => 'mp3');
    return $result;
  }

}

?>