<?php

namespace dahdi;

class DahdiPeerSettings extends \core\MenuModule {

  public static function getLocation() {
    return 'settings/trunks/e1';
  }

  public static function getMenu() {
    return (object) array('name' => 'E1', 'prio' => 1);
  }

  public static function check() {
    return self::checkLicense('oasterisk-e1');
  }

  public static function getZoneInfo() {
    $result = new \SecZoneInfo();
    $result->zoneClass = 'dahdi';
    $result->getObjects = function () {
                              $peers = array();
                              $ini = new \INIProcessor('/etc/asterisk/chan_dahdi.conf');
                              foreach($ini as $k => $v) {
                                $user = $v;
                                if(isset($user->dahdichan)) {
                                  $peer = new \stdClass();
                                  if(!empty($user->getDescription())) {
                                    $peer->text = $user->getDescription();
                                  } else {
                                    $peer->text = $k;
                                  }
                                  if(isset($user->group)) {
                                    $peer->id = 'g'.$user->group;
                                    $peers[]=$peer;
                                  }
                                }
                              }
                              return $peers;
                           };
    return $result;
  }

}

?>
