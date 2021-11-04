<?php

namespace codec;

class siren7 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'siren7', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'ITU G.722.1', "name" => 'siren7');
    return $result;
  }

}

?>