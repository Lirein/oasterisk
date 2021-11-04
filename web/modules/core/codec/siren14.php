<?php

namespace codec;

class siren14 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'siren14', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'ITU G.722.1 Annex C', "name" => 'siren14');
    return $result;
  }

}

?>