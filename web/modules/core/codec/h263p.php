<?php

namespace codec;

class h263p extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'h263', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'H.263+ video', "name" => 'h263p');
    return $result;
  }

}

?>