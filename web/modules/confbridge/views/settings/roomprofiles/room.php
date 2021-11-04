<?php

namespace confbridge;

class RoomSettings extends \view\View {

  public static function getLocation() {
    return 'settings/roomprofiles/room';
  }

  public static function getMenu() {
    return (object) array('name' => 'Профили конф.-комнат', 'prio' => 1, 'icon' => 'oi oi-home');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  public static function getObjects() {
    $ini = self::getINI('/etc/asterisk/confbridge.conf');
    $profiles=array();
    foreach($ini as $k => $v) {
      if(isset($v->type)&&($v->type=='bridge')) {
        $profile=new \stdClass();
        $profile->id = $k;
        $profile->text = empty($v->getComment())?$k:$v->getComment();
        $profiles[]=$profile;
      }
    }
    return $profiles;
}

  public function json(string $request, \stdClass $request_data) {
    $roomdefaults = '{
      "max_members": "0",
      "record_conference": "!yes",
      "record_file": "",
      "internal_sample_rate": "auto",
      "mixing_interval": "20",
      "video_mode": "none",
      "language": "en",
      "regcontext": "",
      "sound_join": "confbridge-join",
      "sound_leave": "confbridge-leave",
      "sound_has_joined": "conf-hasjoin",
      "sound_has_left": "conf-hasleft",
      "sound_kicked": "conf-kicked",
      "sound_muted": "conf-muted",
      "sound_unmuted": "conf-unmuted",
      "sound_only_person": "conf-onlyperson",
      "sound_only_one": "conf-onlyone",
      "sound_there_are": "conf-thereare",
      "sound_other_in_party": "conf-otherinparty",
      "sound_place_into_conference": "conf-placeintoconf",
      "sound_wait_for_leader": "conf-waitforleader",
      "sound_leader_has_left": "conf-leaderhasleft",
      "sound_get_pin": "conf-getpin",
      "sound_invalid_pin": "conf-invalidpin",
      "sound_locked": "conf-locked",
      "sound_locked_now": "conf-lockednow",
      "sound_unlocked_now": "conf-unlockednow",
      "sound_error_menu": "conf-errormenu",
      "sound_begin": "confbridge-begin-leader"
    }';
    $result = new \stdClass();
    switch($request) {
      case "room-profiles": {
        $profilesdata = array();
        $profiles=self::getObjects();
        foreach($profiles as $profile) {
          if(self::checkEffectivePriv('confbridge_profile', $profile->id, 'settings_reader')) $profilesdata[]=(object) array('id' => $profile->id, 'title' => $profile->text);
        }
        $result = self::returnResult($profilesdata);
      } break;
      case "room-profile": {
        if(isset($request_data->room)&&self::checkEffectivePriv('confbridge_profile', $request_data->room, 'settings_reader')) { 
          $profile = new \stdClass();
          $ini = self::getINI('/etc/asterisk/confbridge.conf');
          $roomname = $request_data->room;
          if(isset($ini->$roomname)) {
            $k = $roomname;
            $v = $ini->$roomname;
            if(isset($v->type)&&((string) $v->type=='bridge')) {
              $profile = $v->getDefaults($roomdefaults);
              $profile->id = $k;
              $profile->title = empty($v->getComment())?$k:$v->getComment();
              $profile->readonly=!self::checkEffectivePriv('confbridge_profile', $k, 'settings_writer');
            }
          }
          $result=self::returnResult($profile);
        }
      } break;
      case "set-room-profile": {
        if(isset($request_data->orig_id)&&self::checkEffectivePriv('confbridge_profile', $request_data->orig_id, 'settings_writer')) {
          $result = self::returnError('danger', 'Неизвестная ощибка сохранения профиля');
          $ini = self::getINI('/etc/asterisk/confbridge.conf');
          $roomname = $request_data->id;
          if(isset($request_data->orig_id)&&($request_data->orig_id!='')&&($request_data->orig_id!=$request_data->id)) {
            $oldroomname = $request_data->orig_id;
            if(isset($ini->$roomname)) {
              $result = self::returnError('danger', 'Профиль с таким идентификатором уже существует');
              break;
            }
            if(isset($ini->$oldroomname))
              unset($ini->$oldroomname);
          }
          if((!isset($request_data->orig_id))||$request_data->orig_id=='') {
            if(isset($ini->$roomname)) {
              $result = self::returnError('danger', 'Профиль с таким идентификатором уже существует');
              break;
            }
          }
          $ini->$roomname->setDefaults($roomdefaults, $request_data);
          $ini->$roomname->setComment($request_data->title);
          $ini->$roomname->type = 'bridge';
          $ini->save();
          $confmodule = new \confbridge\ConfbridgeModule();
          if($confmodule&&$confmodule->configReload()) {
            $result = self::returnSuccess();
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove-room-profile": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $result = self::returnError('danger', 'Невозможно удалить профиль');
          $ini = self::getINI('/etc/asterisk/confbridge.conf');
          $roomname = $request_data->id;
          if(isset($ini->$roomname)) {
            $v = $ini->$roomname;
            if(isset($v->type)&&($v->type=='bridge')) {
              unset($ini->$roomname);
              $ini->save();
              $confmodule = new \confbridge\ConfbridgeModule();
              if($confmodule&&$confmodule->configReload()) {
                $result = self::returnSuccess('Профиль успешно удален');
              }
            }
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
    }
    return $result;
  }

  public function implementation() {
    ?>
      <script>
      var room_profile_id='';
      var card = null;
      function updateRooms() {
        this.sendRequest('room-profiles').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==room_profile_id) hasactive=true;
              items.push({id: data[i].id, title: (data[i].title!='')?data[i].title:data[i].id, active: data[i].id==room_profile_id});
            }
          };
          rightsidebar_set(items);
          if(!hasactive) {
            var profile=$('#room-profile-data');
            card.hide();
            window.history.pushState(room_profile_id, $('title').html(), '/'+urilocation);
            room_profile_id='';
            rightsidebar_init(null, sbadd, sbselect);
            sidebar_apply(null);
            if(data.length>0) loadRoom(data[0].id);
          } else {
            sidebar_apply(sbapply);
            rightsidebar_init(sbdel, sbadd, sbselect);
            loadRoom(room_profile_id);
          }
        });
      }

      function loadRoom(profile_id) {
        this.sendRequest('room-profile', {room: profile_id}).success(function(data) {
            if(data.id==profile_id) {
              room_profile_id=data.id;
              rightsidebar_activate(room_profile_id);
              rightsidebar_init(sbdel, sbadd, sbselect);
              card.setValue(data);
              card.show();
              if(data.readonly) card.disable(); else card.enable();
              sidebar_apply(data.readonly?null:sbapply);
            }
        });
      }

      function sendRoomData() {
        var data = card.getValue();
        data.orig_id = room_profile_id;
        this.sendRequest('set-room-profile', data).success(function() {
          room_profile_id=data.id;
          updateRooms();
          return true;
        });
      }

      function sendRoom() {
        var proceed = false;
        var data = card.getValue();
        data.orig_id = room_profile_id;
        if(data.id=='') {
          showalert('warning','Не задан идентификатор профиля');
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog('Идентификатор профиля изменен','Выберите действие с профилем:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              room_profile_id='';
            }
            if(proceed) {
              sendRoomData();
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendRoomData();
        }
      }

      function addRoom() {
        room_profile_id='';
        rightsidebar_activate(null);
        var data={id: 'custom_bridge',
         title: 'Произвольная комната',
         max_members: '',
         record_conference: '',
         video_mode: '',
         sound_join: '',
         sound_leave: '',
         sound_has_joined: '',
         sound_has_left: '',
         sound_kicked: '',
         sound_muted: '',
         sound_unmuted: '',
         sound_only_person: '',
         sound_only_one: '',
         sound_there_are: '',
         sound_other_in_party: '',
         sound_wait_for_leader: '',
         sound_leader_has_left: '',
         sound_place_into_conference: '',
         sound_get_pin: '',
         sound_invalid_pin: '',
         sound_locked: '',
         sound_locked_now: '',
         sound_unlocked_now: '',
         sound_error_menu: '',
         sound_begin: ''
        }
        card.setValue(data);
        card.show();
        card.enable();
        sidebar_apply(sbapply);
        rightsidebar_init(null, null, sbselect);
      }

      function removeRoom() {
        showdialog('Удаление профиля','Вы уверены что действительно хотите удалить профиль конференц-комнаты?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            var data = {};
            data.id = room_profile_id;
            this.sendRequest('remove-room-profile', data).success(function() {
              room_profile_id='';
              updateRooms();
              return false;
            });
          }
        });
      }

      function sbselect(e, item) {
        loadRoom(item);
      }

      function sbapply(e) {
        sendRoom();
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbadd(e) {
        addRoom();
      }

      function sbdel(e) {
        removeRoom();
      }

<?php
  } else {
?>

    var sbadd=null;
    var sbdel=null;

<?php
  }
?>

      $(function () {
        var items=[];
        rightsidebar_set(items);
        rightsidebar_init(null, sbadd, sbselect);
        sidebar_apply(null);
        card = new widgets.section(rootcontent,null);
        obj = new widgets.input(card, {id: 'id', pattern: /[a-zA-Z0-9_-]+/}, "Идентификатор профиля");
        obj = new widgets.input(card, {id: 'title'}, "Наименование профиля");
        obj = new widgets.input(card, {id: 'max_members', pattern: /[0-9]+/, value: 0}, "Максимальное количество участников");
        obj = new widgets.checkbox(card, {single: true, id: 'record_conference', value: false}, "Записывать конференцию", "Включает аудио запись конференции. По умолчанию имя записи формируется как confbgidge-<имя профиля>-<дата_время>.wav и рекомендуется использовать функцию CONFBRIDGE() для установки значения имени файла аудиозаписи.");
        obj = new widgets.select(card, {id: 'video_mode', value: [{id: 'none', text: 'Отключено'}, {id: 'follow_talker', text: 'Текущий говорящий участник'}, {id: 'last_marked', text: 'Последний ведущий'}, {id: 'first_marked', text: 'Первый ведущий'}]}, "Режим презентации видео");
        obj = new widgets.select(card, {id: 'sound_join', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при подключении");
        obj = new widgets.select(card, {id: 'sound_leave', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при отключении ");
        obj = new widgets.select(card, {id: 'sound_has_joined', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при подключении до имени");
        obj = new widgets.select(card, {id: 'sound_has_left', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при отключении до имени");
        obj = new widgets.select(card, {id: 'sound_kicked', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при отбое оператором");
        obj = new widgets.select(card, {id: 'sound_muted', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при отключении звука");
        obj = new widgets.select(card, {id: 'sound_unmuted', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при включении звука");
        obj = new widgets.select(card, {id: 'sound_only_person', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение о количестве участников");
        obj = new widgets.select(card, {id: 'sound_only_one', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение о наличии участников");
        obj = new widgets.select(card, {id: 'sound_there_are', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при единственном участнике");
        obj = new widgets.select(card, {id: 'sound_other_in_party', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при двух участниках");
        obj = new widgets.select(card, {id: 'sound_wait_for_leader', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при ожидании <i>ведущего</i> участника");
        obj = new widgets.select(card, {id: 'sound_leader_has_left', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при подключении <i>ведущего</i> участника");
        obj = new widgets.select(card, {id: 'sound_place_into_conference', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при отключении <i>ведущего</i> участника");
        obj = new widgets.select(card, {id: 'sound_get_pin', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при запросе пин-кода");
        obj = new widgets.select(card, {id: 'sound_invalid_pin', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при неверном вводе пин-кода");
        obj = new widgets.select(card, {id: 'sound_locked', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при попытке подключения к закрытой комнате");
        obj = new widgets.select(card, {id: 'sound_locked_now', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при блокировке комнаты");
        obj = new widgets.select(card, {id: 'sound_unlocked_now', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при разблокировке комнаты");
        obj = new widgets.select(card, {id: 'sound_error_menu', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при наборе неверного пункта меню");
        obj = new widgets.select(card, {id: 'sound_begin', value: sound_data, clean: true, search: true}, "Произвольное звуковое оповещение при подключении первого <i>ведущего</i> участника");
        card.hide();
        updateRooms();
      })
      </script>
    <?php
  }

  public function render() {
    ?>
    <?php
  }

}

?>