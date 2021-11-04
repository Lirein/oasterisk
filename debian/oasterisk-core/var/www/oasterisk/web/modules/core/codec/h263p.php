<?php

namespace core;

class h263pCodec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'h263', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'H.263+ video', "name" => 'h263p');
    return $result;
  }

}

?>