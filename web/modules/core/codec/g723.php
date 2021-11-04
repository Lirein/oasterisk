<?php

namespace codec;

class g723 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'g723', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'G.723.1', "name" => 'g723');
    return $result;
  }

}

?>