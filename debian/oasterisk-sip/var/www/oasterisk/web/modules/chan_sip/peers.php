<?php

namespace sip;

class SIPPeer extends \core\ChannelPeer {

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function getPeers() {
    $users = array();
    if(isset($this->ami)) {
      $peers = $this->ami->SIPPeers();
      $users = self::getAsteriskPeerInfo($peers);
    } else {
      $peers = array();
      $users = array();
    }
    $ini = new \INIProcessor('/etc/asterisk/sip.conf');
    if(!isset($this->ami)) {
      foreach($ini as $entry => $peerinfo) {
        if(!$peerinfo->isTemplate()) {
          if(!isset($peerinfo->type)) continue;
          $user=new \stdClass();
          if(isset($peerinfo->remotesecret)) {
            $user->mode = 'trunk';
          } else
          if($peerinfo->type=='peer') {
            $user->mode = 'trunk';
          } else
          if($peerinfo->type=='user') {
            $user->mode = 'user';
          } else
          if($peerinfo->type == 'friend') {
            $user->mode = 'peer';
          }
          if(isset($user->mode)) {
            $user->login = $entry;
            $users[] = $user;
          }
        }
      }
    }
    foreach($users as $k => $v) {
      $users[$k]->mode = 'peer';
      $login = $v->login;
      if(isset($ini->$login)&&(isset($ini->$login->type))) {
        $peerinfo = $ini->$login;
        $users[$k]->mode = (string) $peerinfo->type;
        if(((string) $peerinfo->type == 'peer')||isset($peerinfo->remotesecret)) {
          $users[$k]->mode = 'trunk';
        } else
          if($peerinfo->type=='friend') {
            $users[$k]->mode='peer';
          }
        $users[$k]->name = '';
        $users[$k]->number = '';
        if(isset($peerinfo->callerid)) {
//$this-agi->verbose($peerinfo[';callerid'],3);
          if(preg_match('/(".*"|.*)\s*<(.*)>/',(string) $peerinfo->callerid, $cid)) {
            $users[$k]->name = $cid[1];
            $users[$k]->number = $cid[2];
          } else {
            $users[$k]->name = (string) $peerinfo->callerid;
          }
        }
        if($users[$k]->name == '') $users[$k]->name = ($ini->$login->getDescription()!='')?$ini->$login->getDescription():$users[$k]->login;
      } else {
        $peerinfo = $this->ami->send_request('SIPshowpeer', array('Peer' => $login));
        if($peerinfo->RemoteSecretExist=='Y') {
          $users[$k]->mode = 'trunk';
        } else if(($peerinfo->RemoteSecretExist == 'N')&&($peerinfo->SecretExist == 'N')&&($peerinfo->MD5SecretExist == 'N')) {
          $users[$k]->mode = 'trunk';
        }
        if(preg_match('/"(.*)" <(.*)>/',$peerinfo->callerid,$cid)) {
          $users[$k]->name = $cid[1];
          $users[$k]->number = $cid[2];
        } else {
          $users[$k]->name = $peerinfo->callerid;
        }
        if($users[$k]->number == '') $users[$k]->number = (isset($peerinfo->accountcode)&&($peerinfo->accountcode!=''))?$peerinfo->accountcode:$users[$k]->login;
        if($users[$k]->name == '') $users[$k]->name = ($peerinfo->getDescription()!='')?$peerinfo->getDescription():$users[$k]->login;
      }
    }
    if(!isset($this->agi)) {
      $effectiveusers=array();
      foreach($users as $user) {
        if(self::checkEffectivePriv('sip', $user->login, 'settings_reader')) $effectiveusers[]=$user;
      }
      $users=$effectiveusers;
    }
    return $users;
  }

}

?>
