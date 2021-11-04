<?php

namespace core;

class gsmCodec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'gsm', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'GSM', "name" => 'gsm');
    return $result;
  }

}

?>