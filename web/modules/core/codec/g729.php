<?php

namespace codec;

class g729 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'g729', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'G.729', "name" => 'g729');
    return $result;
  }

}

?>