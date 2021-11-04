<?php

namespace core;

class mp3Codec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'mp3', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'MPEG I Layer 3', "name" => 'mp3');
    return $result;
  }

}

?>