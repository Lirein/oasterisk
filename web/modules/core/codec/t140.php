<?php

namespace codec;

class t140 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
//    $result &= self::checkModule('format', 't140', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'Passthrough T.140 Realtime Text', "name" => 't140');
    return $result;
  }

}

?>