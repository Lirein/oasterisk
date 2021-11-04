<?php

namespace codec;

class uLaw extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'pcm', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'G.711 µ-law', "name" => 'ulaw');
    return $result;
  }

}

?>