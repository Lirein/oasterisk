<?php

namespace core;

class oggVorbisCodec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'ogg_vorbis', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'OGG/Vorbis', "name" => 'ogg_vorbis');
    return $result;
  }

}

?>