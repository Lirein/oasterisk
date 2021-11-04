<?php

namespace dahdi;

class DahdiPeer extends \core\ChannelPeer {

  public function info() {
    return (object) array("title" => 'E1', "name" => 'dahdi');
  }

  public static function check() {
    return self::checkLicense('oasterisk-e1');
  }

  public function getPeers() {
/*    $users = array();
    $users = $ami->send_request('DAHDIShowChannels', array());*/
    $peers = array();
/*    if(isset($users['Event'])&&($users['Event']=='DAHDIShowChannelsComplete'))
    foreach($users['events'] as $event) {
      if($event['Event']=='DAHDIShowChannels') {
        $peer = array();
        $peer['type']='DAHDI';
        $peer['login']=$event['DAHDIChannel'];
        $peer['name']=$event['Description'];
        $peer['number']=$event['DAHDIChannel'];
        $peer['ip']=null;*/
//        preg_match('/([A-Za-z ]+).*/',$event['Alarm'],$match);
/*        switch($match[1]) {
          case "Red Alarm": {
            $peer['status']='NO SIGNAL';
          } break;
          case "Yellow Alarm": {
            $peer['status']='REMOTE ERR';
          } break;
          case "Blue Alarm": {
            $peer['status']='UNFRAMED';
          } break;
          case "No Alarm": {
            $peer['status']='OK';
          } break;
          default: {
            $peer['status']='UNKNOWN';
          }
        }
        $peer['mode'] = 'peer';
        $peers[]=$peer;
      }
    }*/
    $ini = new \INIProcessor('/etc/asterisk/chan_dahdi.conf');
    foreach($ini as $k => $v) {
      $user = $v;
      if(isset($user->dahdichan)) {
        $peer = new \stdClass();
        $peer->type = 'DAHDI';
        $peer->ip = NULL;
        if(isset($user->description)) {
          $peer->name = (string) $user->description;
        } else {
          $peer->name = $k;
        }
        if(isset($user->group)) {
          $peer->login = 'g'.$user->group;
          if(isset($user->accountcode)) {
            $peer->number = (string) $user->accountcode;
          } else {
            $peer->number = (string) $user->group;
          }
          $peer->mode = 'trunk';
        } else {
          $peer->login = $k;
          if(isset($user->accountcode)) {
            $peer->number = (string) $user->accountcode;
          } else {
            $peer->number = (string) $user->dahdichan;
          }
          $peer->mode = 'peer';
        }
        if(self::checkEffectivePriv('dahdi', $peer->login, 'settings_reader')) $peers[]=$peer;
      }
    }
    return $peers;
  }

}

?>
