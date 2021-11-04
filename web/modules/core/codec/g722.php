<?php

namespace codec;

class g722 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'g722', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'G.722', "name" => 'g722');
    return $result;
  }

}

?>