<?php

namespace codec;

class h261 extends \module\Codec {

  public function register() {
    $result = (object) array("path" => null, "menu" => null, "namespace" => 'core/codec');
    return $result;
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'h261', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'H.261 video', "name" => 'h261');
    return $result;
  }

}

?>