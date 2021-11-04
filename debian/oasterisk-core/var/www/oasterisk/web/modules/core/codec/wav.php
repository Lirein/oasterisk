<?php

namespace core;

class wavCodec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'wav', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'Microsoft WAV/WAV16 Format', "name" => 'wav');
    return $result;
  }

}

?>