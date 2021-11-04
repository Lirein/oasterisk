<?php

namespace codec;

class wavGsm extends \module\Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'wav_gsm', true);
    return $result;
  }

  public static function info() {
    $result = (object) array("title" => 'Microsoft WAV (GSM)', "name" => 'wav_gsm');
    return $result;
  }

}

?>