<?php

namespace codec;

class gsm extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'gsm', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'GSM', "name" => 'gsm');
    return $result;
  }

}

?>