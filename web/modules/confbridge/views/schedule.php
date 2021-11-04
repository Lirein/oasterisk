<?php

namespace confbridge;

class ScheduleManage extends \view\View {

  public static function getLocation() {
    return 'manage/schedule';
  }

  public static function getMenu() {
    return (object) array('name' => 'Расписание конференций', 'prio' => 5, 'icon' => 'oi oi-clock');
  }

  public static function check($write = false) {
    $result = self::checkPriv('system_info');
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  private static function mintime(&$room) {
    if(count($room)>0) {
      $result=$room[0]->time;
      for($i=1; $i<count($room); $i++) {
        if($room[$i]->time<$result) $result=$room[$i]->time;
      }
    } else {
      $result=time();
    }
    return $result;
  }

  private static function orderuser($a, $b) {
    if($a->time==$b->time) {
      return 0;
    }
    if($a->time<$b->time) {
      return -1;
    } else {
      return 1;
    }
  }

  private static function orderroom($a, $b) {
    $atime=self::mintime($a);
    $btime=self::mintime($b);
    if($atime==$btime) {
      return 0;
    }
    if($atime<$btime) {
      return -1;
    } else {
      return 1;
    }
  }

  public function json(string $request, \stdClass $request_data) {
    $result = array();
    switch($request) {
      case 'schedule': {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $schedules=new \stdClass();
          $path='/var/spool/asterisk/outgoing';
          if($dir = opendir($path)) {
            while(($file = readdir($dir)) !== false) {
              if((filetype($path.'/'.$file)=='file')&&(strpos($file, 'room_')===0)) {
                $time=filemtime($path.'/'.$file);
                if(!preg_match('/^room_(.*)_group_(.*)_user_([0-9]+).call$/m',$file,$match))
                  preg_match('/^room_(.*)_user_()([0-9]+).call$/m',$file,$match);
                $roomid = $match[1];
                $schedules->$roomid[]=(object) array('user' => $match[3], 'group' => $match[2], 'time' =>  $time);
              }
            }
            closedir($dir);
          }
          $currentschedule=$confmodule->getSchedulePersistentRooms(new \DateTime('@'.$request_data->date));
          $mintime=time();
          if($request_data->date>$mintime) $mintime=$request_data->date;
          foreach($currentschedule as $roomid => $room) {
            foreach($room as $entryid => $entry) {
              if(isset($schedules->$roomid)) {
                foreach($schedules->$roomid as $schedule) {
                  if(($entry->group==$schedule->group)&&($entry->user==$schedule->user)) {
                    if(($schedule->time>=$mintime)&&($schedule->time<($request_data->date+86400))) {
                      $currentschedule->$roomid[$entryid]->real = true;
                      $currentschedule->$roomid[$entryid]->time = $schedule->time;
                    } elseif($currentschedule->$roomid[$entryid]->time<$schedule->time) {
                      unset($currentschedule->$roomid[$entryid]);
                    }
                    break;
                  }
                }
              }
              if(!isset($currentschedule->$roomid[$entryid])) continue;
              if($entry->group=='') {
                $currentschedule->$roomid[$entryid]->callerid=self::getDB('conf_'.$roomid, 'user_'.$entry->user.'/callerid');
                $currentschedule->$roomid[$entryid]->chan=self::getDB('conf_'.$roomid, 'user_'.$entry->user.'/chan');
                $currentschedule->$roomid[$entryid]->extnum=self::getDB('conf_'.$roomid, 'user_'.$entry->user.'/extnum');
              } else {
                $currentschedule->$roomid[$entryid]->callerid=self::getDB('group_'.$entry->group, 'user_'.$entry->user.'/callerid');
                $currentschedule->$roomid[$entryid]->chan=self::getDB('group_'.$entry->group, 'user_'.$entry->user.'/chan');
                $currentschedule->$roomid[$entryid]->extnum=self::getDB('group_'.$entry->group, 'user_'.$entry->user.'/extnum');
              }
              if(!(($currentschedule->$roomid[$entryid]->time>=$mintime)&&($currentschedule->$roomid[$entryid]->time<($request_data->date+86400)))) unset($currentschedule->$roomid[$entryid]);
            }
            if(count($currentschedule->$roomid)==0) unset($currentschedule->$roomid);
              else usort($currentschedule->$roomid, array(__CLASS__, "orderuser"));
          }
          $currentschedule = (array) $currentschedule;
          uasort($currentschedule, array(__CLASS__, "orderroom"));
          $result = self::returnResult($currentschedule);
        }
      } break;
      case 'reschedule': {
        $confmodule = new \confbridge\ConfbridgeModule();
        $result = self::returnError('danger', 'Не удается перепланировать конференц-комнатуы');
        if($confmodule) {
          if(isset($request_data->userid)) {
            $confmodule->rescheduleRoomUser($request_data->roomid, $request_data->userid, $request_data->groupid, $request_data->date);
            $result = self::returnSuccess('Участник конференц-комнаты перепланирован на следующую дату');
          } elseif(isset($request_data->roomid)) {
            $confmodule->cancelPersistentRoom($request_data->roomid);
            $confmodule->schedulePersistentRoom($request_data->roomid, new \DateTime('@'.$request_data->date));
            $result = self::returnSuccess('Комната перепланирована на следующую дату');
          } else {
            $confmodule->schedulePersistentRooms();
            $result = self::returnSuccess('Комнаты перепланированы на следующую дату');
          }
        }
      } break;
      case 'reset': {
        $confmodule = new \confbridge\ConfbridgeModule();
        $result = self::returnError('danger', 'Не удается сбросить расписание конференц-комнат');
        if($confmodule) {
          $rooms=\confbridge\ConfbridgeModule::getRooms();
          foreach($rooms as $room) {
            $confmodule->cancelPersistentRoom($room);
          }
          $confmodule->schedulePersistentRooms();
          $result = self::returnSuccess('Расписание сброшено');
        }
      } break;
    }
    return $result;
  }

  public function implementation() {
    ?>
    <script>
      var schedule=null;
      var datetime=null;
      var dialog=null;

      function rescheduleRooms(aobject) {
        this.sendRequest('reschedule').success(function() {
          loadSchedule();
          return true;
        });
        dialog.hide();
      }

      function rescheduleRoom(aobject) {
        this.sendRequest('reschedule', {roomid: aobject.getID(), date: datetime.getMoment().unix()+86400}).success(function() {
          loadSchedule();
          return true;
        });
      }

      function resetRooms(aobject) {
        this.sendRequest('reset').success(function() {
          loadSchedule();
          return true;
        });
        dialog.hide();
      }

      function rescheduleUser(aobject, entry) {
        var user=entry.id.match(/group_(.*)_user_(.*)/i);
        var groupid=user[1];
        var userid=user[2];
        this.sendRequest('reschedule', {date: datetime.getMoment().unix()+86400, roomid: aobject.labeltext, groupid: groupid, userid: userid}).success(function() {
          loadSchedule();
          return true;
        });
        return false;
      }

      function loadSchedule() {
        this.sendRequest('schedule', {date: datetime.getMoment().unix()}).success(function(data) {
          schedule.node.textContent='';
          if(data.length==0) showalert('warning', 'Нет расписания на указанную дату');
          for(roomid in data) {
            var list=[];
            for(i in data[roomid]) {
              list.push({id: 'group_'+data[roomid][i].group+'_user_'+data[roomid][i].chan, text: data[roomid][i].callerid, subtext: data[roomid][i].chan, badge: (new moment(data[roomid][i].time*1000)).format('HH:mm'), opacity: data[roomid][i].real?100:50});
            }
            var obj = new widgets.list(schedule, {value: list, id: 'room_'+roomid, remove: true, columns: 3}, roomid);
            obj.onRemove = rescheduleUser;
            var schedroom = new widgets.button(obj.label, {id: roomid, icon: 'trash', class: 'danger'}, "");
            schedroom.node.classList.add('ml-4');
            schedroom.onClick = rescheduleRoom;
          }
        });
      }

      $(function () {
        var items=[];
        dialog = new widgets.dialog(rootcontent, {id: 'resetdialog'}, "Обновление/сброс расписания планировщика");
        dialog.dialog.classList.remove('modal-lg');
        dialog.dialog.classList.add('modal-md');
        var obj = new widgets.label(dialog, {}, "Выберите действие с расписанием.", "<b>Сброс расписания</b> - удаляет все запланированные вызовы и создает их заново, проводимые вызовы, собранные через планировщик будут отменены.<br><b>Обновление расписания</b> - создает отстутвующие записи в расписании, если они не были созданы автоматической проверкой расписания (в начале каждого часа).");
        obj = new widgets.button(dialog.footer, {class: 'danger'}, "Сбросить");
        obj.onClick = resetRooms;
        obj = new widgets.button(dialog.footer, {class: 'warning'}, "Обновить");
        obj.onClick = rescheduleRooms;
        dialog.closebtn.setParent(dialog.footer);
        dialog.savebtn.hide();

        card = new widgets.section(rootcontent,null);
        var sect = new widgets.section(card,null);
        sect.node.classList.add('row');
        var column = new widgets.columns(sect,2);
        column.node.classList.remove('col-lg-6');
        column.node.classList.add('col-sm-7');
        column.node.classList.add('col-lg-8');
        column.node.classList.add('col-xl-9');
        datetime = new widgets.datetime(column, {id: 'date', format: 'DD.MM.YYYY', value: new moment().startOf("day"), from: new moment().startOf("day")}, "Расписание планировщика на", "Выберите дату на котрую нужно отобразить события планировщика");
        datetime.onChange = loadSchedule;
        var column = new widgets.columns(sect,2);
        column.node.classList.remove('col-lg-6');
        column.node.classList.add('col-sm-5');
        column.node.classList.add('col-lg-4');
        column.node.classList.add('col-xl-3');
        column.node.classList.add('text-center');
        obj = new widgets.button(column, null, "Обновить планировщик");
        obj.onClick = function() {dialog.show()};
        schedule = new widgets.section(card, null);

        loadSchedule();
      });
    </script>
    <?php
  }

  public function render() {
  }

}

?>