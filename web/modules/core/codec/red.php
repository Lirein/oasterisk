<?php

namespace codec;

class red extends \module\Codec {

  public static function check($write = false) {
    $result = true;
//    $result &= self::checkModule('format', 'red', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'T.140 Realtime Text with redundancy', "name" => 'red');
    return $result;
  }

}

?>