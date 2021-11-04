<?php

namespace confbridge;


class ConfbridgeDW extends \core\DashboardWidget {

  public static function info() {
    $result = (object) array("title" => 'Конференц-комнаты', "name" => 'confbridges');
    return $result;
  }

  public static function check($write = false) {
    return self::checkLicense('oasterisk-confbridge');
  }

  private static function orderroom($a, $b) {
    return strcmp($a->number, $b->number);
  }

  public function json(string $request, \stdClass $request_data) {
    $returndata = array();
    $returndata['rooms'] = array();
    $confmodule = new \confbridge\ConfbridgeModule();
    $nums=array();
    $returndata['rooms']=$confmodule->getRoomList();
    $rooms = &$returndata['rooms'];
    foreach($rooms as $roomid => $room) {
      $nums[]=$room->number;
      $room_info = $confmodule->getRoomInfo($room->number);
      if(isset($room_info->max_members)) {
        $rooms[$roomid]->avail=(string) $room_info->max_members;
      } else {
        $rooms[$roomid]->avail=0;
      }
      $rooms[$roomid]->active=false;
      if(!self::checkEffectivePriv('confbridge_room', $room->number, 'settings_reader')) {
        unset($rooms[$roomid]);
      }
    }
    $prooms=$confmodule->getRooms();
    $ini = self::getINI('/etc/asterisk/confbridge.conf');
    foreach($prooms as $roomid) {
      if(self::checkEffectivePriv('confbridge_room', $roomid, 'settings_reader')) {
        $room_info = $confmodule->getPersistentRoom($roomid);
        $room = new \stdClass();
        $room->active=$room_info->active;
        if(!in_array($roomid, $nums)) {
          $room->number=$roomid;
          $room->count=0;
          if(!empty($room_info->maxcount)) {
            $room->avail=$room_info->maxcount;
          } elseif(isset($room_info->profile)) {
            $profile = $room_info->profile;
            if(isset($ini->$profile)) {
              $res = $ini->$profile;
              unset($res->type);
            }
            if(isset($res->max_members)) {
              $room->avail=(string) $res->max_members;
            } else {
              $room->avail=0;
            }
          } else {
            $room->avail=0;
          }
          $rooms[]=$room;
        } else {
          foreach($rooms as $lroomid => $room) {
            if($room->number==$roomid) {
              $rooms[$lroomid]->active=$room_info->active;
            }
          }
        }
      }
    }
    usort($rooms, array(__CLASS__, "orderroom"));
    return $returndata;
  }

  public function implementation() {
    ?>
    if(data.rooms.length) {
      var rooms=$('<div class=\"list-group\"></div>');
      for(var i = 0; i < data.rooms.length; i++) {
        var badgeclass='success';
        var cardclass='';
        if(data.rooms[i].avail!=0) {
          if(data.rooms[i].count>(data.rooms[i].avail*0.65)) badgeclass='warning';
          if(data.rooms[i].count>(data.rooms[i].avail*0.9)) badgeclass='danger';
        }
        if(data.rooms[i].active) cardclass='list-group-item-success';
        $('<a '+
<?php if(self::checkPriv('system_control')) { ?>
         'href=\"/manage/rooms?id='+data.rooms[i].number+'\" '+
<?php } ?>
         'class=\"small list-group-item '+cardclass+' list-group-item-action justify-content-between\">'+data.rooms[i].number+'<span class=\"badge badge-'+badgeclass+' badge-pill right\">'+data.rooms[i].count+((data.rooms[i].avail!=0)?('/'+data.rooms[i].avail):'')+'</span></a>').appendTo(rooms);
      }
      $('#rooms .card-block').html(rooms);
    } else $('#rooms .card-block').html('');
    <?php
  }

  public function render() {
    printf("
<div class='card' id='rooms'>
 <div class='card-header'>
  Конференц-комнаты
 </div>
 <div class='card-block' style='height: 300px; overflow-y: auto;'>
 </div>
</div>
    ");
  }

}

?>