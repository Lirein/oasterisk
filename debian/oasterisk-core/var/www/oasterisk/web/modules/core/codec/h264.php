<?php

namespace core;

class h264Codec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'h264', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'H.264 video', "name" => 'h264');
    return $result;
  }

}

?>