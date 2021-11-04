<?php

namespace codec;

class mpeg4 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'mpeg4', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'MPEG4 video', "name" => 'mpeg4');
    return $result;
  }

}

?>