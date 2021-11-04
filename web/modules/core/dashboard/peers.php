<?php

namespace core;

class PeersDW extends DashboardWidget {

  public static function info() {
    $result = (object) array("title" => 'Абоненты', "name" => 'peers');
    return $result;
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkPriv('system_info');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $return = array();
    $return['peers'] = array();
    $peers = self::getAsteriskPeers();
    foreach($peers as $peer) {
      if($peer->mode=='peer') array_push($return['peers'], $peer);
    }
    return $return;
  }

  public function implementation() {
    printf("
    if(isSet(data.peers)) {
      if(data.peers.length) {
        var peers=$('<div class=\"list-group\"></div>');
        for(var i = 0; i < data.peers.length; i++) {
          var badgeclass='danger';
          switch(data.peers[i].status) {
            case 'OK': badgeclass='success'; break;
            case 'BUSY': badgeclass='warning'; break;
            case 'UNMONITORED': badgeclass='secondary'; break;
          }
          if(data.peers[i].number!='') data.peers[i].display=data.peers[i].number;
          if(data.peers[i].login!='') data.peers[i].display=data.peers[i].login;
          if(!isSet(data.peers[i].link)) data.peers[i].link=data.peers[i].type;
          $('<a href=\"/settings/peers/'+data.peers[i].link.toLowerCase()+'?id='+data.peers[i].login+'\" class=\"small list-group-item list-group-item-action justify-content-between\">'+data.peers[i].name+' ('+data.peers[i].display+')<span class=\"badge badge-'+badgeclass+' badge-pill right\">'+data.peers[i].type+':'+data.peers[i].status+'</span></a>').appendTo(peers);
        }
        $('#peers .card-block').html(peers);
      } else $('#peers .card-block').html('');
    } else $('#peers').hide();
    ");
  }

  public function render() {
    printf("
<div class='card' id='peers'>
 <div class='card-header'>
  Абоненты
 </div>
 <div class='card-block' style='height: 300px; overflow-y: auto;'>
 </div>
</div>
    ");
  }

}

?>