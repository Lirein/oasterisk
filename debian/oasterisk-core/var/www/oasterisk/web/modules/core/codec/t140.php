<?php

namespace core;

class t140Codec extends Codec {

  public static function check($write = false) {
    $result = true;
//    $result &= self::checkModule('format', 't140', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'Passthrough T.140 Realtime Text', "name" => 't140');
    return $result;
  }

}

?>