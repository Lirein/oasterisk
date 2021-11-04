<?php

namespace codec;

class g726aal extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'g726', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'G.726 AAL', "name" => 'g726aal');
    return $result;
  }

}

?>