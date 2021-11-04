<?php

namespace confbridge;

class PersistentGroupSettings extends \view\View {

  public static function getLocation() {
    return 'settings/rooms/group';
  }

  public static function getMenu() {
    return (object) array('name' => 'Группы участников', 'prio' => 2, 'icon' => 'oi oi-people');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-confbridge');
    $result &= self::checkPriv('settings_reader');
    return $result;
  }

  private static function ordergroup($a, $b) {
    return strcmp($a,$b);
  }

  public static function getObjects() {
    $groups = array();
    $confmodule = new \confbridge\ConfbridgeModule();
    if($confmodule) {
      $groups=$confmodule->getGroups();
    }
    usort($groups, array(__CLASS__, "ordergroup"));
    foreach($groups as $groupkey => $group) {
      $groups[$groupkey]=(object) array('id' => $group, 'text' => $group);
    }
    return $groups;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "persistent-groups": {
        $return = array();
        $groups=self::getObjects();
        foreach($groups as $group) {
          if(self::checkEffectivePriv('confbridge_group', $group->id, 'settings_reader')) $return[]=(object) array('id' => $group->id, 'title' => $group->text);
        }
        $result = self::returnResult($return);
      } break;
      case "persistent-group": {
        $confmodule = new \confbridge\ConfbridgeModule();
        $abmodule = null;
        if(($confmodule)&&isset($request_data->id)&&self::checkEffectivePriv('confbridge_group', $request_data->id, 'settings_reader')) { 
          $return = new \stdClass();
          $ini = self::getINI('/etc/asterisk/confbridge.conf');
          $return=$confmodule->getPersistentGroup($request_data->id);
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
          $return->readonly=!self::checkEffectivePriv('confbridge_group', $request_data->id, 'settings_writer');
          $result = self::returnResult($return);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove-persistent-group": {
        $confmodule=new \confbridge\ConfbridgeModule();
        if(($confmodule)&&isset($request_data->id)&&self::checkPriv('settings_writer')) { 
          if($confmodule->removePersistentGroup($request_data->id)) {
            $result = self::returnSuccess('Группа участников конференц комнат успешно удалена');
            $rooms=$confmodule->getRooms();
            foreach($rooms as $room) {
              $group_cnt=$this->ami->DBget('conf_'.$room, 'groups');
              $groups = array();
              for($i=1; $i<=$group_cnt; $i++) {
                $groups[]=$this->ami->DBget('conf_'.$room, 'group_'.$i.'/id');
              }
              if(in_array($request_data->id, $groups)) {
                $confmodule->cancelPersistentRoom($room);
              }
            }
            $confmodule->schedulePersistentRooms();
          } else {
            $result = self::returnError('danger', 'Не удалось удалить группу участников конференц-комнат');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "save-persistent-group": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if(($confmodule)&&isset($request_data->id)&&self::checkEffectivePriv('confbridge_group', $request_data->id, 'settings_writer')) { 
          if(!is_array($request_data->users)) $request_data->users = array();
          if($confmodule->savePersistentGroup($request_data->id, $request_data->new_id, $request_data->users)) {
            $rooms=$confmodule->getRooms();
            foreach($rooms as $room) {
              $group_cnt=$this->ami->DBGet('conf_'.$room, 'groups');
              $groups = array();
              for($i=1; $i<=$group_cnt; $i++) {
                $groups[]=$this->ami->DBGet('conf_'.$room, 'group_'.$i.'/id');
              }
              if(in_array($request_data->id, $groups)) {
                $confmodule->cancelPersistentRoom($room);
              }
            }
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось сохранить группу частников конференц-комнаты');
          }
          $confmodule->schedulePersistentRooms();
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
      var card = null;
      var add_btn = null;
      var persistent_group_id='';
      function updatePersistentGroups() {
        this.sendRequest('persistent-groups').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==persistent_group_id) hasactive=true;
              data[i].active = data[i].id==persistent_group_id;
              items.push(data[i]);
            }
          }
          rightsidebar_set(items);
          if(!hasactive) {
            card.hide();
            window.history.pushState(persistent_group_id, $('title').html(), '/'+urilocation);
            persistent_group_id='';
            sidebar_apply(null);
            rightsidebar_init(null, sbadd, sbselect);;
            if(data.length>0) loadPersistentGroup(data[0].id);
          } else {
            sidebar_apply(sbapply);
            rightsidebar_init(sbdel, sbadd, sbselect);
          }
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

      function updatePersistentGroup() {
        if(persistent_group_id!=0) this.sendRequest('persistent-group', {id: persistent_group_id}).success(function(data) {
          if(data.id !== undefined) {
            rightsidebar_activate(data.id);
            rightsidebar_init(data.readonly?null:sbdel, ((menu_profiles.length == 0)||(user_profiles.length == 0))?null:sbadd, sbselect);
            user=data.users;
            delete data.users;
            card.setValue(data);
            if((menu_profiles.length == 0)||(user_profiles.length == 0)) add_btn.hide();
            updateUsers();
            card.show();
            if(data.readonly) {
              card.disable();
              userdialog.disable();
            } else {
              card.enable();
              userdialog.enable();
            }
            sidebar_apply(data.readonly?null:sbapply);
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

      function loadPersistentGroup(aid) {
        persistent_group_id=aid;
        updatePersistentGroup();
      }

      function newPersistentGroup() {
        persistent_group_id='';
        rightsidebar_activate(null);
        card.setValue({id: 'Новая группа участников', zones: []});
        user=[];
        updateUsers();
        card.show();
        sidebar_apply(sbapply);
        rightsidebar_init(null, null, sbselect);
      }

      function removePersistentGroup() {
        showdialog('Удаление группы','Вы уверены что действительно хотите удалить постоянную группу участников конференц-комнат?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            this.sendRequest('remove-persistent-group', {id: persistent_group_id}).success(function() {
              updatePersistentGroups();
              return true;
            });
          }
        });
      }

      function sendPersistentGroup() {
        var group=card.getValue();
        var users=[];
        for(var i=0; i<group.users.length; i++) {
          users.push(user[group.users[i].substr(5)]);
        }
        this.sendRequest('save-persistent-group', {id: persistent_group_id, new_id: group.id, zones: group.zones, users: users}).success(function() {
          persistent_group_id = group.id;
          updatePersistentGroups();
          updatePersistentGroup();
          return true;
        });
      }

      function savePersistentGroup() {
        var group=card.getValue();
        if(group.id=='') {
          showalert('warning','Не задано наименование группы');
          return false;
        }
        var proceed = false;
        if((persistent_group_id!='')&&(group.id!=persistent_group_id)) {
          showdialog('Наименование группы изменено','Выберите действие с группой:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              persistent_group_id='';
            }
            if(proceed) {
              sendPersistentGroup();
            };
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendPersistentGroup();
        };
      }

      function removeGroupUser(sender, user) {
        removeRoomUser(user.id.substr(5));
        return false;
      }

      function editGroupUser(sender, user) {
        editRoomUser(user.id.substr(5));
      }

      function switchModeGroupUser(sender, entry) {
        user[entry.id.substr(5)].auto=entry.control.getValue();
      }

      function sbselect(e, item) {
        loadPersistentGroup(item);
      }

      function sbapply(e) {
        savePersistentGroup();
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbadd(e) {
        newPersistentGroup();
      }

      function sbdel(e) {
        removePersistentGroup();
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
        obj = new widgets.input(card, {id: 'id', pattern: /[а-яА-Яa-zA-Z0-9«»_ -]+/, placeholder: 'unique_id'}, "Наименование группы", "Наименовение группы участников конференц-комнат");
        obj = new widgets.section(card, null);
        obj.node.classList.add('form-group');
        obj.node.classList.add('text-center');
        add_btn = new widgets.button(obj, {onClick: showUserAdd, class: 'success'}, "Добавить участника");
        obj = new widgets.list(card, {id: 'users', controls: [{id: 'mode', class: 'buttons', initval: {small: true, clean: true, value: [{id: false, text: _('manual',"Ручной"), checked: true},{id: true, text: _('auto',"Авто")}]}}], remove: true, edit: true}, "Список абонентов группы участников конференц комнат", "Позволяет задать постоянный переиспользуемый список участников, который можно указывать в разных конференц-комнатах.<br>Участники могут быть автоматически вызваны при сборе селектора по расписанию переключением режима в \"Автоматический\"");
        obj.onRemove = removeGroupUser;
        obj.onEdit = editGroupUser;
        obj.onControlAction = switchModeGroupUser;
        card.hide();
        updatePersistentGroups();
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