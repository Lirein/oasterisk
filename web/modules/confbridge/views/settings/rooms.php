<?php

namespace confbridge;

class RoomsSettings extends \view\Menu implements \module\IJSON {

  public static function getLocation() {
    return 'settings/rooms';
  }

  public static function getMenu() {
    return (object) array('name' => 'Конференц-комнаты', 'prio' => 6, 'icon' => 'oi oi-people');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "get-sounds": {
        $sounds=new \core\Sounds();
        $sounddata=array();
        foreach($sounds->get() as $v => $dummy) {
          $sounddata[] = (object) array('id' => $v, 'text' => $v);
        }
        $result = self::returnResult($sounddata);
      } break;
      case "get-contexts": {
        $dialplan=new \core\Dialplan();
        $contexts=array();
        foreach($dialplan->getContexts() as $v) {
          $contexts[] = (object) array('id' => $v->id, 'text' => $v->title);
        }
        $result = self::returnResult($contexts);
      } break;
      case "user-profiles": {
        $module = new \confbridge\UserSettings();
        $result=$module->json($request, $request_data);
      } break;
      case "menu-profiles": {
        $module = new \confbridge\MenuSettings();
        $result=$module->json($request, $request_data);
      } break;
      case "room-profiles": {
        $module = new \confbridge\RoomSettings();
        $result=$module->json($request, $request_data);
      } break;
      case "users": {
        $result =self::returnResult(self::getAsteriskPeers());
      } break;
      case "contact-property": {
        if(isset($request_data->id)) {
          $contactinfo = new ConfbridgeProperty($request_data->id);
          $result = self::returnResult($contactinfo->getProperties());
        } else {
          $result = self::returnError('warning', 'Идентификатор контакта не указан');
        }
      } break;
    }
    return $result;
  }

  public function implementation() {
    ?>
    <script>
      var userdialog = null;
      var sound_data = [];
      var context_data = [];
      var room_profiles = [];
      var user_profiles = [];
      var menu_profiles = [];

      $(function () {
        this.sendRequest('get-sounds').success(function(sounds) {
          sound_data.splice(0);
          sound_data.push.apply(sound_data,{id: '', text: 'Не указано'});
          sound_data.push.apply(sound_data,sounds);
          return false;
        });
        this.sendRequest('get-contexts').success(function(contexts) {
          context_data.splice(0);
          context_data.push.apply(context_data,contexts);
          return false;
        });
        this.sendRequest('room-profiles').success(function(data) {
          room_profiles = data;
          return false
        });
        this.sendRequest('menu-profiles').success(function(data) {
          menu_profiles = data;
        });
        this.sendRequest('user-profiles').success(function(data) {
          user_profiles = data;
        });

        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
        userdialog = new widgets.dialog(rootcontent, {}, "Добавление постоянного участника");
        userdialog.data={id: '', profile: '', menu: '', chan: '', callerid: '', trunknumber: ''};
        var obj=new widgets.select(userdialog, {id: 'profile'}, "Профиль участника", "Определяет возможные права доступа и поведение конференц-комнаты для такого участника");
        obj=new widgets.select(userdialog, {id: 'menu'}, "Профиль меню участника", "Определяет набор действий участника в конференции при нажатии клавиш на телефоне (DTMF)");
        obj=new widgets.input(userdialog, {id: 'pin', pattern: /[0-9]+/}, "Пинкод участника", "Задание пинкода позволяет подключаться к конференции входящим вызовом любым абонентам, знающим пинкод участника");
        var sect=new widgets.section(userdialog);
        sect.node.classList.add('row');
        var col=new widgets.section(sect);
        col.node.classList.add('form-group');
        col.node.classList.add('col-xs-12');
        col.node.classList.add('col-sm-4');
        col.node.classList.add('col-md-3');
        obj=new widgets.buttons(col, {id: 'auto', clean: true, value: [{id: false, text: _('manual',"Ручной"), checked: true},{id: true, text: _('auto',"Авто")}]});
        col=new widgets.section(sect);
        col.node.classList.add('col-xs-12');
        col.node.classList.add('col-sm-8');
        col.node.classList.add('col-md-9');
        obj=new widgets.input(col, {id: 'delay', pattern: /[\-]{0,1}[0-9]*/, inline: true}, "Звонить за","Позволяет указать время в секундах для вызова участника <b>до</b> времени активации конференции.<br>Указание отрицательного значения позволяет запланировать вызов участника после времени активации конференции.");
        var label = obj.label.cloneNode();
        label.textContent="секунд";
        obj.node.appendChild(label);
        obj.inputdiv.classList.remove('col-12');
        obj.inputdiv.classList.remove('col-md-7');
        obj.inputdiv.classList.add('col-6');
        obj.inputdiv.classList.add('col-sm-5');
        obj.inputdiv.classList.add('col-md-4');
        sect=new widgets.section(userdialog);
        sect.node.classList.add('row');
        sect.node.classList.add('form-group');
        obj=new widgets.select(sect, {id: 'chantype'}, "Тип канала");
        obj.onChange=dialogChannelTypeChange;
        obj.node.classList.remove('form-group');
        obj.node.classList.remove('row');
        obj.node.classList.add('col-3');
        obj.node.classList.add('col-lg-2');
        obj.label.className='form-label';
        obj.inputdiv.className='input-group';
        obj=new widgets.select(sect, {id: 'chan'}, "Абонент/Шлюз");
        obj.onChange=dialogChannelChange;
        obj.node.classList.remove('form-group');
        obj.node.classList.remove('row');
        obj.node.classList.add('col-6');
        obj.node.classList.add('col-lg-7');
        obj.label.className='form-label';
        obj.inputdiv.className='input-group';
        userdialog.chanuser=obj;
        obj=new widgets.input(sect, {id: 'trunknum', pattern: /[UINLSVR]{0,1}[0-9]*/}, "Вызываемый номер");
        obj.node.classList.remove('form-group');
        obj.node.classList.remove('row');
        obj.node.classList.add('col-3');
        obj.label.className='form-label';
        obj.inputdiv.className='input-group';
        obj.onChange=this.dialogTrunknumChange;
        userdialog.trunknum=obj;
        sect=new widgets.section(userdialog);
        sect.node.classList.add('row');
        userdialog.trunkdata=sect;
        obj=new widgets.input(sect, {id: 'extnum', pattern: /[0-9]+/}, "Дополнительный номер", "Донабираемый внутренний номер сотрудника после события поднятия трубки");
        obj.node.classList.add('col-12');
        obj.node.classList.add('col-md-7');
        obj.inputdiv.classList.remove('col-md-7');
        obj.inputdiv.classList.add('col-md-6');
        obj=new widgets.input(sect, {id: 'extdelay', pattern: /[0-9]+/, inline: true}, "Задержка", {caption: "Задержка (в секундах)", text: "Период ожидания перед набором дополнительного внутреннего номера сотрудника"});
        obj.node.classList.add('col-12');
        obj.node.classList.add('col-md-5');
        obj=new widgets.input(userdialog, {id: 'callerid'}, "Отображаемое имя");
        sect=new widgets.section(userdialog);
        sect.node.classList.add('row');
        sect.node.classList.add('form-group');
        obj=new widgets.input(sect, {id: 'retries', pattern: /[0-9]+/}, "Количество повторов", "Сколько раз пытаться перезвонить участнику, если его не удалось вызвать с первой попытки");
        obj.node.classList.remove('form-group');
        obj.node.classList.remove('row');
        obj.node.classList.add('col-12');
        obj.node.classList.add('col-md-4');
        obj.label.className='form-label';
        obj.inputdiv.className='input-group';
        obj=new widgets.input(sect, {id: 'timeout', pattern: /[0-9]+/}, "Длительность", {caption: "Длительность (в секундах)", text: "Время в течение которого осуществляется попытка вызова участника"});
        obj.node.classList.remove('form-group');
        obj.node.classList.remove('row');
        obj.node.classList.add('col-12');
        obj.node.classList.add('col-md-4');
        obj.label.className='form-label';
        obj.inputdiv.className='input-group';
        obj=new widgets.input(sect, {id: 'retry', pattern: /[0-9]+/}, "Задержка", {caption: "Задержка (в секундах)", text: "Период между повторными попытками вызова участника"});
        obj.node.classList.remove('form-group');
        obj.node.classList.remove('row');
        obj.node.classList.add('col-12');
        obj.node.classList.add('col-md-4');
        obj.label.className='form-label';
        obj.inputdiv.className='input-group';
        userdialog.onOpen=dialogShow;
        userdialog.onSave=dialogSave;
      });

      function dialogShow(sender) {
        users = [];
        this.sendRequest('users').success(function(data) {
          users=data;
          if(users.length>0) {
            var type=localStorage.getItem("confbridge-usertype");
            var found=false;
            for(var i=0; i<users.length; i++) {
              if(users[i].type==type) {
                found=true;
                break;
              }
            }
            if(!found) type=users[0].type;
          }
          dialogSetUsers(sender, type, sender.data.chan);
        });
        if(menu_profiles.length) {
          var profile=sender.getValue().menu;
          var profiles=[];
          for(var i = 0; i < menu_profiles.length; i++) {
            profiles.push({id: menu_profiles[i].id, text: menu_profiles[i].title, checked: (menu_profiles[i].id==profile)});
          }
          sender.setValue({menu: {value: profiles, clean: true}});
        }
        if(user_profiles.length) {
          var profile=sender.getValue().profile;
          var profiles=[];
          for(var i = 0; i < user_profiles.length; i++) {
            profiles.push({id: user_profiles[i].id, text: user_profiles[i].title, checked: (user_profiles[i].id==profile)});
          }
          sender.setValue({profile: {value: profiles, clean: true}});
        }
        return true;
      }

      function dialogSave(sender) {
        var id=sender.data.id;
        var newuser=sender.getValue();
        var chan=newuser.chantype+'/'+newuser.chan;
        if(newuser.trunknum!='') chan+='/'+newuser.trunknum;
        newuser.chan=chan;
        for(var i=0; i<user_profiles.length; i++) {
          if(user_profiles[i].id==newuser.profile) {
            newuser.admin=user_profiles[i].admin;
            newuser.marked=user_profiles[i].marked;
            break;
          }
        }
        if(id=='') { //new user
          user.push(newuser);
        } else { //old user
          user[id] = newuser;
        }
        updateUsers();
        return true;
      }

      function dialogTrunknumChange(sender) {
        userdialog.data.trunknum = sender.getValue();
        return true;
      }

      function dialogChannelTypeChange(sender) {
        userdialog.setValue({callerid: ''});
        dialogSetUsers(userdialog, sender.getValue(), '');
        return true;
      }

      function dialogChannelChange(sender) {
        var user = {type: '', login: sender.getValue()};
        let chandata = userdialog.data.chan.split('/');
        if(chandata[1] != user.login) {
          userdialog.setValue({callerid: ''});
          for(var i = 0; i < users.length; i++) {
            if(users[i].login == user.login) {
              user=users[i];
              break;
            }
          }
          userdialog.data.chan =  user.type+'/'+user.login;
          if(user.type == 'Local') {
            this.sendRequest('contact-property', {id: user.login}).success(function(data) {
              let values = {};
              if(data.pincode != "") values.pin = data.pincode; else values.pin='';
              if(data.retries != "") values.retries = data.retries; else values.retries='2';
              if(data.dialtimeout != "") values.timeout = data.dialtimeout; else values.timeout='10';
              if(data.retrydelay != "") values.retry = data.retrydelay; else values.retry='5';
              if(data.calldelay != "") values.delay = data.calldelay; else values.delay='0';
              userdialog.setValue(values);
            });
          }
          dialogSetUser(userdialog,sender.getValue());
        }
        return true;
      }

      function dialogSetUsers(dialog, type, chan) {
        var chans = [];
        var channels = [];
        var chanusers = [];
        var user = "";
        var trunknum = "";
        if(chan!='') {
          var chandata=chan.split('/');
          type=chandata[0];
          user=chandata[1];
          if(chandata.length>2) {
            trunknum=chandata[2];
          }
        }
        for(var i=0; i<users.length; i++) {
          if(find(chans, users[i].type)==-1) chans.push(users[i].type);
          if(type==users[i].type) chanusers.push({id: users[i].login, text: ((users[i].name=='')?users[i].number:users[i].name), checked: (user==users[i].login)});
        }
        for(var i=0; i<chans.length; i++) {
          channels.push({id: chans[i], text: chans[i], checked: (type==chans[i])});
        }
        if(channels.length==0) {
          channels.push({id: type, text: type, checked: true});
        }
        if(chanusers.length==0) {
          chanusers.push({id: user, text: user, checked: true});
        }
        localStorage.setItem("confbridge-usertype", type);
        if(user == '') {
          user = chanusers[0].id;
          chanusers[0].checked = true;
        }
        dialog.setValue({chantype: {clean: true, value: channels}, chan: {clean: true, value: chanusers}, trunknum: trunknum});
        dialogSetUser(dialog, user);
        if(chan=='') {
          dialogChannelChange(dialog.content.querySelector('#chan').widget);
        }
      }

      function dialogSetUser(dialog, auser) {
        var data=dialog.getValue();
        var trunkNumber = data.trunknum;
        var extendNumber = data.extnum;
        var extendNumberDelay = data.extendelay;
        var callerId = '';
        var user = {};
        for (var i = 0; i < users.length; i++) {
          if((users[i].login == data.chan)||(users[i].login == auser)) {
            user=users[i];
            break;
          } 
        }
        if(!isSet(user.mode)) user.mode=(trunkNumber=='')?'peer':'trunk';
        callerId=String(user.name).trim();
        if((callerId!=data.callerid)&&(data.callerid!='')) callerId=data.callerid;
        if(user.mode==='peer') {
          dialog.trunknum.hide();
          dialog.trunkdata.hide();
          dialog.chanuser.node.classList.remove('col-lg-7');
          dialog.chanuser.node.classList.add('col-lg-10');
          userdialog.data.trunknum = '';
        } else {
          dialog.chanuser.node.classList.remove('col-lg-10');
          dialog.chanuser.node.classList.add('col-lg-7');
          dialog.trunknum.show();
          dialog.trunkdata.show();
          userdialog.data.trunknum = trunkNumber;
        }
        dialog.setValue({trunknum: trunkNumber, extnum: extendNumber, extdelay: extendNumberDelay, callerid: callerId});
      }

      function showUserAdd() {
        userdialog.setLabel('Добавление постоянного участника');
        userdialog.savebtn.setLabel('Добавить');
        userdialog.data.id='';
        userdialog.setValue({pin: '', callerid: '', profile: 'default-user', menu: 'default-menu', delay: 0, retries: 2, retry: 5, timeout: 10, auto: false, chan: '', extnum: '', trunknum: '', extdelay: 5});
        userdialog.show();
      }

      function removeRoomUser(id) {
        showdialog('Удаление участника','Вы уверены что действительно хотите удалить участника из конференц-комнаты?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            user.splice(id,1);
            updateUsers();
          }
        });
      }

      function editRoomUser(id) {
        userdialog.data.id = id;
        userdialog.data.profile = user[id].profile;
        userdialog.data.menu = user[id].menu;
        userdialog.data.chan = user[id].chan;
        userdialog.data.callerid = user[id].callerid;
        let tmpuser = Object.assign({}, user[id]);
        let chandata = tmpuser.chan.split('/');
        tmpuser.chantype = chandata[0];
        tmpuser.chan = chandata[1];
        userdialog.setValue(tmpuser);
        userdialog.setLabel('Изменение постоянного участника');
        userdialog.savebtn.setLabel('Применить');
        userdialog.show();
      }
     </script>
    <?php
  }

  public function dialog() {
    ?>
    <?php
  }

}

?>