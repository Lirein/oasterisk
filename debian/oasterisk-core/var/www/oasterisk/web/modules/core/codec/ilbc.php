<?php

namespace core;

class ilbcCodec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'ilbc', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'iLBC', "name" => 'ilbc');
    return $result;
  }

}

?>