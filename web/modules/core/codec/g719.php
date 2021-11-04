<?php

namespace codec;

class g719 extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'g719', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'ITU G.719', "name" => 'g719');
    return $result;
  }

}

?>