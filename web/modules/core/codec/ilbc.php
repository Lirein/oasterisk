<?php

namespace codec;

class ilbc extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'ilbc', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'iLBC', "name" => 'ilbc');
    return $result;
  }

}

?>