<?php

namespace core;

class AddressBookManage extends \view\Collection {

  public static function getLocation() {
    return 'manage/addressbook';
  }

  public static function getAPI() {
    return 'addressbook';
  }

  public static function getViewLocation() {
    return 'addressbook';
  }
  public static function getMenu() {
    return (object) array('name' => 'Адресные книги', 'prio' => 4, 'icon' => 'oi oi-spreadsheet');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function implementation() {
    ?>
      <script>

      async function init(parent, data) {

        this.id = null;

        this.contactdialog = new widgets.dialog(dialogcontent, null, _('Редактирование контакта'));
        this.contactdialog.onSave = this.saveContact;
        await require('addressbook/entry', this.contactdialog);

        this.name = new widgets.input(parent, {id: 'name', expand: true}, _('Название книги'));

        this.addcontactbtn = new widgets.button(this.name.inputdiv, {id: 'addcontactbtn', class: 'success', icon: 'oi oi-plus'}, "Добавить контакт");
        this.addcontactbtn.node.classList.add('ml-md-3')
        this.addcontactbtn.onClick = this.addContact;

        this.newfile = new widgets.file(parent, {accept: 'text/csv'});
        this.newfile.hide();
        this.importbtn = new widgets.button(this.name.inputdiv, {id: 'importbtn', class: 'warning', icon: 'oi oi-data-transfer-upload'}, "Импорт");
        this.importbtn.node.classList.add('ml-md-3')
        this.importbtn.onClick = this.importClick;
        this.exportbtn = new widgets.button(this.name.inputdiv, {id: 'exportbtn', class: 'success', icon: 'oi oi-data-transfer-download'}, "Экспорт");
        this.exportbtn.node.classList.add('ml-md-3')
        this.exportbtn.onClick = this.exportAddressbook;

        this.contactstable = new widgets.table(parent, {id: 'contacts', expand: true, sorted: true, head: {name: _('Наименование'), title: _('Должность'), phones: _('Номера'), remove: ' ', edit: ''}, expand: true, value: [], clean: true});
        this.contactstable.setHeadHint('phones', _('Все номера которые назначены контакту адресной книги'));
        this.contactstable.setHeadWidth('remove', '1px');
        this.contactstable.setHeadWidth('edit', '1px');
        this.contactstable.setCellFilter('phones', this.contact_phones);
        this.contactstable.setCellControl('remove', {class: 'button', initval: {class: 'danger', icon: 'oi oi-trash', onClick: this.removeContact}, title: '', novalue: true});
        this.contactstable.setCellControl('edit', {class: 'button', initval: {icon: 'oi oi-pencil', onClick: this.editClick}, title: '', novalue: true});

        this.hasAdd = true;

      }

      function setValue(data) {
        if(!isSet(data.id)) data.id = null;
        this.id = data.id;
        if(this.id) {
          this.addcontactbtn.show();
          this.exportbtn.show();
          this.importbtn.show();
        } else {
          this.addcontactbtn.hide();
          this.exportbtn.hide();
          this.importbtn.hide();
        }
        this.parent.setValue(data);
      }

      function getValue() {
        return {
          id: this.id,
          name: this.name.getValue()
        };
      }

      function saveContact() {
        let data = this.contactdialog.view.getValue();
        data.book = this.id;
        sendSubject('rest/addressbook/entry', this.contactdialog.view.id, data).success(function(data) {
          this.contactdialog.hide();
          loadEntry();
        }.bind(this));
      }

      function removeContact(e) {
        let data = this.contactdialog.view.getValue();
        data.book = this.id;
        removeSubject('rest/addressbook/entry', e.rowdata.id).success(function(data) {
          loadEntry();
        }.bind(this));
      }

      async function add(sender) {
        this.setValue({id: false, name: _('Новая адресная книга'), contacts: []});
        setCurrentID(null);
      }

      function addContact(sender) {
        this.contactdialog.view.setValue({book: this.id, id: null});
        this.contactdialog.show();
      };


      function importClick(sender) {
        this.newfile.enable();
        this.newfile.input.click();
        this.newfile.onChange = this.importAddressbook;
      }
      
      function importAddressbook(sender) {
        if(!sender.getValue() == '') {
          this.sendRequest('import', {id: this.id, file: sender.getValue()}).success(function() {
            loadEntry();
            sidebar_apply(saveEntry);
          }.bind(this));
        }
      }

      function exportAddressbook(sender) {
        window.location = '/rest/addressbook?json=export&id='+this.id;
      }

      function contact_phones(aphones) {
        return aphones.join(', ');
      }

      function editClick(e) {
        this.contactdialog.view.setValue({book: this.id, id: e.rowdata.id});
        this.contactdialog.show();
      }

    </script>
    <?php
  }

}

?>