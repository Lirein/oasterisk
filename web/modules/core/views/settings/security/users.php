<?php

namespace core;

class UsersSecuritySettings extends \view\Collection {

  public static function getLocation() {
    return 'settings/security/users';
  }

  public static function getAPI() {
    return 'security/user';
  }

  public static function getViewLocation() {
    return 'users';
  }

  public static function getMenu() {
    return (object) array('name' => 'Пользователи', 'prio' => 1, 'icon' => 'PermIdentitySharpIcon');
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
        this.id = new widgets.input(parent, {id: 'id', pattern: /[a-zA-Z0-9_-]+/, placeholder: 'unique_id', default: ""}, _("Логин"), _("Уникальный идентификатор пользователя"));
        this.secret = new widgets.input(parent, {id: 'secret', password: true, required:true, default: ""}, _("Пароль"));
        this.name = new widgets.input(parent, {id: 'name', default: _('Новый пользователь') }, _("Имя пользователя"));
        this.role = new widgets.select(parent, {id: 'role', view: 'groups', default: 'full_control'}, _("Группа безопасности"));
        this.expert = new widgets.toggle(parent, {id: 'expert', expand: true, value: false, default: false}, _('Включить экспертный режим доступа'), _('Использование экспертного режима рекомендуется включать <b>только квалифицированному персоналу</b> в области Связи или ведущему системному администратору.<br>Изменение настроек АТС в экспертном режиме <b>может привести к полной или частичной неработоспособности</b> системы связи.<br><i>Обратитесь к руководству пользователя для подробных инструкций.</i>'))
        this.displayconnects = new widgets.toggle(parent, {single: true, id: 'displayconnects', default: true}, _("Уведомления о действиях пользователя"));
        this.multiplelogin = new widgets.toggle(parent, {single: true, id: 'multiplelogin', default: true}, _("Разрешить несколько сессий"));
        this.acl = new widgets.select(parent, {id: 'acl', readonly: false, multiple: true, minlines: 0, view: 'acl', default: []}, _("Списки прав доступа"));
        this.permit = new widgets.collection(parent, {id: 'permit', select: 'iplist', entry: 'iplist', options: [true], default: []}, _("Разрешенные адреса"), _("Укажите адреса, с которых разрешен вход данному пользователю."));

        this.hasSave = true;
        this.hasAdd = true;
      }

      function setMode(mode) {
        switch(mode) {
          case 'basic': {
            this.displayconnects.hide();
            this.multiplelogin.hide();
            this.acl.hide();
            this.permit.hide();
            this.expert.hide();
          } break;
          case 'advanced': {
            this.displayconnects.hide();
            this.multiplelogin.hide();
            this.acl.show();
            this.permit.show();
            this.expert.show();
          } break;
          case 'expert': {
            this.displayconnects.show();
            this.multiplelogin.show();
            this.acl.show();
            this.permit.show();
            this.expert.show();
          } break;
        }
      }

      function setValue(data) {
        this.data = data;
        this.parent.setValue(data);
        if(data.iscurrentuser) {
          this.hasremove = false;
        } else {
          this.hasremove = true;
        }
        if(isSet(data.viewmode)) {
          this.expert.setValue(data.viewmode == 'expert');
          if(data.iscurrentuser && viewMode!=data.viewMode) setViewMode(data.viewmode);
        }
      }

      function getValue() {
        let data = this.parent.getValue();
        if(data.expert) data.viewmode = 'expert';
        else {
          if(this.data.iscurrentuser&&(viewMode!='basic')) data.viewmode = 'advanced';
          else data.viewmode = 'basic';
        }
        delete data.expert;
        if(data.acl == '') data.acl = null;
        return data;
      }

      async function add() {
        super.add();
        this.setValue({id: null});
      }

      async function send () {
        let result = this.getValue();
        let logout = false;
        let removethis = false;

        if(this.data.iscurrentuser && ((this.data.id != data.id) || (this.data.secret != data.secret))) logout = true;
        if (this.data.id) {
          if ((viewMode == 'expert')&&(result.id != this.data.id)) { 
            let modalresult = await showdialog(null,_("Логин был изменен. Переименовать или копировать пользователя?"),"error",['Copy', 'Rename', 'Cancel']); //Cancel не работает
            if(modalresult=='Copy') {
              if ((result.id == this.data.id)) result.id = false;
            } else if (modalresult=='Rename') {
              if ((result.id != this.data.id)) removethis = true;
            } else {
              return false;
            }
          } 
        } 

        if ((result.id != this.data.id)&&(this.currentItems.indexOfId(result.id) != -1)) {
          let modalresult = await showdialog(null,_("Пользователь с логином '{0}' уже существует. Заменить пользователя?").format(result.id),"error",['Yes','No']);
          if(modalresult=='No') return false;
        }

        if (removethis) super.remove(this.data.id);
        super.send();
        this.load();
        //if (logout) logout(); ToDo
      }

      async function remove(id, title) {
        let modalresult = await showdialog(_('Удаление пользователя'),_('Вы уверены что действительно хотите удалить <b>"{0}"</b>?').format(title),"error",['Yes','No']);
        if(modalresult=='Yes') {
          return await super.remove(id, title);
        }
        return false
      }

    </script>
    <?php
  }

}

?>