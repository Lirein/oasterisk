<?php

namespace core;

class jpegCodec extends Codec {

  public static function check($write = false) {
    $result = true;
//    $result &= self::checkModule('format', 'jpeg', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'Joint Photographic Experts Group (JPEG)', "name" => 'jpeg');
    return $result;
  }

}

?>