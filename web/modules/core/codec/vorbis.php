<?php

namespace codec;

class oggVorbis extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'ogg_vorbis', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'OGG/Vorbis', "name" => 'ogg_vorbis');
    return $result;
  }

}

?>