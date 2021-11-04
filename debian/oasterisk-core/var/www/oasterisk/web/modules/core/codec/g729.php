<?php

namespace core;

class g729Codec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'g729', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'G.729', "name" => 'g729');
    return $result;
  }

}

?>