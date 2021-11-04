<?php

namespace core;

class StaffSettingsView extends \view\Collection {

  public static function getLocation() {
    return 'settings/staff';
  }

  public static function getViewLocation() {
    return 'staff';
  }

  public static function getAPI() {
    return 'staff';
  }

  public static function getMenu() {
    return (object) array('name' => 'Сотрудники', 'prio' => 3, 'icon' => 'ContactsSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    $result &= self::checkPriv('settings_reader');
    return $result;
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {
        this.card = null;

        if(!isSet(data)) data = {};
        this.group_id=(typeof data.id != undefined)?data.id:'';
        this.contact_id = null;
        this.defaulttype = '';

        this.newcontacthandlers = [];
        this.savecontacthandlers = [];

        if(!isSet(this.__proto__.contactdialog)) this.__proto__.contactdialog = new widgets.dialog(dialogcontent, null, _('Редактирование контакта'));


        [this.contact, this.columns, this.domains] = await Promise.all([require('staff/contact', this.__proto__.contactdialog), this.asyncRequest('columns'), this.asyncRequest('domains')]);


        this.allcollist = {};
        
        for(let i in this.columns) {
          if(isSet(this.columns[i].module)) {
            this.allcollist[this.columns[i].id] = this.columns[i].module+': '+this.columns[i].title;
          } else {
            this.allcollist[this.columns[i].id] = this.columns[i].title;
          }
        }

        this.allcollist['controls'] = '';
        
        this.title = new widgets.input(parent, {id: 'title', expand: true},
            _("Группа контактов"));
        this.domain = new widgets.select(parent, {id: 'domain', options: this.domains},
            _("Домен"), _("Задает домен, которому принадлежат контакты"));

        this.addcontactbtn = new widgets.iconbutton(this.title, {id: 'addcontactbtn', color: 'success', icon: 'AddSharpIcon', onClick: this.addContact}, null, 
            _("Добавить контакт"));
        this.newfile = new widgets.file(parent, {accept: 'application/json'});
        this.newfile.hide();
        this.importbtn = new widgets.iconbutton(this.title, {id: 'importbtn', class: 'warning', icon: 'CloudImportSharpIcon'}, null,
            _("Импорт"));
        this.importbtn.hide();
        this.importbtn.onClick = () => {
          this.newfile.onChange = function(sender) {
            if(!sender.getValue() == '') {
              importContact(this.importbtn, sender.getValue());
            }
          }
          this.newfile.open();
        };
        this.exportbtn = new widgets.iconbutton(this.title, {id: 'exportbtn', class: 'success', icon: 'CloudExportSharpIcon'}, null, 
            _("Экспорт"));
        this.exportbtn.onClick = this.exportContact;
        this.exportbtn.hide();

        this.contactstable = new widgets.table(parent, {id: 'contacts', head: this.allcollist, value: []});
        this.contactstable.selfalign = {xs: 12};
        this.contactstable.disableCellSort('controls');
        this.contactstable.setHeadWidth('controls', '120px');
        // this.contactstable.setCellFilter('controls', this.filterControls);
        this.contactstable.setCellControl('controls', [
          new widgets.iconbutton(null, {id: 'remove', color: 'default', onClick: this.removeContact, icon: 'DeleteSharpIcon'}, null, _('Удалить контакт')),
          new widgets.iconbutton(null, {id: 'edit', color: 'primary', onClick: this.editContact, icon: 'EditSharpIcon'}, null, _('Редактировать контакт')),
        ]);

        this.newcontacthandlers.push(() => {
          this.sendRequest('newid', {group: this.group_id}).success((data) => {
            this.contactdialog.setValue({id: data});
          });
          this.contactdialog.setValue({id: 1, name: '', title: '', actions: {applications: this.applications, users: this.users, value: []}});
        });
        this.hasAdd = true;
        this.hasSave = true;
      }

      function setMode(mode) {
        switch(mode) {
          case 'basic': {
            this.title.selfalign = {xs: 12};
            this.domain.hide();
          } break;
          default: {
            this.domain.show();
            if(this.domain.hidden||this.domain.hiddenreal) {
              this.title.selfalign = {xs: 12};
            } else {
              this.title.selfalign = null;
            }
          } break;
        }
      }

      function setValue(data) {
        if((isSet(data.id))&&(data.id.indexOf('@')==-1)) {
          this.importbtn.show();
          this.exportbtn.show();
        } else {
          this.importbtn.hide();
          this.exportbtn.hide();
        }
        this.parent.setValue(data);
        if((viewMode=='basic')||(this.domain.hidden||this.domain.hiddenreal)) {
          this.title.selfalign = {xs: 12};
        } else {
          this.title.selfalign = null;
        }
        this.data = data;
      }

      function editContact(e, data) {
        this.contact.load(data.item.id);
        this.__proto__.contactdialog.setLabel(data.item.group_title);
        this.__proto__.contactdialog.setElement(data.item.name);
        this.__proto__.contactdialog.show();
      }

      function addContact(e) {
        console.log(this.data);
        this.contact.add();
        this.__proto__.contactdialog.setLabel(this.data.title);
        this.__proto__.contactdialog.setElement(_('Добавление контакта'));
        this.__proto__.contactdialog.show();
      }

      function removeContact(e) {
        showdialog(_('Удаление контакта'),_('Вы уверены что хотите удалить контакт <b>{0}</b>?').format(e.rowdata.name),"warning",['Yes', 'No'],function(a) {
          if(a=='Yes') {
            let data = {};
            data.group = this.group_id;
            data.id = e.rowdata.id;
            this.sendRequest('remove', data, 'rest/staff/contact').success(function(data) {
              this.contact_id='';
              this.loadContacts(this.group_id);
            }.bind(this));
          }
        }.bind(this));
      }

      async function add() {
        setCurrentID('');
        this.contact_id='';
        let tpl_data={id: 'new-group', title: _('Новый список контактов'), contacts: {value: [], clean: true}};

        super.setValue(tpl_data);
        this.addcontactbtn.hide();
      }

      async function remove(id, title) {
        showdialog(_('Удаление адресной книги'),_('Вы уверены что действительно хотите удалить адресную книгу <b>\"{0}\"</b>?').format(title),"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            let data = {};
            data.id = this.group_id;
            this.sendRequest('remove', data).success(function(data) {
              this.group_id='';
              this.loadContactGroups();
            }.bind(this));
          }
        }.bind(this));
      }

      function sendContactGroup() {
        let proceed = false;
        let data = this.card.getValue()
        data.orig_id = this.group_id;
        delete data.contacts;
        if(data.id=='') {
          showalert('warning',_('Не задан идентификатор адресной книги'));
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog(_('Идентификатор адресной книги изменен'),_('Выберите действие с адресной книгой:'),"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              data.copy = data.orig_id;
              data.orig_id = '';
              proceed=true;
              this.group_id='';
            }
            if(proceed) {
              this.sendRequest('set', data).success(function() {
                this.group_id = data.group;
                this.parent.btn.node.classList.remove('btn-danger');
              });
            }
            this.parent.btn.node.classList.add('btn-secondary');
          });
        }
        return true;
      }

      function cellSubnameFilter(value, rowdata, field) {
        let fields = field.split('.');
        value = rowdata;
        for(let i in fields) {
          if(!isSet(value[fields[i]])) {
            value = null;
            break;
          }
          value = value[fields[i]];
        }
        if(!value) return _('Не задан');
        return value;
      }

      function cellIdFilter(value, rowdata, field) {
        let number = value.split('@');
        if(isSet(rowdata.group_title)) {
          return number[0]+'@['+rowdata.group_title+']';
        }
        return number[0];
      }

      function importContact(sender, data) {
        this.sendRequest('import', {action: 'import', file: data}).success(function() {
          this.loadContactGroups();
        }.bind(this));
      }

      function exportContact(sender) {
        sendSingleRequest('export').success(function() {
          window.location = '?json=export';
        });
      }
 
    </script>
    <?php
  }

}

?>
