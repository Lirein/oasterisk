<?php

namespace peer;

class PeerModule extends \core\Module implements \AGIInterface {

  public function agi(\stdClass $request_data) {
    if(isset($request_data->peer)) {
      $dst='';
      $users = self::getAsteriskPeers();
      foreach($users as $user) {
        if($user->mode=='peer') {
          if($request_data->peer==$user->login) {
            $this->agi->verbose('Set dest by login to '.$user->number,3);
            $dst=$user->number;
            break;
          } else
          if($request_data->peer==$user->number) {
            $this->agi->verbose('Set dest by number to '.$user->number,3);
            $dst=$user->login;
            break;
          }
        }
      }
      $this->agi->set_variable('DEST',$dst);
    }
  }

}

?>