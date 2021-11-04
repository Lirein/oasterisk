<?php

namespace core;

class GroupsSecuritySettings extends \view\Collection {

  public static function getLocation() {
    return 'settings/security/groups';
  }

  public static function getAPI() {
    return 'security/group';
  }

  public static function getViewLocation() {
    return 'groups';
  }

  public static function getMenu() {
    return (object) array('name' => 'Группы безопасности', 'prio' => 2, 'icon' => 'GroupSharpIcon');
  }
 
  public static function check() {
    $result = true;
    $result &= self::checkPriv('security_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function implementation() {
    ?>
    <script>

      async function init(parent, data) {
        [this.privs, this.scopes] = await Promise.all([this.asyncRequest('privileges'), this.asyncRequest('scopes')]);
        for(let i in this.privs) {
          let priv = {id: this.privs[i]};
          switch(priv.id) {
            case 'system_info': priv.title = _('Получении информации о состоянии АТС'); break;
            case 'system_control': priv.title = _('Управление АТС'); break;
            case 'realtime': priv.title = _('Получение событий реального времени'); break;
            case 'agent': priv.title = _('Оператор очереди вызовов'); break;
            case 'dialing': priv.title = _('Осуществление исходящих вызовов'); break;
            case 'message': priv.title = _('Отправка текстовых сообщений'); break;
            case 'settings_reader': priv.title = _('Чтение настроек'); break;
            case 'settings_writer': priv.title = _('Запись настроек'); break;
            case 'dialplan_reader': priv.title = _('Чтение номерного плана'); break;
            case 'dialplan_writer': priv.title = _('Запись номерного плана'); break;
            case 'security_reader': priv.title = _('Чтение настроек безопасности'); break;
            case 'security_writer': priv.title = _('Изменение настроек безопасности'); break;
            case 'cdr': priv.title = _('Доступ к аналитике'); break;
            case 'invoke_commands': priv.title = _('Запуск приложений'); break;
            case 'debug': priv.title = _('Отладка'); break;
            default: priv.title = priv.id;
          }
          this.privs[i] = priv;
        }
        this.miniprivs = [
          {id: 'manage', title: _('Управление')},
          {id: 'read', title: _('Чтение настроек')},
          {id: 'write', title: _('Изменение настроек')},
          {id: 'cdr', title: _('Статистика')}
        ];
        this.rights = {};
        this.rights.manage = ['system_info', 'system_control', 'realtime', 'agent', 'dialing', 'message', 'invoke_commands'];
        this.rights.read = ['settings_reader', 'dialplan_reader', 'security_reader'];
        this.rights.write = ['settings_writer', 'dialplan_writer', 'security_writer'];
        this.rights.cdr = ['cdr'];
        this.id = new widgets.input(parent, {id: 'id', pattern: /[a-zA-Z0-9_-]+/, default: '', placeholder: 'unique_id'}, _("Идентификатор профиля безопасности"), _("Уникальный идентификатор профиля безопасности в системе"));
       // this.id.onInput=this.checkRole;
        this.name = new widgets.input(parent, {id: 'name', expand: true}, _("Имя профиля"));
       // this.name.onInput=this.checkRole;
        this.section = new widgets.section(parent, null);
        this.section.selfalign = {xs: 12};
        this.section.itemsalign = {xs: 12, lg:6};
        this.scope = new widgets.list(this.section, {id: 'scope', options: this.scopes, checkbox: true, tree: true, lines: 4}, _("Область видимости профиля безопасности"), _("Область видимости ограничивает набор привилегий указанными частями графического интерфейса. Например - изменение настроек только учетных записей SIP.<br>Если область видимости не задана, доступ предоставляется ко всем загруженным модулям в зависимости от набора привилегий."));
        this.scope.onChange = this.scopeChange;
        this.privileges = new widgets.list(this.section, {id: 'privileges', options: [], checkbox: true}, _("Набор привилегий безопасности"), _("Задаёт набор привилегий доступных пользователю"));
        this.privileges.onChange = this.privilegeChange;

        this.hasAdd = true;
        this.hasSave = true;
      }

      function setValue(data) {
        this.parent.renderLock();
        this.data = data;
        this.parent.setValue(data);
        //this.scope.setValue([].concat(data.scope, data.objects));
        this.updatePrivileges();
        this.checkRole();
        this.parent.renderUnlock();
        this.parent.redraw();
      }

      function getValue() {
        let result = this.parent.getValue();
        if (!isSet(result.id)) result.id = this.data.id;
        if (viewMode != 'expert') {
          let privs = [];
          result.privileges.forEach(priv => privs = privs.concat(this.rights[priv]));
          result.privileges = privs;
        }
        return result;
      }

      function setMode(mode) {
        this.parent.renderLock();
        switch(mode) {
          case 'basic': 
          case 'advanced': {
            this.id.hide();
            this.name.selfalign = {xs:12};
          } break;
          case 'expert': {
            this.id.show();
            this.name.selfalign = {xs:12, lg:6};
          } break;
        }
        //this.setScopeMode
        this.updatePrivileges()
        this.parent.renderUnlock();
        this.parent.redraw();
      }

      function setScopeMode(items, values, mode) {
        for(let i in items) {
          if(isSet(items[i].value)) {
            this.setScopeMode(items[i].value, values, mode);
          } else {
            if(mode=='selected') {
              if(items[i].type!='object') {
                values.push(items[i].id);
              }
            } else {
              if(items[i].type!='object') {
                let index = values.indexOf(items[i].id);
                if(index!=-1) {
                  values.splice(index, 1);
                }
              }
            }
          }
        }
      }

      function getOption(item, root) {
        let option = null;
        if((!isSet(root))||(root == null)) root = this.scope.options;
        for(let i in root) {
          let entry = root[i];
          if(entry.id == item) {
            option = entry;
            break;
          }
          if((isSet(entry.value))&&(entry.value)&&(entry.value.length>0)) option = this.getOption(item, entry.value);
          if(option) break;
        };
        return option;
      }

      function updatePrivileges() {
        switch(viewMode) {
          case 'basic':
          case 'advanced': {
            this.data.miniprivs = [];
            if(isSet(this.data.privileges)) {
              let rights = []; 
              this.miniprivs.forEach((item) => {
                rights = []; 
                this.rights[item.id].forEach(right => { if(this.data.privileges.indexOf(right)!=-1) rights.push(right);});
                if (this.rights[item.id].equals(rights)) {
                  this.data.miniprivs.push(item.id);
                }
              });
              this.privileges.setValue({options: this.miniprivs, value: this.data.miniprivs});  
            }
          } break;
          case 'expert': {
            this.privileges.setValue({options: this.privs, value: this.data.privileges});  
          } break;
        }
      }

      function checkRole() {
        switch(this.data.id) {
          case 'full_control':
          case 'admin':
          case 'technician':
          case 'operator':
          case 'manager': {
            this.scope.disable();
            this.privileges.disable();
          } break;
          default: {
            if(!this.data.readonly) {
              this.scope.enable();
              this.privileges.enable();
            } else {
              this.scope.disable();
              this.privileges.disable();
            }
          }
        }
      }

      function scopeChange(sender, item, items) {
        let index = items.indexOf(item.id);
        if(index!=-1) { //Selected
          if(isSet(item.value)) {
            // if(index!=-1) items.splice(index, 1);
            this.setScopeMode(item.value, items, 'selected');
          }
        } else { //Unselected
          if(isSet(item.value)) {
            // items.push(item.id);
            this.setScopeMode(item.value, items, 'unselected');
          }
        }
        return true;
      }

      function privilegeChange(sender, item) {
        if((viewMode != 'expert') && (item != null)) {
          if(item.checked === true) {
            this.rights[item.id].forEach(priv => {
              if(this.data.privileges.indexOf(priv)==-1) this.data.privileges.push(priv);
            });
          }
          if(item.checked === false) {
            this.rights[item.id].forEach(priv => {
              let pos = this.data.privileges.indexOf(priv);
              if(pos!=-1) this.data.privileges.splice(pos,1);
            });           
          }
        }
      }

      async function send () {
        let result = this.getValue();
        let system = false;
        let removethis = false;
        switch(this.data.id) {
          case 'full_control':
          case 'admin':
          case 'technician':
          case 'operator':
          case 'manager': system = true;
        }

        if (this.data.id) {
          // if (((viewMode == 'expert')&&(result.id != this.data.id)) ||
          //     ((viewMode != 'expert')&&(result.name != this.data.name)) ){
          if ((viewMode == 'expert')&&(result.id != this.data.id)) { //Нельзя одинаковые id, можно одинаковые имена
            if (system) {
              let modalresult = await showdialog(null,_("Изменение невозможно. Копировать группу?"),"error",['Yes','No']);
              if(modalresult=='Yes') {
                if ((result.id == this.data.id)) result.id = false;
              } else {
                return false;
              }
            } else {
              let modalresult = await showdialog(null,_("Имя группы было изменено. Переименовать или копировать группу?"),"error",['Copy', 'Rename', 'Cancel']); //Cancel не работает
              if(modalresult=='Copy') {
                if ((result.id == this.data.id)) result.id = false;
              } else if (modalresult=='Rename') {
                if ((result.id != this.data.id)) removethis = true;
              } else {
                return false;
              }
            }
          } else {
            if (system) {
              let modalresult = await showdialog(null,_("Изменение невозможно. Копировать группу?"),"error",['Yes','No']);
              if(modalresult=='Yes') {
                result.id = false;
              } else {
                return false;
              }
            }
          }
        } 

        if ((result.id != this.data.id)&&(this.currentItems.indexOfId(result.id) != -1)) {
          switch(result.id) {
            case 'full_control':
            case 'admin':
            case 'technician':
            case 'operator':
            case 'manager': {
              await showdialog(null,_("Группа '{0}' уже существует. Нельзя заменить системную группу").format(result.id),"error",['Yes']);
              return false;
              break;
            }
            default: {
              let modalresult = await showdialog(null,_("Группа '{0}' уже существует. Заменить группу?").format(result.id),"error",['Yes','No']);
              if(modalresult=='No') return false;
            }
          }
        }
        if (removethis) super.remove(this.data.id);
        this.setValue(result);
        super.send();
      }
      //ToDo update после копирования и переименования

      async function add() {
        this.setValue({id: false, name: _('Новая группа'), scope: [], objects: [], privileges: [], readonly: false});
        setCurrentID(null);
      };

      async function remove(id, title) {
        try {
          if(!isSet(title)) {
            title = id;
            if(id === this.data.id) {
              title = this.data.name;
            }
          }
          let modalresult = await showdialog(_("Удаление группы"),_("Вы уверены что действительно хотите удалить группу <b>\"{0}\"</b>?").format(title),"error",['Yes','No']);
          if(modalresult=='Yes') {
            return await super.remove(id, title);
          }
        } finally {
          return false;
        }
      }

      </script>
    <?php
  }

}

?>