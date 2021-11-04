<?php

namespace confbridge;

class MenuSettings extends \view\View {

  public static function getLocation() {
    return 'settings/roomprofiles/menu';
  }

  public static function getMenu() {
    return (object) array('name' => 'Профили меню', 'prio' => 3, 'icon' => 'oi oi-menu');
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
      if(isset($v->type)&&($v->type=='menu')) {
        $profile=new \stdClass();
        $profile->id = $k;
        $profile->text = empty($v->getComment())?$k:$v->getComment();
        $profiles[]=$profile;
      }
    }
    return $profiles;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menu-profiles": {
        $returnData = array();
        $profiles=self::getObjects();
        foreach($profiles as $profile) {
          if(self::checkEffectivePriv('confbridge_profile', $profile->id, 'settings_reader')) $returnData[] = (object) array('id' => $profile->id, 'title' => $profile->text);
        }
        $result = self::returnResult($returnData);
      } break;
      case "menu-profile": {
        if(isset($request_data->menu)&&self::checkEffectivePriv('confbridge_profile', $request_data->menu, 'settings_reader')) {
          $profile = new \stdClass();
          $ini = self::getINI('/etc/asterisk/confbridge.conf');
          $menu = $request_data->menu;
          if(isset($ini->$menu)) {
            $v = $ini->$menu;
            if(isset($v->type)&&($v->type == 'menu')) {
              $profile->id = $menu;
              $profile->title = empty($v->getComment())?$menu:$v->getComment();
              $profile->actions = array();
              foreach($v as $dtmf => $action) {
                if($dtmf!=='type') $profile->actions[] = (string)$dtmf.'='.((string) $action);
              }
              $profile->readonly = !self::checkEffectivePriv('confbridge_profile', $menu, 'settings_writer');
              $result=self::returnResult($profile);
            }
          }
        }
      } break;
      case "set-menu-profile": {
        if(isset($request_data->orig_id)&&self::checkEffectivePriv('confbridge_profile', $request_data->orig_id, 'settings_writer')) {
          $ini = self::getINI('/etc/asterisk/confbridge.conf');
          $id = $request_data->id;
          if(isset($request_data->orig_id)&&($request_data->orig_id != '')&&($request_data->orig_id != $request_data->id)) {
            $orig_id = $request_data->orig_id;
            if(isset($ini->$id)) {
              $result=self::returnError('danger', "Профиль с таким иденификатором уже существует");
              break;
            }
            if(isset($ini->$orig_id))
              unset($ini->$orig_id);
          }
          if((!isset($request_data->orig_id)) || $request_data->orig_id=='') {
            if(isset($ini->$id)) {
              $result=self::returnError('danger', "Профиль с таким иденификатором уже существует");
              break;
            }
          }
          unset($ini->$id);
          $ini->$id->type = 'menu';
          foreach($request_data->actions as $action) {
            $dtmf = (string) $action->dtmf;
            if($action->data!='') {
              $ini->$id->$dtmf = $action->action.'('.$action->data.')';
            } else {
              $ini->$id->$dtmf = $action->action;
            }
          }
          $ini->$id->setComment($request_data->title);
          $ini->save();
          $confmodule = new \confbridge\ConfbridgeModule();
          if($confmodule&&$confmodule->configReload()) {
            $result=self::returnSuccess();
          }
        }
      } break;
      case "remove-menu-profile": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $result = self::returnError('danger', 'Невозможно удалить профиль меню');
          $ini = self::getINI('/etc/asterisk/confbridge.conf');
          $id = $request_data->id;
          if(isset($ini->$id)) {
            $v = $ini->$id;
            if(isset($v->type)&&($v->type == 'menu')) {
              unset($ini->$id);
              $ini->save();
              $confmodule = new \confbridge\ConfbridgeModule();
              if($confmodule&&$confmodule->configReload()) {
                $result = self::returnSuccess('Профиль меню успешно удален');
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
      widgets.confmenu=class confmenuWidget extends baseWidget {
        constructor(parent, data, label, hint) {
          super(parent,data,label,hint);
          this.node = document.createElement('div');
          this.node.widget = this;
          this.node.className='form-group row';
          if(this.label) {
            this.label.className = 'col-12 form-label';
            this.node.appendChild(this.label);
          }
          this.context_data=[];
          this.sound_data=[];
          this.sound_select = {theme: "bootstrap", minimumInputLength: 1, ajax: {
              transport: function(params, success, failure) {
                // fitering if params.data.q available
                var items = [];
                if (params.data && params.data.q) {
                  items = this.sender.sound_data.filter(function(item) {
                      return new RegExp(params.data.q, 'i').test(item.text);
                  });
                }
                var promise = new Promise(function(resolve, reject) {
                  resolve({results: items});
                });
                promise.then(success);
                promise.catch(failure);
              },
              sender: this
            },
            dropdownAutoWidth: true,
          };
          this.context_select = {theme: "bootstrap", minimumInputLength: 1, ajax: {
              transport: function(params, success, failure) {
                // fitering if params.data.q available
                var items = [];
                if (params.data && params.data.q) {
                  items = this.sender.context_data.filter(function(item) {
                      var res1 = new RegExp(params.data.q, 'i').test(item.id);
                      var res2 = new RegExp(params.data.q, 'i').test(item.text);
                      return res1 || res2;
                  });
                }
                var promise = new Promise(function(resolve, reject) {
                  resolve({results: items});
                });
                promise.then(success);
                promise.catch(failure);
              },
              sender: this
            },
            dropdownAutoWidth: true,
          };
          this.content = document.createElement('div');
          this.content.className='form-group col-12';
          this.node.appendChild(this.content);
          this.inputlabel = document.createElement('label');
          this.inputlabel.className='col-12 form-label';
          this.inputlabel.textContent=_('newmenuentry',"Новый пункт меню");
          this.node.appendChild(this.inputlabel);
          this.inputdiv = this.createMenuAction('#', 'no_op', '');
          this.inputdiv.classList.add('col-12');
          this.inputdiv.widget=this;
          this.inputdiv.btn.textContent='+';
          this.inputdiv.btn.onclick=this.newMenuAction;
          this.node.appendChild(this.inputdiv);
          this.setParent(parent);
          this.inputdiv.select.onchange({target: this.inputdiv.select});
          if((isSet(data)) && data ) this.setValue(data);
        }
        inputKeypress(sender) {
          var BACKSPACE = 8;
          var DELETE = 46;
          var TAB = 9;
          var LEFT = 37 ;
          var UP = 38 ;
          var RIGHT = 39 ;
          var DOWN = 40 ;
          var END = 35 ;
          var HOME = 35 ;
          var result = false;
          // Checking backspace and delete  
          if(sender.keyCode == BACKSPACE || sender.keyCode == DELETE || sender.keyCode == TAB 
              || sender.keyCode == LEFT || sender.keyCode == UP || sender.keyCode == RIGHT || sender.keyCode == DOWN)  {
              result = true;
          }
          if(sender.target.pattern) {
            var expr=RegExp('^'+sender.target.pattern+'$','g');
            result = expr.test(sender.target.value.substr(0,sender.target.selectionStart)+sender.key+sender.target.value.substr(sender.target.selectionEnd));
          } else result = true;
          return result;
        }
        createMenuAction(dtmf, action, data) {
          var inputdiv = document.createElement('div');
          inputdiv.className = 'input-group';
          inputdiv.input = document.createElement('input');
          inputdiv.input.type='text';
          inputdiv.input.className='form-control col-2';
          inputdiv.input.pattern='[#*0-9A-D]+';
          inputdiv.input.value=dtmf;
          inputdiv.input.widget=this;
          inputdiv.input.entry=inputdiv;
          inputdiv.input.onkeypress=this.inputKeypress;
          inputdiv.appendChild(inputdiv.input);
          inputdiv.select = document.createElement('select');
          inputdiv.select.className='custom-select col';
          inputdiv.select.onchange=this.selectMenuAction;
          inputdiv.select.widget=this;
          inputdiv.select.entry=inputdiv;
          var options=[
                  {id: 'playback', text: 'Проиграть и выйти'},
                  {id: 'playback_and_continue', text: 'Проиграть и продолжить'},
                  {id: 'toggle_mute', text: 'Включить/Выключить микрофон'},
                  {id: 'no_op', text: 'Ничего не делать'},
                  {id: 'decrease_listening_volume', text: 'Уменьшить громкость'},
                  {id: 'increase_listening_volume', text: 'Увеличить громкость'},
                  {id: 'reset_listening_volume', text: 'Сбросить громкость'},
                  {id: 'decrease_talking_volume', text: 'Уменьшить громкость микрофона'},
                  {id: 'increase_talking_volume', text: 'Увеличить громкость микрофона'},
                  {id: 'reset_talking_volume', text: 'Сбросить громкость микрофона'},
                  {id: 'dialplan_exec', text: 'Выполнить диалплан'},
                  {id: 'leave_conference', text: 'Покинуть конференц-комнату'},
                  {id: 'set_as_single_video_src', text: 'Стать единственным источником видео'},
                  {id: 'release_as_single_video_src', text: 'Перестать быть единственным источником видео'},
                  {id: 'participant_count', text: 'Узнать количество участников'},
                  {id: 'admin_kick_last', text: '•Выкинуть последнего участника'},
                  {id: 'admin_toggle_conference_lock', text: '•Заблокировать/Разблокировать конференцию'},
                  {id: 'admin_toggle_mute_participants', text: '•Отключить/Включить микрофоны участников'}
          ];
          for(var i=0; i<options.length; i++) {
            var item = document.createElement('option');
            item.value=options[i].id;
            item.textContent=options[i].text;
            inputdiv.select.appendChild(item);
          }
          inputdiv.appendChild(inputdiv.select);
          inputdiv.subhidden = document.createElement('span');
          inputdiv.subhidden.className='col-5 row';
          inputdiv.appendChild(inputdiv.subhidden);
          inputdiv.hiddendata = document.createElement('input');
          inputdiv.hiddendata.type='hidden';
          inputdiv.hiddendata.value=data;
          inputdiv.subhidden.appendChild(inputdiv.hiddendata);
          inputdiv.soundselect = document.createElement('select');
          inputdiv.soundselect.className='custom-select';
          inputdiv.subhidden.appendChild(inputdiv.soundselect);
          inputdiv.contextselect = document.createElement('select');
          inputdiv.contextselect.className='custom-select';
          inputdiv.subhidden.appendChild(inputdiv.contextselect);
          inputdiv.exten = document.createElement('input');
          inputdiv.exten.className='form-control';
          inputdiv.exten.type='text';
          inputdiv.exten.pattern='.+';
          inputdiv.exten.onkeypress=this.inputKeypress;
          inputdiv.subhidden.appendChild(inputdiv.exten);
          inputdiv.prio = document.createElement('input');
          inputdiv.prio.className='form-control';
          inputdiv.prio.type='text';
          inputdiv.prio.pattern='[0-9]+';
          inputdiv.prio.onkeypress=this.inputKeypress;
          inputdiv.subhidden.appendChild(inputdiv.prio);
          inputdiv.subbtn = document.createElement('span');
          inputdiv.subbtn.className='input-group-append';
          inputdiv.appendChild(inputdiv.subbtn);
          inputdiv.btn = document.createElement('button');
          inputdiv.btn.className='btn btn-secondary';
          inputdiv.btn.textContent='-';
          inputdiv.btn.onclick=this.removeMenuAction;
          inputdiv.btn.widget=this;
          inputdiv.btn.entry=inputdiv;
          inputdiv.subbtn.appendChild(inputdiv.btn);
          inputdiv.select.value=action;
          return inputdiv;
        }
        getMenuAction(sender) {
          var actiondata = {dtmf: '', action: '', data: ''};
          actiondata.dtmf = sender.input.value;
          actiondata.action = sender.select.value;
          switch(actiondata.action) {
            case "dialplan_exec": {
              actiondata.data = sender.contextselect.value+','+sender.exten.value+','+sender.prio.value;
            } break;
            case "playback":
            case "playback_and_continue": {
              actiondata.data = sender.soundselect.value;
            } break;
          }
          return actiondata;
        }
        removeMenuAction(sender) {
          var result = true;
          sender.target.entry.parentNode.removeChild(sender.target.entry);
          return false;
        }
        newMenuAction(sender) {
          var result = true;
          var data = sender.target.widget.getMenuAction(sender.target.entry);
          for(var i=0; i<sender.target.widget.content.childNodes.length; i++) {
            if(sender.target.widget.content.childNodes[i].input.value==data.dtmf) return false;
          }
          var entry=sender.target.widget.createMenuAction(data.dtmf, data.action, data.data);
          entry.widget=sender.target.widget;
          sender.target.widget.content.appendChild(entry);
          entry.select.onchange({target: entry.select});
          return result;
        }
        selectMenuAction(sender) {
          var result = true;
          var entry=sender.target.entry;
          var action=sender.target.value;
          switch(action) {
            case "dialplan_exec": {
              entry.soundselect.style.display='none';
              $(entry.soundselect).select2().next().hide();
              entry.contextselect.style.width='50%';
              entry.contextselect.style.display=null;
              $(entry.contextselect).select2(entry.widget.context_select).next().css('width', '50%').show();
              entry.exten.style.display=null;
              entry.exten.style.width='30%';
              entry.prio.style.display=null;
              entry.prio.style.width='20%';
              var list = entry.hiddendata.value.split(',',3);
              if(list[0]=='') list[0]='public';
              entry.contextselect.value=list[0];
              if(!entry.contextselect.value) {
                var j=entry.widget.context_data.indexOfId(list[0]);
                var option = document.createElement('option');
                if(j==-1) {
                  option.innerText=list[0];
                  option.value=list[0];
                } else {
                  option.innerText=entry.widget.context_data[j].text;
                  option.value=entry.widget.context_data[j].id;
                }
                entry.contextselect.appendChild(option);
                entry.contextselect.value=list[0];
              }
              $(entry.contextselect).trigger('change');
              entry.exten.value=(list.length>1)?list[1]:'s';
              entry.prio.value=(list.length>2)?list[2]:'1';
              entry.subhidden.style.display=null;
            } break;
            case "playback":
            case "playback_and_continue": {
              entry.soundselect.style.display=null;
              entry.soundselect.style.width='100%';
              $(entry.soundselect).select2(entry.widget.sound_select).next().css('width', '100%').show();
              entry.contextselect.style.display='none';
              $(entry.contextselect).select2().next().hide();
              entry.exten.style.display='none';
              entry.prio.style.display='none';
              entry.soundselect.value=entry.hiddendata.value;
              if(!entry.soundselect.value) {
                var j=entry.widget.sound_data.indexOfId(entry.hiddendata.value);
                var option = document.createElement('option');
                if(j==-1) {
                  option.innerText=entry.hiddendata.value;
                  option.value=entry.hiddendata.value;
                } else {
                  option.innerText=entry.widget.sound_data[j].text;
                  option.value=entry.widget.sound_data[j].id;
                }
                entry.soundselect.appendChild(option);
                entry.soundselect.value=entry.hiddendata.value;
              }
              $(entry.soundselect).trigger('change');
              entry.subhidden.style.display=null;
            } break;
            default: {
              entry.subhidden.style.display='none';
            }
          }
          return false;
        }
        setValue(avalue) {
          if(typeof avalue == 'string') {
            avalue = {value: [avalue]};
          } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
            avalue = {value: avalue};
          }
          if((!isSet(avalue.id))&&(this.content.id == '')) avalue.id='confmenu-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
          if(isSet(avalue.id)) {
            this.content.id=avalue.id;
            if(this.label) this.label.htmlFor=this.content.id;
          }
          if(isSet(avalue.sound_data)) {
            this.sound_data = avalue.sound_data;
          }
          if(isSet(avalue.context_data)) {
            this.context_data = avalue.context_data;
          }
          if(isSet(avalue.value)) {
            this.content.textContent='';
            for(var i=0; i<avalue.value.length; i++) {
              if(typeof avalue.value[i] == 'string') {
                var list=avalue.value[i].split('=');
                var data=[''];
                if(isSet(list[1])) {
                  data=list[1].split('(');
                  if(isSet(data[1])) data[1]=data[1].split(')')[0]; else data[1]='';
                  avalue.value[i]={dtmf: list[0], action: data[0], data: data[1]};
                }
              }
              var entry=this.createMenuAction(avalue.value[i].dtmf, avalue.value[i].action, avalue.value[i].data);
              entry.widget=this;
              this.content.appendChild(entry);
              entry.select.onchange({target: entry.select});
            }
          }
          return true;
        }
        disable() {
          var nodes = this.node.querySelectorAll('input');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=true;
          }
          nodes = this.node.querySelectorAll('select');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=true;
          }
          nodes = this.node.querySelectorAll('button');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=true;
          }
          return true;
        }
        disabled() {
          return this.inputdiv.btn.disabled;
        }
        enable() {
          var nodes = this.node.querySelectorAll('input');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=false;
          }
          nodes = this.node.querySelectorAll('select');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=false;
          }
          nodes = this.node.querySelectorAll('button');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=false;
          }
          return true;
        }
        getID() {
          return this.content.id;
        }
        getValue() {
          var result=[];
          for(var i=0; i<this.content.childNodes.length; i++) {
            result.push(this.getMenuAction(this.content.childNodes[i]));
          }
          return result;
        }
      }

      var card=null;
      var menu_profile_id='';
      function updateMenus() {
        this.sendRequest('menu-profiles').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==menu_profile_id) hasactive=true;
              items.push({id: data[i].id, title: (data[i].title!='')?data[i].title:data[i].id, active: data[i].id==menu_profile_id});
            }
          };
          rightsidebar_set(items);
          if(!hasactive) {
            card.hide();
            window.history.pushState(menu_profile_id, $('title').html(), '/'+urilocation);
            menu_profile_id='';
            sidebar_apply(null);
            rightsidebar_init(null, sbadd, sbselect);
            if(data.length>0) loadMenu(data[0].id);
          } else {
            sidebar_apply(sbapply);
            rightsidebar_init(sbdel, sbadd, sbselect);
            loadMenu(menu_profile_id);
          }
        });
      }

      function loadMenu(profile_id) {
        this.sendRequest('menu-profile', {menu: profile_id}).success(function(data) {
            if(data.id==profile_id) {
              menu_profile_id=data.id;
              rightsidebar_activate(menu_profile_id);
              rightsidebar_init(sbdel, sbadd, sbselect);
              data.name=data.id;
              data.desc=data.title;
              card.setValue(data);
              card.show();
              if(data.readonly) card.disable(); else card.enable();
              sidebar_apply(data.readonly?null:sbapply);
            }
        });
      }

      function sendMenuData() {
        var data = card.getValue();
        data.id = data.name;
        data.orig_id = menu_profile_id;
        data.title = data.desc;
        this.sendRequest('set-menu-profile', data).success(function() {
          menu_profile_id=data.id;
          updateMenus();
          return true;
        });
      }

      function sendMenu() {
        var proceed = false;
        var data = card.getValue();
        data.orig_id = menu_profile_id;
        data.id = data.name;
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
              menu_profile_id='';
            }
            if(proceed) {
              sendMenuData();
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendMenuData();
        }
      }

      function addMenu() {
        menu_profile_id='';
        rightsidebar_activate(null);
        card.setValue({name: 'custom_menu', desc: 'Произвольное меню', actions: []});
        card.show();
        sidebar_apply(sbapply);
        rightsidebar_init(null, null, sbselect);
      }

      function removeMenu() {
        showdialog('Удаление профиля','Вы уверены что действительно хотите удалить профиль меню участника?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            var data = {};
            data.id = menu_profile_id;
            this.sendRequest('remove-menu-profile', data).success(function(data) {
              menu_profile_id='';
              updateMenus();
              return true;
            });
          }
        });
      }

      function sbselect(e, item) {
        loadMenu(item);
      }

      function sbapply(e) {
        sendMenu();
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbadd(e) {
        addMenu();
      }

      function sbdel(e) {
        removeMenu();
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
        obj = new widgets.input(card, {id: 'name', pattern: /[a-zA-Z0-9_-]+/}, "Идентификатор меню");
        obj = new widgets.input(card, {id: 'desc'}, "Наименование меню");
        obj = new widgets.confmenu(card, {id: 'actions', sound_data: sound_data, context_data: context_data}, "Пункты меню", "Укажите последовательность DTMF кодов для вызова пункта меню");
        card.hide();
        updateMenus();
      });
      </script>
    <?php
  }

  public function render() {
    ?>
    <?php
  }

}

?>