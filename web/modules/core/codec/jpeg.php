<?php

namespace codec;

class jpeg extends \module\Codec {

  public static function check($write = false) {
    $result = true;
//    $result &= self::checkModule('format', 'jpeg', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'Joint Photographic Experts Group (JPEG)', "name" => 'jpeg');
    return $result;
  }

}

?>