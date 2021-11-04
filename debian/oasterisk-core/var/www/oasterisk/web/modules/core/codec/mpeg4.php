<?php

namespace core;

class mpeg4Codec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'mpeg4', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'MPEG4 video', "name" => 'mpeg4');
    return $result;
  }

}

?>