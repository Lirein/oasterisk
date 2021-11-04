<?php

namespace codec;

class ADPCM extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'pcm', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'Dialogic ADPCM', "name" => 'adpcm');
    return $result;
  }

}

?>