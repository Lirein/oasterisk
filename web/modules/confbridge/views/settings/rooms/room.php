<?php

namespace confbridge;

class PersistentRoomSettings extends \view\View {

  public static function getLocation() {
    return 'settings/rooms/room';
  }

  public static function getMenu() {
    return (object) array('name' => 'Постоянные комнаты', 'prio' => 1, 'icon' => 'oi oi-project');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  private static function orderroom($a, $b) {
    return strcmp($a,$b);
  }

  public static function getObjects() {
    $rooms = array();
    $confmodule = new \confbridge\ConfbridgeModule();
    if($confmodule) {
      $rooms=$confmodule->getRooms();
    }
    usort($rooms, array(__CLASS__, "orderroom"));
    foreach($rooms as $roomkey => $room) {
      $rooms[$roomkey]=(object) array('id' => $room, 'text' => $room);
    }
    return $rooms;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "persistent-rooms": {
        $return = array();
        $rooms=self::getObjects();
        foreach($rooms as $room) {
          if(self::checkEffectivePriv('confbridge_room', $room->id, 'settings_reader')) $return[]=(object) array('id' => $room->id, 'title' => $room->text);
        }
        $result = self::returnResult($return);
      } break;
      case "persistent-room": {
        $abmodule = null;
        $confmodule = new \confbridge\ConfbridgeModule();
        if(($confmodule)&&isset($request_data->id)&&self::checkEffectivePriv('confbridge_room', $request_data->id, 'settings_reader')) { 
          $return = new \stdClass();
          $ini = self::getINI('/etc/asterisk/confbridge.conf');
          $return=$confmodule->getPersistentRoom($request_data->id);
          foreach($return->users as $userkey => $user) {
            $return->users[$userkey]->admin = false;
            $return->users[$userkey]->marked = false;
            $return->users[$userkey]->waitmarked = false;
            $return->users[$userkey]->endmarked = false;
            $return->users[$userkey]->muted = false;
            $userprofile = $user->profile;
            if(isset($ini->$userprofile)) {
              $defaultsettings = '{
                "admin": "!no",
                "marked": "!no",
                "wait_marked": "!no",
                "end_marked": "!no",
                "startmuted": "!no"
              }';
              $profile = $ini->$userprofile->getDefaults($defaultsettings);
              $return->users[$userkey]->admin = $profile->admin;
              $return->users[$userkey]->marked = $profile->marked;
              $return->users[$userkey]->waitmarked = $profile->wait_marked;
              $return->users[$userkey]->endmarked = $profile->end_marked;
              $return->users[$userkey]->muted = $profile->startmuted;
            }
            if(strpos($return->users[$userkey]->chan,'Local/')===0) {
              $chaninfo = explode('@', substr($return->users[$userkey]->chan, 6));
              if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
              if($abmodule) {
                $contact = $abmodule->getContact(substr($chaninfo[1], 3), $chaninfo[0]);
                if($contact) $return->users[$userkey]->number = implode(', ', $contact->numbers);
              }
            }
          }
          $return->id=$request_data->id;
          $groups=array();
          foreach($return->groups as $group) {
            $groups[]=$group->id;
          }
          $return->groups = $groups;
          $return->readonly=!self::checkEffectivePriv('confbridge_room', $request_data->id, 'settings_writer');
          $result = self::returnResult($return);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove-persistent-room": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if(($confmodule)&&isset($request_data->id)&&self::checkPriv('settings_writer')) { 
          $confmodule->cancelPersistentRoom($request_data->id);
          if($confmodule->removePersistentRoom($request_data->id)) {
            $result = self::returnSuccess('Конференц-комната успешно удалена');
          } else {
            $result = self::returnError('danger', 'Не удалось удалить конференц-комнату');
            $confmodule->schedulePersistentRooms();
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "save-persistent-room": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if(($confmodule)&&isset($request_data->id)&&self::checkEffectivePriv('confbridge_room', $request_data->id, 'settings_writer')) { 
          if(isset($request_data->id)&&($request_data->id!='')&&($request_data->id!=$request_data->new_id)) {
            $confmodule->cancelPersistentRoom($request_data->id);
          }
          if(!is_array($request_data->users)) $request_data->users=array(); //fixup empty room
          if(!is_array($request_data->groups)) $request_data->groups=array(); //and no groups in room
          if($confmodule->savePersistentRoom($request_data->id, $request_data->new_id, $request_data->alias, $request_data->pin, $request_data->maxcount, $request_data->regex, $request_data->profile, $request_data->users, $request_data->from, $request_data->to, $request_data->offset, $request_data->days, $request_data->enabled, $request_data->groups, $request_data->activeoncall, $request_data->disallowcallout, $request_data->useinstatistics, $request_data->activatebefore)) {
            $result = self::returnSuccess();
            if($request_data->enabled=='true') {
              if($request_data->id!=$request_data->new_id) {
                system('/bin/sh -c \'find /var/spool/asterisk/outgoing -name "room_'.$request_data->id.'_*" -exec rm {} \;\'');
              }
              $days=explode('&', $request_data->days);
              $ftime=explode(':', $request_data->from);
              $ttime=explode(':', $request_data->to);
              $now = new \DateTime();
              $fschedule = new \DateTime();
              $fschedule->setTimezone(new \DateTimeZone('GMT'));
              $fschedule->setTime($ftime[0],$ftime[1]);
              $tschedule = new \DateTime();
              $tschedule->setTimezone(new \DateTimeZone('GMT'));
              $tschedule->setTime($ttime[0],$ttime[1]);
              if($fschedule->getTimestamp()>$tschedule->getTimestamp()) $tschedule->add(new \DateInterval('P1D'));
              $flschedule = clone $fschedule;
              if(isset($request_data->offset)&&($request_data->offset!=0)) {
                if($request_data->offset>0) {
                  $flschedule->add(new \DateInterval('PT'.$request_data->offset.'M'));
                } else {
                  $flschedule->sub(new \DateInterval('PT'.(-1*$request_data->offset).'M'));
                }
              }
              if($flschedule->format('d')>$fschedule->format('d')) {
                $flschedule->sub(new \DateInterval('P1D'));
                $fschedule->sub(new \DateInterval('P1D'));
                $tschedule->sub(new \DateInterval('P1D'));
              } elseif($flschedule->format('d')<$fschedule->format('d')) {
                $flschedule->add(new \DateInterval('P1D'));
                $fschedule->add(new \DateInterval('P1D'));
                $tschedule->add(new \DateInterval('P1D'));
              }
              if($now->getTimestamp()>$fschedule->getTimestamp()||(($request_data->days!='')&&!in_array(strtolower($flschedule->format('D')),$days))) {
                $fschedule->add(new \DateInterval('P1D'));
                $flschedule->add(new \DateInterval('P1D'));
                if($request_data->days!='') {
                  while(!in_array(strtolower($flschedule->format('D')),$days)) {
                    $fschedule->add(new \DateInterval('P1D'));
                    $flschedule->add(new \DateInterval('P1D'));
                  }
                }
              }
              $confmodule->cancelPersistentRoom($request_data->new_id);
              $confmodule->startPersistentRoom($request_data->new_id, $fschedule->getTimestamp());
            } else {
              system('/bin/sh -c \'find /var/spool/asterisk/outgoing -name "room_'.$request_data->new_id.'_*" -exec rm {} \;\'');
            }
          } else {
            $result = self::returnError('danger', 'Не удалось сохранить конференц-комнату');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "schedule-persistent-rooms": {
        if(self::checkPriv('settings_writer')) {
          $confmodule = new \confbridge\ConfbridgeModule();
          if($confmodule) {
            if($confmodule->schedulePersistentRooms()) {
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('danger', 'Не удалось перепланировать конференц-комнаты');
            }
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "persistent-groups": {
        $module = new \confbridge\PersistentGroupSettings();
        $result = $module->json($request, $request_data);
      } break;
    }
    return $result;
  }

  public function implementation() {
    global $location;
    ?>
      <script>
      var card=null;
      var add_btn=null;
      var roomprofiles=[];
      var users=[];
      var user=null;
      var group=null;
      var persistent_room_id='';

      function updatePersistentRooms() {
        this.sendRequest('persistent-rooms').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==persistent_room_id) hasactive=true;
              data[i].active = data[i].id==persistent_room_id;
              items.push(data[i]);
            }
          }
          rightsidebar_set(items);
          if(!hasactive) {
            card.hide();
            window.history.pushState(persistent_room_id, $('title').html(), '/'+urilocation);
            rightsidebar_init(null, sbadd, sbselect);
            sidebar_apply(null);
            persistent_room_id='';
            if(data.length>0) loadPersistentRoom(data[0].id);
          } else {
            rightsidebar_init(sbdel, sbadd, sbselect);
            sidebar_apply(sbapply);
          }
          return false;
        });
      }

      if([].indexOf) {
        var find = function(array, value) {
          return array.indexOf(value);
        }
      } else {
        var find = function(array, value) {
          for (var i = 0; i < array.length; i++) {
            if (array[i] === value) return i;
          }
          return -1;
        }
      }

      function updatePersistentRoom() {
        if(persistent_room_id!=0) this.sendRequest('persistent-room', {id: persistent_room_id}).success(function(data) {
          if(isSet(data.id)) {
            rightsidebar_activate(data.id);
            rightsidebar_init(data.readonly?null:sbdel, (room_profiles.length == 0)?null:sbadd, sbselect);

            data.period = {from: data.from, for: data.to};
            data.days = data.days.split('&');
            if(data.days[0]=='') data.days=[];
            user=data.users;
            delete data.users;
            card.setValue(data);
            if((menu_profiles.length == 0)||(user_profiles.length == 0)) add_btn.hide();
            updateUsers();
            if(data.readonly) card.disable(); else card.enable();
            sidebar_apply(data.readonly?null:sbapply);
            card.show();
          }
        });
      }

      function updateUsers() {
        var users=[];
        if(user.length) {
          for(var i = 0; i < user.length; i++) {
            status='';
            if(user[i].marked&&user[i].admin) status='danger';
            else if(user[i].admin) status='warning';
            else if(user[i].marked) status='success';
            users.push({id: 'user_'+i, text: user[i].callerid, subtext: (isSet(user[i].number))?(user[i].chan+' ('+user[i].number+')'):user[i].chan, class: status, mode: user[i].auto});
          }
        }
        card.setValue({users: {value: users, clean: true}});
      }

      function loadPersistentRoom(aid) {
        persistent_room_id=aid;
        updatePersistentRoom();
      }

      function removeRoomGroup(sender, item) {
        if(!isSet(item.removed)) {
          showdialog('Удаление группы','Вы уверены что действительно хотите удалить группу участников из конференц-комнаты?',"error",['Yes','No'],function(e) {
            if(e=='Yes') {
              item.removed=true;
              if(sender.listRemove(sender.list, item)) sender.list.delete(item.id);
            }
          });
          return false;
        } else {
          return true;
        }
      }

      function newPersistentRoom() {
        persistent_room_id='';
        rightsidebar_activate(null);
        var data = {id: 'Новая комната', alias: '', profile: 'default_bridge', groups: [], enabled: false, days: ['mon','tue','wed','thu','fri'], period: {utc: false, value: {from: '00:00', for: '23:59'}}, pin: '', maxcount: 0, regex: '', zones: []};
        user=[];
        card.setValue(data);
        card.setValue({period: {utc: true}});
        updateUsers();
        card.enable();
        card.show();
        sidebar_apply(sbapply);
        rightsidebar_init(null, null, sbselect);
      }

      function removePersistentRoom() {
        showdialog('Удаление комнаты','Вы уверены что действительно хотите удалить постоянную конференц-комнату?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            this.sendRequest('remove-persistent-room', {id: persistent_room_id}).success(function(data) {
              updatePersistentRooms();
              return true;
            });
          }
        });
      }

      function savePersistentRoom() {
        var data = card.getValue();
        data.from = data.period.from;
        data.to = data.period.for;
        data.offset = new moment().utcOffset();
        data.new_id=data.id;
        data.id=persistent_room_id;
        data.days = data.days.join('&');
        var users=[];
        for(var i=0; i<data.users.length; i++) {
          users.push(user[data.users[i].substr(5)]);
        }
        data.users=users;
        delete data.period;
        if(data.new_id=='') {
          showalert('warning','Не задан идентификатор комнаты');
          return false;
        }
        if((persistent_room_id!='')&&(data.new_id!=persistent_room_id)) {
          showdialog('Наименование комнаты изменено','Выберите действие с комнатой:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            var $proceed = false;
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              persistent_room_id='';
              data.id = '';
            }
            if(proceed) {
              this.sendRequest('save-persistent-room', data).success(function() {
                persistent_room_id = data.new_id;
                updatePersistentRooms();
                updatePersistentRoom();
                return true;
              });
            };
          });
        } else {
          showdialog('Изменение комнаты','Вы уверены что действительно хотите сохранить изменения постоянной конференц-комнаты?',"warning",['Yes','No'],function(e) {
            if(e=='Yes') {
              this.sendRequest('save-persistent-room', data).success(function() {
                persistent_room_id = data.new_id;
                updatePersistentRooms();
                updatePersistentRoom();
                return true;
              });
            }
          });
        }
      }

      function removePersistentRoomUser(sender, user) {
        removeRoomUser(user.id.substr(5));
        return false;
      }

      function editPersistentRoomUser(sender, user) {
        editRoomUser(user.id.substr(5));
      }

      function switchModePersistentRoomUser(sender, entry) {
        user[entry.id.substr(5)].auto=entry.control.getValue();
      }

      function sbselect(e, item) {
        loadPersistentRoom(item);
      }

      function sbapply(e) {
        savePersistentRoom();
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbadd(e) {
        newPersistentRoom();
      }

      function sbdel(e) {
        removePersistentRoom();
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
        obj = new widgets.input(card, {id: 'id', pattern: /[а-яА-Яa-zA-Z0-9«»_ -]+/, placeholder: 'unique_id'}, "Наименование конференции", "Наименовение конференц-комнаты для оператора");
        obj = new widgets.input(card, {id: 'alias'}, "Исходящий синоним конференции", "Отображаемое наименование и исходящий номер конференц-комнаты в формате: <b>[Имя]</b><i>[&lt;номер&gt;]</i>");
        obj = new widgets.select(card, {id: 'profile'}, "Профиль конференц-комнаты", "Профиль конференц-комнаты определяет набор голосовых сообщений, режим видеовещания и максимально допустимое количество участников проводимой конференции");
        obj = new widgets.buttons(card, {id: 'enabled', clean: true, value: [{id: false, text: _('off',"Выключена"), checked: true},{id: true, text: _('on',"Включена")}]}, "Режим активности комнаты");
        var subcard = new widgets.section(card,null);
        subcard.node.classList.add('row');
        cont = new widgets.columns(subcard,2);
        cont.node.classList.add('form-group');
        obj = new widgets.checkbox(cont, {id: 'activeoncall', value: false}, "Активировать при наличии участников", "Включает возможность дозвониться в конференц-комнату, если в ней присутствует хотя бы один участник.");
        cont = new widgets.columns(subcard,2);
        cont.node.classList.add('form-group');
        obj = new widgets.checkbox(cont, {id: 'disallowcallout', value: false}, "Запретить оператору сбор неактивной комнаты", "Позволяет ограничить оператору возможность вызова участников неактивной конференции.<br>Если у оператора есть право осуществлять исходящие вызовы, то данная опция к нему не применяется.");
        var subcard = new widgets.section(card,null);
        subcard.node.classList.add('row');
        cont = new widgets.columns(subcard,2);
        cont.node.classList.add('form-group');
        obj = new widgets.checkbox(cont, {id: 'useinstatistics', value: true}, "Использовать в аналитике", "Включает вывод аналитики по конференц-комнате");
        obj = new widgets.section(card, null);
        obj.node.classList.add('form-group');
        obj.node.classList.add('text-center');
        obj = new widgets.buttons(obj, {id: 'days', clean: true, multiple: true, value: [
         {id: 'mon', text: _('monday',"Понедельник"), shorttext: _('mon',"Пн"), checked: true},
         {id: 'tue', text: _('tuesday',"Вторник"), shorttext: _('tue',"Вт"), checked: true},
         {id: 'wed', text: _('wednessday',"Среда"), shorttext: _('wed',"Ср"), checked: true},
         {id: 'thu', text: _('thusday',"Четверг"), shorttext: _('thu',"Чт"), checked: true},
         {id: 'fri', text: _('friday',"Пятница"), shorttext: _('fri',"Пт"), checked: true},
         {id: 'sat', text: _('saturday',"Суббота"), shorttext: _('sat',"Сб"), checked: false},
         {id: 'sun', text: _('sunday',"Воскресенье"), shorttext: _('sun',"Вс"), checked: false}
        ]});
        obj = new widgets.datetimefromto(card, {id: 'period', format: 'HH:mm', value: {from: '00:00', for: '23:59'}});
        obj.setValue({utc: true});
        obj = new widgets.input(card, {id: 'activatebefore', value: '0', pattern: /[0-9]+/}, "Активировать за", "Позволяет осуществлять входящие вызовы за N секунд до автовызова участников комнаты");
        obj = new widgets.input(card, {id: 'pin', pattern: /[0-9]+/}, "Номер конференц-комнаты", "Задает номер конференции, запрашиваемый при входящем вызове");
        obj = new widgets.input(card, {id: 'maxcount', pattern: /[0-9]+/}, "Максимальное количество участников", "Позволяет переопределить максимальное количество участников проводимой конференции");
        obj = new widgets.input(card, {id: 'regex'}, "Шаблон фильтра", "Задает регулярное выражение фильтра входящих номеров. Если номер соответствует шаблону фильтра, доступ для такого номера будет запрещен к проводимой конференции.<br>Исключением являются номера явно добавленные в список участников конференц-комнаты.<small><br><br><b>Пример:</b> <i>.*</i><br><b>Действие:</b> Разрешает входящие подключения только явному перечню участников.<br><br><b>Пример:</b> <i>((8|+7)9[0-9]{9})|(650[0-9]{3})</i><br><b>Действие:</b> Запрещает входящие подключения сотовых телефонов и шестизначных номеров начинающихся с 650.</small>");

        obj = new widgets.section(card, null);
        obj.node.classList.add('form-group');
        obj.node.classList.add('text-center');
        add_btn = new widgets.button(obj, {onClick: showUserAdd, class: 'success'}, "Добавить участника");
        obj = new widgets.list(card, {id: 'users', controls: [{id: 'mode', class: 'buttons', initval: {clean: true, value: [{id: false, text: _('manual',"Ручной"), checked: true},{id: true, text: _('auto',"Авто")}]}}], remove: true, edit: true}, "Список участников конференц комнаты", "Задает список участников конференц-комнаты.<br>Участники, объявленные в настройках конференц-комнаты переопределяют профили совпадающих абонентов из включаемых групп участников.<br>Участники могут быть автоматически вызваны при сборе селектора по расписанию переключением режима в \"Автоматический\"");
        obj.onRemove = removePersistentRoomUser;
        obj.onEdit = editPersistentRoomUser;
        obj.onControlAction = switchModePersistentRoomUser;
        obj = new widgets.collection(card, {id: 'groups'}, "Группы участников", "Определяет набор групп участников конференц комнаты. Одна группа может быть включена в несколько конференц-комнат.");
        obj.onRemove = removeRoomGroup;
        card.hide();
        var roomprofiles = [];
        for(var i=0; i<room_profiles.length; i++) {
          roomprofiles.push({id: room_profiles[i].id, text: room_profiles[i].title});
        }
        card.setValue({profile: {value: roomprofiles, clean: true}});
        this.sendRequest('persistent-groups').success(function(data) {
          for(var i = 0; i < data.length; i++) {
            data[i].text = data[i].title;
          }
          card.setValue({groups: {value: data, clean: true}});
        });
        updatePersistentRooms();
      })
      </script>
    <?php
  }

  public function render() {
    ?>
        <input type="password" hidden/>
    <?php
  }

}

?>