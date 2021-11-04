<?php

namespace core;

class slinCodec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'sln', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => '16 bit Signed Linear PCM', "name" => 'slin');
    return $result;
  }

}

?>