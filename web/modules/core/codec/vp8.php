<?php

namespace codec;

class vp8 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'vp8', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'VP8 video', "name" => 'vp8');
    return $result;
  }

}

?>