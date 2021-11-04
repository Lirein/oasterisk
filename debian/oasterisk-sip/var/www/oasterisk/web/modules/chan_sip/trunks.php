<?php

namespace sip;

class SIPTrunk extends \core\ChannelTrunk {

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function info() {
    return (object)array("title" => 'SIP', "name" => 'sip');
  }

  public function getTrunks() {
    return array();
  }

}

?>
